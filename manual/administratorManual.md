# Administrator Manual — Greenhouse Observation App

> **Version:** 1.0.0-rc.1  
> **Date:** 26 May 2026  
> **Language note:** This manual is in **English** — the administrator works at the configuration file, database, and web-server level where the design documents (FDS, TDS, strategy) are also in English. The admin GUI itself is in Dutch; wherever this manual refers to an on-screen label the Dutch text is shown `"like this"`.

---

## Table of contents

1. [Role and responsibilities](#1-role-and-responsibilities)
2. [Installation](#2-installation)
3. [First-time setup](#3-first-time-setup)
4. [The admin GUI — overview](#4-the-admin-gui--overview)
5. [Dashboard](#5-dashboard)
6. [Greenhouses — Kassen](#6-greenhouses--kassen)
7. [Taxonomy — Taxonomie](#7-taxonomy--taxonomie)
8. [Observations — Waarnemingen](#8-observations--waarnemingen)
9. [Users — Gebruikers](#9-users--gebruikers)
10. [Export](#10-export)
11. [Changing your password — Wachtwoord](#11-changing-your-password--wachtwoord)
12. [Operations: backup, security, monitoring](#12-operations-backup-security-monitoring)
13. [Updates and schema migrations](#13-updates-and-schema-migrations)
14. [Lost-password recovery](#14-lost-password-recovery)
15. [Troubleshooting](#15-troubleshooting)

---

## 1. Role and responsibilities

One administrator per deployment. You are the single technical owner of this installation. Your responsibilities:

| Responsibility | When |
|---|---|
| Install and configure the app on the host | Once |
| Set the admin password via the setup wizard | Once, on first login |
| Create greenhouse records and post QR signs | At install; again when adding greenhouses |
| Support operators — handle name changes, correction requests, GDPR inquiries | Ongoing |
| Produce CSV exports for the analyst | On request |
| Maintain the host — backups, HTTPS, monitoring, updates | Ongoing |

There is no multi-admin support. There is no web-based "forgot my password" path — recovery requires shell access to the server (§14).

---

## 2. Installation

### 2.1 Prerequisites

| Item | Requirement |
|---|---|
| PHP | 7.4 minimum (PHP 7.4 is past upstream EOL — prefer 8.1+ for security updates) |
| Extensions | `pdo_sqlite` required. For HEIC photo uploads from iPhones: ImageMagick with `libheif`. |
| Web server | Apache ≥ 2.4 or nginx ≥ 1.18 |
| Storage | Two writable directories **outside** the web document root: one for the SQLite database, one for photos |
| Transport | Your responsibility. Plain HTTP is fine on a private LAN; public-internet deployments must use HTTPS |

### 2.2 Directory layout

```
/var/www/html/obs/           ← document root (web-served)
    index.php                ← entry point
    config.php               ← you create this (§2.3)
    template_config.php      ← shipped template — do not delete
    src/                     ← application code
    views/                   ← Dutch UI templates
    migrations/              ← numbered SQL migration files
    lang/nl.php              ← Dutch UI strings
    assets/css/              ← stylesheet

/var/lib/obs-app/            ← OUTSIDE document root
    db/
        greenhouse.db        ← SQLite database (created on first run)
    photos/                  ← photo storage (created on first run)
```

The paths under `/var/lib/obs-app/` are a convention — use any path outside the document root.

### 2.3 Configuration

Copy `template_config.php` to `config.php` (same directory, next to `index.php`) and fill in the values:

**Required keys:**

| Key | Default | Description |
|---|---|---|
| `admin_name` | *(empty)* | Your login name for the admin GUI. Must not be empty. |
| `admin_session_timeout` | `3600` | Admin inactivity timeout in seconds. Minimum 60. |
| `db_path` | `/var/www/greenhouse-data/greenhouse.db` | Absolute path to the SQLite file. **Must be outside the document root.** |
| `photo_root` | `/var/www/greenhouse-data/photos` | Absolute path to the photo directory. **Must be outside the document root.** |
| `admin_url_path` | `management` | The URL segment for the admin GUI (e.g. `management` → `https://host/management/`). Use something non-obvious on public-internet deployments. Letters, digits, hyphens, and underscores only. |
| `edit_window_hours` | `24` | How long operators may edit or delete their own observations after saving. There is no GUI control for this — change it here and restart. |
| `timezone` | `Europe/Amsterdam` | IANA timezone name. All user-facing timestamps and CSV exports use this timezone. All internal storage is UTC. |
| `retention_days` | `365` | Auto-delete observations older than this many days. Set to `0` to disable automatic deletion entirely. |

**Strongly recommended:**

| Key | Example | Description |
|---|---|---|
| `public_base_url` | `https://obs.example.com` | Canonical public URL, **no trailing slash**. Required for correct QR codes behind a reverse proxy. Without it, the app derives the URL from `$_SERVER`, which may yield an internal hostname. |

**Optional:**

| Key | Example | Description |
|---|---|---|
| `admin_contact` | `admin@example.com` | Shown on the privacy notice — the contact point for GDPR requests. |
| `error_log_path` | `/var/log/obs/error.log` | PHP error log destination. Defaults to PHP's `error_log` ini setting. |

> **Never put the admin password in `config.php`.** It is set through the setup wizard on first login and stored as a bcrypt hash in the database.

### 2.4 Filesystem permissions

The web-server user (`www-data` on Debian/Ubuntu, `apache` or `nginx` on RHEL) needs:

- **Read** on the application source tree and `config.php`
- **Read + write + execute** on the directory containing `db_path` and on `photo_root`

```bash
# Create the storage directories
mkdir -p /var/lib/obs-app/db /var/lib/obs-app/photos

# Grant the web-server user ownership
chown -R www-data:www-data /var/lib/obs-app/
chmod 750 /var/lib/obs-app/db /var/lib/obs-app/photos
```

On SELinux systems (RHEL, Fedora):

```bash
semanage fcontext -a -t httpd_sys_rw_content_t "/var/lib/obs-app(/.*)?"
restorecon -Rv /var/lib/obs-app
```

### 2.5 Web server: rewrite rules

All requests must be routed through `index.php`. Add the appropriate rewrite rule:

**Apache** — `.htaccess` or `VirtualHost`:

```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteRule ^ index.php [L]
```

**nginx** — `server {}` block:

```nginx
location / {
    try_files $uri $uri/ /index.php?$query_string;
}
location ~ \.php$ {
    fastcgi_pass unix:/run/php/php8.1-fpm.sock;
    include fastcgi_params;
    fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
}
```

---

## 3. First-time setup

### 3.1 Set your admin password

1. Open the admin URL in a browser: `https://<host>/<admin_url_path>/`  
   Example: `https://obs.example.com/management/`
2. Because the database has no admin record yet, the app shows the **setup wizard**.

> **📷 Screenshot needed:** The setup wizard — two password fields ("Nieuw wachtwoord", "Bevestigen") and a submit button.

3. Enter a strong password twice and submit. A password manager is recommended — the only recovery path is a manual database operation (§14).
4. The wizard redirects to the login page. Sign in with your `admin_name` (from `config.php`) and the password you just set.

### 3.2 Verify the installation

Before doing anything else, check the health endpoint:

```
GET https://<host>/health
```

Expected response (HTTP 200):

```json
{
  "status": "ok",
  "version": "1.0.0-rc.1",
  "db": "reachable",
  "ts": "2026-05-26T12:00:00Z"
}
```

If you get HTTP 503, the JSON body's `error` key explains what went wrong (misconfigured `db_path`, unsafe storage location, etc.).

### 3.3 Create your first greenhouse

No observations can be recorded until at least one greenhouse exists.

1. Go to `"Kassen"` in the navigation.
2. Click `"+ Kas toevoegen"`.
3. Fill in the form:
   - **Kas-ID** — exactly 4 uppercase hex characters (`[0-9A-F]{4}`). The convention is the **last 2 bytes of the paired greenhouse controller's MAC address in uppercase hex** — e.g. controller MAC `AA:BB:CC:DD:5E:3F` → ID `5E3F`. If there is no paired controller, pick any unique 4-hex value. The form accepts lowercase and normalises it to uppercase automatically.
   - **Naam** — the friendly name operators see on their home screen, e.g. `Kas Willemshoeve`.
   - **Locatie** — optional free-text location (e.g. `Noord-kas, rij 3`).
   - **Notities** — optional admin notes.
4. Click `"Opslaan"`.

### 3.4 Print and post the QR sign

Every greenhouse has a QR code that encodes its entry URL (`<public_base_url>/<gh-id>/`). Two ways to access it:

**From the greenhouse list** — click the `"QR"` button on the greenhouse's row. A modal opens showing the QR image with a `"Downloaden"` link to save the PNG.

**From the greenhouse edit page** — click `"Bewerken"` on the greenhouse row. The edit page shows the QR code inline under the form, along with the encoded URL for verification.

> **📷 Screenshot needed:** Greenhouse edit page showing the QR code section — the encoded URL as `<code>`, the QR image, and the greenhouse statistics below.

To produce the wall sign:
1. Download the QR PNG from the modal (or screenshot the inline QR from the edit page).
2. Place the image in a document next to the greenhouse name and the URL in plain text.
3. Print, laminate, and post it at the greenhouse entrance.

> **Important:** If `public_base_url` was not set in `config.php` when the QR was generated, the encoded URL may contain an internal hostname. Set the key, then re-download the QR and re-print the sign.

### 3.5 Review the pre-seeded taxonomy

On first run the app seeds **8 categories** with their default tags. The first five categories come from the strategy document; the three climate-impact categories were added via migration `0002_climate_taxonomy.sql`:

| Category (Dutch display name) | Internal key | Source |
|---|---|---|
| Welzijnscheck | `wellbeing` | Launch seed |
| Omgeving | `environment` | Launch seed |
| Gewas | `crop` | Launch seed |
| Sensor/regeling | `sensor_control` | Launch seed |
| Onderhoud | `maintenance` | Launch seed |
| Temperatuur | `temperature` | Migration 0002 |
| Luchtvochtigheid — te hoog | `humidity_high` | Migration 0002 |
| Luchtvochtigheid — te laag | `humidity_low` | Migration 0002 |

Check the taxonomy in `"Taxonomie"` before operators start recording. Rename display names to suit your site if needed (§7). Leave internal keys alone.

---

## 4. The admin GUI — overview

Open the admin URL. After login you see a navigation bar at the top of every page:

```
Dashboard | Kassen | Taxonomie | Waarnemingen | Gebruikers | Export | Wachtwoord    [Uitloggen]
```

> **📷 Screenshot needed:** The admin navigation bar — all seven navigation links plus the "Uitloggen" button on the right.

| Nav item | Dutch label | What it does |
|---|---|---|
| Dashboard | `Dashboard` | Overview counters: number of greenhouses, users, total observations, observations in the last 24 h |
| Greenhouses | `Kassen` | Create, view, edit, and delete greenhouse records; access QR codes |
| Taxonomy | `Taxonomie` | Manage categories and tags: add, rename, reorder, archive, hard-delete |
| Observations | `Waarnemingen` | Browse, filter, view, edit, and delete observations across all users |
| Users | `Gebruikers` | List operators, view their history, invalidate a cookie ("forget") |
| Export | `Export` | Download observations as CSV in two dialects |
| Password | `Wachtwoord` | Change your admin password |
| Logout | `Uitloggen` | End the admin session immediately |

The session expires automatically after `admin_session_timeout` seconds of inactivity (default 1 hour). You are redirected to the login page on expiry.

---

## 5. Dashboard

> **📷 Screenshot needed:** The Dashboard page — a small table showing "Kassen", "Gebruikers", "Waarnemingen (totaal)", "Waarnemingen (laatste 24 u)" with their counts.

A quick health check at a glance. No actions here — use the navigation to reach the detail pages.

---

## 6. Greenhouses — Kassen

### 6.1 Greenhouse list

> **📷 Screenshot needed:** The "Kassen" list page — table with columns ID, Naam, Locatie, Waarnemingen, and action buttons "QR" and "Bewerken" per row.

The list shows every greenhouse with:
- **ID** — the 4-character hex identifier (`5E3F`)
- **Naam** — the friendly name
- **Locatie** — optional location note
- **Waarnemingen** — number of observations linked to this greenhouse

**Actions per row:**
- `"QR"` — opens a modal with the QR code PNG and a `"Downloaden"` link
- `"Bewerken"` — opens the edit form

### 6.2 Creating a greenhouse

Click `"+ Kas toevoegen"` at the bottom of the list. See §3.3 for the field-by-field guide.

### 6.3 Editing a greenhouse

On the edit form:

- **ID** is read-only after creation — it is shown as plain text, not an input field. If you need to correct an ID, delete the greenhouse (requires zero observations — see §6.4) and recreate it.
- **Naam**, **Locatie**, and **Notities** can be changed at any time. The new name appears on the operator home screen on the next page load.
- Below the form: the QR code is shown inline with the encoded URL for verification.
- Below the QR: observation count for this greenhouse.
- If the observation count is **0**, a `"Verwijderen"` (delete) button is shown.

> **Changing the friendly name mid-season is fine. Changing the ID invalidates all printed QR signs — re-print before operators arrive.**

### 6.4 Deleting a greenhouse

You cannot delete a greenhouse that has observations. The `"Verwijderen"` button only appears when `Waarnemingen = 0`. To delete a greenhouse that has data:

1. Go to `"Waarnemingen"`, filter by that greenhouse.
2. Delete the observations individually (or have the analyst archive the CSV first).
3. Once the count is zero, return to the greenhouse edit page — the delete button appears.

This safeguard is intentional and cannot be bypassed through the GUI.

---

## 7. Taxonomy — Taxonomie

### 7.1 Category list

> **📷 Screenshot needed:** The "Taxonomie" page — table with columns "Volgorde" (↑↓ buttons), "Interne sleutel", "Naam", "Tags" (count), "Status" (badge), and action buttons "Tags", "Bewerken", "Archiveren/Herstellen".

Each row shows:
- **↑ / ↓** reorder buttons
- **Interne sleutel** — the stable English key (read-only after creation)
- **Naam** — the Dutch display name shown to operators
- **Tags** — count of tags in this category
- **Status** — `Actief` (green) or `Gearchiveerd` (grey)
- Action buttons: `"Tags"`, `"Bewerken"`, `"Archiveren"` / `"Herstellen"`

### 7.2 Reordering categories

Use the **↑** and **↓** buttons to change the display order. The order is reflected in the operator category picker (mock M3) on the next page load. The ↑ button on the first row and the ↓ button on the last row are greyed out.

### 7.3 Adding a category

Click `"+ Categorie toevoegen"` at the bottom.

- **Interne sleutel** — a short English identifier: lowercase letters, digits, and underscores only (e.g. `pest_monitoring`). **Cannot be changed after creation.** Observations are linked to this key permanently.
- **Weergavenaam** — the Dutch label operators see (e.g. `Plaag-monitoring`). Can be renamed at any time.

### 7.4 Renaming a category or tag

Click `"Bewerken"` on the row. You can only change the **display name** — the internal key is fixed. The new name appears in the recording flow and on existing observation detail views immediately after saving.

> The analyst's CSV columns `category_key` and `tag_key` contain the internal key, which never changes — model scripts referencing those columns continue to work after a rename.

### 7.5 Archiving vs hard-deleting

**Archive (recommended):** the item disappears from the recording flow (operators can no longer pick it) but historical observations still display it correctly. Reversible — click `"Herstellen"` to reactivate.

**Hard-delete:** the row is permanently removed. The GUI warns you with the count of observations that will lose their human-readable label. On confirm, those observations retain the raw `category_id` / `tag_id` database value — user-facing views fall back to the internal key as the label.

> **Best practice: always archive, never hard-delete.** Once the analyst has built a model on a taxonomy key, removing it permanently is difficult to undo.

### 7.6 Tags within a category

Click `"Tags"` on a category row to manage its tags.

> **📷 Screenshot needed:** The tag list page for a category — table with ↑↓ buttons, internal key, display name, observation count, status badge, and "Bewerken" / "Archiveren" actions; "+ Tag toevoegen" button at the bottom.

The tag list works the same way as the category list:
- **↑ / ↓** — reorder tags within the category; order is reflected in the operator tag picker (mock M4)
- `"Bewerken"` — rename the display name
- `"Archiveren"` / `"Herstellen"` — archive or restore
- `"+ Tag toevoegen"` — add a new tag (same internal key rules as categories; unique within the category, not globally)

### 7.7 Tag limit in the recording flow

The operator tag picker (mock M4) shows at most **6 tags** per category. If you add more than 6 tags to a category, archive the extras so only 6 remain active, or the operator will see more than 6 options — the interface handles it gracefully but it is harder to pick quickly on a phone.

---

## 8. Observations — Waarnemingen

### 8.1 Observation list with filters

> **📷 Screenshot needed:** The "Waarnemingen" page — filter bar at the top with "Kas" dropdown, "Van" and "Tot" date pickers, and "Zoeken" button; below it a table of observations.

**Filters:**
- **Kas** — select a specific greenhouse or `"— alle —"` for all
- **Van** — from date (inclusive)
- **Tot** — to date (inclusive)

Click `"Zoeken"` to apply. The result count appears above the table (`N waarneming(en)`).

The table shows: observation ID, timestamp, greenhouse name, operator handle, category, tag, severity (or `—`), and a truncated note preview.

Click `"Detail"` on any row to open the full observation.

### 8.2 Observation detail

> **📷 Screenshot needed:** Observation detail page — data table on the left with all fields (Tijdstip, Kas, Gebruiker, Categorie, Tag, Ernst, Opmerking, Aangemaakt, Bijgewerkt), photo below if present, and the "Verwijderen" button; links to "Bewerken" and "← Terug" at the bottom.

The detail page shows every stored field:
- Timestamp (in the configured timezone)
- Greenhouse name and ID
- Operator handle and internal user ID
- Category and tag display names
- Severity (1–5, or `—` if not set)
- Note (pre-formatted)
- Created at / updated at timestamps
- Photo at full resolution (if uploaded)

**Actions:**
- `"Bewerken"` — opens the edit form
- `"Verwijderen"` — permanently deletes the observation (confirmation dialog appears first)

### 8.3 Editing an observation

> **📷 Screenshot needed:** The observation edit form — table layout with Tijdstip (datetime-local input), Categorie (dropdown), Tag (dropdown, filtered by selected category), Ernst (segmented 1–5 radio buttons), Opmerking (textarea), Foto (current photo or file input for new upload); "Opslaan" and "Annuleren" buttons.

The edit form lets you modify every field:

| Field | Control | Notes |
|---|---|---|
| **Tijdstip** | `datetime-local` input | Labelled in the greenhouse's local timezone |
| **Categorie** | Dropdown | Changing the category filters the tag dropdown automatically |
| **Tag** | Dropdown | Only shows tags belonging to the selected category |
| **Ernst** | Segmented 1–5 buttons | `—` clears severity (stores SQL NULL) |
| **Opmerking** | Textarea | Free text |
| **Foto** | Inline preview if photo exists; file input if not | Click `"Foto verwijderen"` to remove an existing photo |

Admin edits are **not subject to the 24-hour operator edit window** — you can correct any observation at any age.

### 8.4 Deleting an observation

From the detail page, click `"Verwijderen"`. A browser confirmation dialog asks you to confirm. On confirm:
- The database row is deleted
- The photo file (if any) is unlinked from disk synchronously
- The observation disappears from all lists; its URL returns HTTP 404

Deletion is permanent — there is no soft-delete or recycle bin.

---

## 9. Users — Gebruikers

> **📷 Screenshot needed:** The "Gebruikers" page — table with columns Naam, Aangemaakt, Laatste bezoek, Kas, Waarnemingen, Status (Actief/Vergeten badge), and a "Vergeet mij" button on active rows.

### 9.1 What the user list shows

| Column | Meaning |
|---|---|
| **Naam** | The handle the operator chose on registration |
| **Aangemaakt** | Date the account was created |
| **Laatste bezoek** | Date the device last opened the app |
| **Kas** | The greenhouse the device is currently linked to |
| **Waarnemingen** | Total number of observations attributed to this user |
| **Status** | `Actief` — cookie valid; `Vergeten` — cookie invalidated |

### 9.2 Forgetting a user

Click `"Vergeet mij"` on an active user row. A confirmation dialog names the operator. On confirm:

- The user's cookie is invalidated. Their next visit to the app shows the registration screen (they appear as a new visitor).
- **The user record and all their observations remain in the database.** Nothing is deleted.
- The status badge changes to `Vergeten`.

Use this when an operator:
- Switches to a new phone and wants to re-link
- Shares a device with someone else and wants a fresh start
- Requests a full GDPR erasure (after which you also delete their observations — see §12.3)

### 9.3 Common operator requests

| Request | Action |
|---|---|
| "I want to change my name" | Tell them: open the app → `"Instellingen"` → `"Naam wijzigen"`. No admin action needed. |
| "I forgot my name" | Check the `"Gebruikers"` list — their handle is visible. |
| "My phone was reset, I can't log in" | They registered fresh with a new name. Their old name is still in the list as `Vergeten`. The old observations remain attributed to the old name. |
| "Please delete all my data (GDPR right to erasure)" | See §12.3. |
| "I want to download all my data (GDPR right of access)" | Tell them: open the app → `"Instellingen"` → `"Download mijn gegevens"`. A ZIP is produced in the browser — no admin action needed. |

---

## 10. Export

> **📷 Screenshot needed:** The "Export" page — four rows: Kas dropdown, Van date, Tot date, Formaat dropdown; "Download CSV" button.

### 10.1 Filter and export

1. **Kas** — select the greenhouse to export from (required; one at a time)
2. **Van** / **Tot** — optional date range; leave blank for all dates
3. **Formaat** — choose the CSV dialect:

| Option | Label | Use when |
|---|---|---|
| Dialect A | `"CSV — standaard (Python/R)"` | Handing off to the analyst. RFC 4180: comma-separated, UTF-8 without BOM, `T` separator in timestamps (`2026-05-23T14:30:00+02:00`). Parses with Python's `csv.reader` and `datetime.fromisoformat` without arguments. |
| Dialect B | `"CSV voor Excel (puntkomma, BOM)"` | Opening directly in Dutch Excel. Semicolon-separated, UTF-8 with BOM, space separator in timestamps (`2026-05-23 14:30:00+02:00`). Double-clicking opens it correctly in Dutch/German Excel. |

4. Click `"Download CSV"`.

### 10.2 CSV columns

Both dialects produce the same 12-column structure:

| Column | Content |
|---|---|
| `greenhouse_id` | 4-char hex greenhouse ID |
| `observation_id` | Integer row ID |
| `ts_iso8601` | Timestamp with timezone offset (separator differs by dialect) |
| `user_id` | Internal user ID (stable join key) |
| `user_handle` | Operator's handle at time of export (snapshot) |
| `category_key` | Category internal key (stable) |
| `category_display` | Category display name at time of export |
| `tag_key` | Tag internal key (stable) |
| `tag_display` | Tag display name at time of export |
| `severity` | 1–5 or empty if not recorded |
| `note` | Free-text note or empty |
| `photo_filename` | Relative photo path or empty |

The `_key` columns are the analyst's stable join keys. Display names can change across exports; keys do not.

### 10.3 Filename convention

| Dialect | Filename pattern |
|---|---|
| A (CSV) | `observations_<gh-id>_<from>_<to>.csv` |
| B (Excel) | `observations_<gh-id>_<from>_<to>_excel.csv` |

An unbounded date produces `all` in the corresponding position (e.g. `observations_5E3F_all_20260526.csv`).

Both variants can coexist in the same folder — the `_excel` suffix distinguishes them.

### 10.4 Empty result

If the filter matches zero observations, the download still succeeds: the file contains only the header row. The analyst's tooling sees a valid zero-row CSV.

---

## 11. Changing your password — Wachtwoord

Click `"Wachtwoord"` in the navigation bar.

> **📷 Screenshot needed:** The "Wachtwoord wijzigen" form — three fields: "Huidig wachtwoord", "Nieuw wachtwoord", "Bevestigen"; "Wijzigen" button.

Enter your current password, then the new password twice, and click `"Wijzigen"`. The new password is hashed (bcrypt) and stored; the old hash is discarded. The change takes effect immediately.

If you enter the wrong current password, the form returns an error and the stored hash is unchanged.

> If you cannot remember your current password, see §14 (lost-password recovery).

---

## 12. Operations: backup, security, monitoring

### 12.1 Backup

**Backup is your responsibility** — the application does not do it for you.

The two things to back up are the SQLite database file and the photo directory. Recommended nightly cron script:

```bash
#!/bin/bash
set -euo pipefail

DBFILE=/var/lib/obs-app/db/greenhouse.db
PHOTODIR=/var/lib/obs-app/photos
BACKUPDIR=/var/backups/obs-app
DATE=$(date +%Y%m%d)

mkdir -p "$BACKUPDIR"

# Safe online backup — atomic even while the app is serving traffic
sqlite3 "$DBFILE" ".backup $BACKUPDIR/greenhouse-$DATE.db"

# Mirror the photo directory
rsync -a --delete "$PHOTODIR/" "$BACKUPDIR/photos/"

# Keep 14 daily DB snapshots
find "$BACKUPDIR" -name 'greenhouse-*.db' -mtime +14 -delete
```

> Use `sqlite3 .backup` rather than `cp`. A plain `cp` can capture a partially-written transaction. `sqlite3 .backup` uses SQLite's online backup API and is safe under concurrent writes.

**Restore drill:** test this at least once before you need it. Stop the web server, copy a DB backup into place, rsync photos back, start the web server, open the admin GUI, verify the data.

### 12.2 HTTP security headers

Set these in your web-server configuration. The app itself does not emit them:

**nginx:**

```nginx
add_header Content-Security-Policy
  "default-src 'self'; img-src 'self' data:; style-src 'self' 'unsafe-inline'; script-src 'self'"
  always;
add_header X-Content-Type-Options  "nosniff"      always;
add_header X-Frame-Options         "DENY"          always;
add_header Referrer-Policy         "same-origin"   always;
# HTTPS only:
add_header Strict-Transport-Security
  "max-age=31536000; includeSubDomains" always;
```

**Apache** (in `<VirtualHost>` or `.htaccess`):

```apache
Header always set Content-Security-Policy \
  "default-src 'self'; img-src 'self' data:; style-src 'self' 'unsafe-inline'; script-src 'self'"
Header always set X-Content-Type-Options "nosniff"
Header always set X-Frame-Options "DENY"
Header always set Referrer-Policy "same-origin"
# HTTPS only:
Header always set Strict-Transport-Security "max-age=31536000; includeSubDomains"
```

### 12.3 PHP runtime settings

In `php.ini` or your PHP-FPM pool configuration:

```ini
expose_php = Off
upload_max_filesize = 8M
post_max_size = 12M
session.cookie_httponly = 1
session.cookie_samesite = Lax
default_charset = "UTF-8"
```

The app forces `display_errors = Off` at runtime regardless of the host's `php.ini`, so PHP errors never leak to operators. They go to `error_log_path` (config) or PHP's default error log.

### 12.4 Monitoring

Point an uptime checker (UptimeRobot, Healthchecks.io, a cron + `curl` script) at `/health`:

```
GET https://<host>/health
```

- **HTTP 200 + `"status":"ok"`** — everything is healthy
- **HTTP 503 + `"status":"degraded"`** — database unreachable or configuration invalid; the `error` key in the JSON body tells you what is wrong

A check every 5 minutes is sufficient for a community-scale deployment.

### 12.5 Data retention and the sweep

When `retention_days > 0` (the default is 365), the app runs a cleanup sweep automatically. The sweep:

1. Deletes observations (and their photos) older than `retention_days` days
2. Deletes admin audit log entries older than 90 days
3. Deletes orphan user rows (cookie invalidated **and** zero observations remaining)

The sweep runs at most once per admin login, throttled to once per hour. It does not run in the background — it piggybacks on your own login activity.

With `retention_days = 0`, none of the three cleanup steps run, regardless of data age.

### 12.6 Full GDPR erasure (right to be forgotten)

When an operator requests complete deletion of their data:

1. Go to `"Waarnemingen"`, filter by the user's greenhouse and date range to find their observations.  
   *(Tip: there is no filter by user name in the current list view — find the user's ID from `"Gebruikers"` first, then scan the observation list for their handle.)*
2. Open each observation and click `"Verwijderen"`. Repeat for all their observations.
3. Go to `"Gebruikers"`, find the user, click `"Vergeet mij"`. Their cookie is invalidated.
4. The next retention sweep deletes the now-observation-less user row automatically.

---

## 13. Updates and schema migrations

### Updating the application

1. **Back up first** (§12.1).
2. Replace the application source tree (`src/`, `views/`, `migrations/`, `lang/`, `assets/`, `index.php`) with the new release. **Do not overwrite `config.php`** — it is not included in the release archive.
3. The first request after restart triggers the migration runner: it reads the current `schema_meta.version` from the database, scans `migrations/` for files numbered higher than the current version, and applies them in order inside transactions.
4. Check `/health` and the admin GUI to confirm the update is clean.

No manual SQL is required for schema changes — everything is handled automatically by the migration runner.

### Migration files

Migration files are named `NNNN_<slug>.sql` (e.g. `0002_climate_taxonomy.sql`). Each file is idempotent — safe to re-run. Each file ends with:

```sql
INSERT OR REPLACE INTO schema_meta (id, version) VALUES (1, N);
```

The currently applied schema version is shown in the admin GUI footer.

---

## 14. Lost-password recovery

There is no web-based reset. Recovery requires shell access to the host:

1. SSH into the host.
2. Open the SQLite database:
   ```bash
   sqlite3 /var/lib/obs-app/db/greenhouse.db
   ```
3. Delete the admin record:
   ```sql
   DELETE FROM admin;
   .quit
   ```
4. Open the admin URL in a browser — the setup wizard re-appears.
5. Set a new password.
6. Sign in normally.

**Operator data is not affected.** The audit log notes that a new password was set, but does not record by whom — use your host's SSH audit log for accountability.

---

## 15. Troubleshooting

| Symptom | Likely cause | Resolution |
|---|---|---|
| Every request shows the setup-required page | `config.php` missing, or `admin_name` is empty | Verify `config.php` exists and `admin_name` is filled in |
| Every request returns HTTP 500 "unsafe storage location" | `db_path` or `photo_root` resolves inside the document root | Move them outside; the app refuses to run otherwise |
| Boot fails with a named config key | `validate_config()` rejected that value | Fix the value as described in the error message |
| Admin URL returns HTTP 404 | `admin_url_path` in config doesn't match the URL you're requesting | Open `config.php`, read `admin_url_path`, and use that exact path segment |
| `/health` returns HTTP 503 | Database unreachable, wrong path, or wrong permissions | Check `db_path` exists, is outside the document root, and is writable by the web-server user |
| QR codes decode to an internal hostname | `public_base_url` unset on a reverse-proxy deployment | Set `public_base_url` in `config.php`, then re-download QR images and re-print signs |
| Operators see "scan een QR-code" instead of the recording flow | Greenhouse doesn't exist yet, or the URL doesn't match a valid greenhouse ID | Go to `"Kassen"`, verify the greenhouse exists; re-print the QR sign if the URL was wrong |
| Admin login rejected: "too many attempts" | Rate-limit lockout after 5 wrong attempts from your IP | Wait (60 s → 120 s → ... up to 1 h cap); or run `DELETE FROM admin_login_attempts WHERE ip = '<your-ip>';` to reset manually |
| Admin session expires too quickly | `admin_session_timeout` is too low | Raise the value in `config.php` |
| Excel opens the CSV as a single column | Wrong dialect selected at export | Re-export with `"CSV voor Excel (puntkomma, BOM)"` |
| Operator photo upload fails with HTTP 413 | File exceeds `upload_max_filesize` in `php.ini` | Ask the operator to reduce the image resolution before sharing; or raise the limit in `php.ini` and restart PHP-FPM |
| HEIC photo upload rejected on iPhone | ImageMagick built without `libheif` | Install `libheif` and recompile ImageMagick, or instruct iPhone users: Settings → Camera → Formats → **Most Compatible** (saves as JPEG) |
| White page with no error shown | PHP fatal error; `display_errors` is off | Check `error_log_path` (or PHP's default error log) for the actual message |
| Operators say the category list looks wrong after a taxonomy change | Browser caching | Ask them to do a hard refresh (Ctrl+Shift+R / Cmd+Shift+R) |

---

*For questions not covered here, open an issue at the project repository, or consult the design documents: `design/functionalRequirements.md` (FDS) and `design/technicalDesignSpecification.md` (TDS).*
