# Administrator Manual — Greenhouse Observation App

> **Note on language.** This manual is in **English** because the administrator operates at the config-file / SQL / web-server level where the FDS, TDS, and strategy documents (also English) are the working references. The **admin GUI itself is in Dutch** (per FR-UI-070); wherever this manual references a clickable label the admin will see on screen, the Dutch text is quoted `"like this"`. The companion **user manual** (`userManual.md`, in this same directory) is in Dutch — it addresses the operators on the shop floor.

| Document | Administrator Manual — Greenhouse Observation App |
| Audience | The single administrator per herenboeren-corporation deployment |
| Assumed skill | Basic web hosting (FTP/SSH, edit a PHP file, run a SQL command, write a cron job) |
| Companion docs | `userManual.md` (operator-facing, Dutch — same directory); `../design/functionalRequirements.md` (FDS), `../design/technicalDesignSpecification.md` (TDS), `../design/operatorObservationStrategy.md` (strategy) |

---

## Contents

1. [Who is the administrator?](#1-who-is-the-administrator)
2. [Installation](#2-installation)
3. [First-time configuration](#3-first-time-configuration)
4. [Daily administration](#4-daily-administration)
5. [Greenhouses](#5-greenhouses)
6. [Users](#6-users)
7. [Observations](#7-observations)
8. [Taxonomy](#8-taxonomy)
9. [CSV export](#9-csv-export)
10. [Privacy and GDPR](#10-privacy-and-gdpr)
11. [Operations: backup, hardening, monitoring](#11-operations-backup-hardening-monitoring)
12. [Updates and migrations](#12-updates-and-migrations)
13. [Lost-password recovery](#13-lost-password-recovery)
14. [Troubleshooting](#14-troubleshooting)

---

## 1. Who is the administrator?

One person per deployment, sole technical contact for the herenboeren community using the app. Responsibilities:

- **Install** the app on the host and write the configuration.
- **Create greenhouses** and post their QR signs.
- **Day-to-day support**: respond to operator requests (forget my account, fix an observation older than 24 h, produce a CSV export for the analyst).
- **Operate the host**: backups, transport security (HTTPS — A1), updates, monitoring.

You are the single source of admin-side decisions for this deployment. There is no multi-admin support and no web-based "reset admin password" path — the recovery procedure requires shell access to the host (§13).

## 2. Installation

### Prerequisites

| Requirement | Detail |
|---|---|
| Hosting | Any conventional PHP host: shared LAMP/LEMP, managed PHP, or self-hosted VPS. |
| PHP | 7.4 or newer. PHP 7.4 is the supported minimum but is past upstream EOL — public-internet deployments should consider running 8.1+ for ongoing security updates (TDS-STK-060). |
| Web server | Apache ≥ 2.4 or nginx ≥ 1.18. Either works without code changes (TDS-STK-030). |
| PHP extensions | `pdo_sqlite` (required). For HEIC photo uploads from iPhones: ImageMagick built with `libheif`. |
| Filesystem | Write access to **two directories OUTSIDE the document root** — one for the SQLite database, one for photos. |
| Transport security | Up to you (A1). LAN-only deployments can run plain HTTP; public-internet deployments **shall** put HTTPS in front. |

### Layout the app expects

```
/var/www/html/          ← document root (web-served)
    index.php           ← application entry point
    config.php          ← YOU create this (see below)
    template_config.php ← shipped template
    src/                ← application code
    views/              ← Dutch UI templates
    migrations/         ← SQL migration files
    lang/nl.php         ← Dutch UI strings

/var/lib/obs-app/db/    ← OUTSIDE doc root
    observations.sqlite ← created on first run

/var/lib/obs-app/photos/ ← OUTSIDE doc root
    YYYY/MM/<obs-id>.jpg
    YYYY/MM/<obs-id>_thumb.jpg
```

The paths under `/var/lib/obs-app/` are conventional — pick anything outside the document root.

### Configuration

1. Copy `template_config.php` → `config.php` (in the application root, next to `index.php`).
2. Edit `config.php` and fill these required keys:

| Key | Example | Meaning |
|---|---|---|
| `admin_name` | `"admin"` | Your administrator login name. |
| `admin_session_timeout` | `1800` | Inactivity timeout in seconds (1800 = 30 min). |
| `db_path` | `"/var/lib/obs-app/db/observations.sqlite"` | SQLite file. Must resolve **outside the document root**. |
| `photo_root` | `"/var/lib/obs-app/photos"` | Photo directory. Must resolve outside the document root. |
| `admin_url_path` | `"beheer"` | Path segment of the admin URL. Default `"management"`; change to something less guessable on a public-internet deployment. |
| `edit_window_hours` | `24` | How long operators can edit/delete their own observations. |
| `timezone` | `"Europe/Amsterdam"` | IANA timezone name. |
| `retention_days` | `365` | Auto-delete observations older than this. `0` = keep forever. |

Strongly recommended:

| Key | Example | Meaning |
|---|---|---|
| `public_base_url` | `"https://obs.example.com"` | Canonical public URL. Required for QR correctness behind reverse proxies (TDS-URL-040). No trailing slash. |

Optional:

| Key | Example | Meaning |
|---|---|---|
| `admin_contact` | `"admin@example.com"` | Shown on the privacy page so users know who to ask. |
| `error_log_path` | `"/var/log/obs-app/error.log"` | Where PHP errors are written. Defaults to PHP's `error_log` ini setting. |

3. **Do not** put the admin password in `config.php`. It is set via the setup wizard (§3).

### Filesystem permissions

The web-server user (`www-data`, `nginx`, `apache`, depending on distro) needs:

- **Read** on `config.php` and the application source tree.
- **Read + write + execute** on `db_path`'s directory and on `photo_root` (SQLite needs to create journal sidecar files; photo storage needs writes for new uploads).

On Linux:

```
chown -R www-data:www-data /var/lib/obs-app/
chmod 750 /var/lib/obs-app/db /var/lib/obs-app/photos
```

If you're on SELinux (RHEL/Fedora), you'll also need:

```
semanage fcontext -a -t httpd_sys_rw_content_t "/var/lib/obs-app(/.*)?"
restorecon -Rv /var/lib/obs-app
```

### First run

1. Open the admin URL in a browser: `https://<your-host>/<admin_url_path>/` (e.g. `https://obs.example.com/beheer/`).
2. The app sees the empty `admin` table and renders the **setup wizard** — enter an admin password twice and submit. **Pick a strong password and store it somewhere safe** (a password manager); the only recovery path is the manual SQL one in §13.
3. The wizard redirects to the admin login. Sign in with `admin_name` + the password you just set.
4. After login you land on the admin home.

### Verifying the install

| Check | Expected result |
|---|---|
| `GET /health` | HTTP 200, JSON body `{"status":"ok","version":"…","db":"reachable","ts":"…"}` |
| `db_path` on disk | File exists, owned by the web-server user, schema initialised |
| `photo_root` on disk | Directory exists, empty for now |
| Admin GUI → `"Kassen"` | Empty list (you haven't created any yet) |
| Admin GUI → `"Taxonomie"` | 5 categories pre-seeded with their default tags (TDS-STO-130) |

If `GET /health` returns HTTP 503, the JSON body's error key tells you what went wrong. If the admin URL returns HTTP 404 instead of the setup wizard, double-check `admin_url_path` in `config.php`.

## 3. First-time configuration

### Create your first greenhouse

1. Admin GUI → `"Kassen"` → `"Nieuwe kas"`.
2. **Greenhouse ID** — exactly 4 uppercase hex chars (`^[0-9A-F]{4}$`). The convention is the **last 2 bytes of the paired greenhouse-controller's MAC address in uppercase hex** (e.g. controller MAC `AA:BB:CC:DD:5E:3F` → `5E3F`). If there's no paired controller, pick any unique 4-hex value. The form accepts lowercase and normalises it to uppercase.
3. **Friendly name** — what operators see on the home screen, e.g. `"Kas Willemshoeve"`.
4. Optional location and free-text notes.
5. Save.

The user-side app becomes usable only after at least one greenhouse exists (FR-GH-060).

### Print and post the QR sign

1. Open the greenhouse's detail page in the admin GUI.
2. The **QR code is automatically rendered inline** — no `"Genereer QR"` button (FR-GH-070). The QR encodes `<public_base_url>/<gh-id>/`.
3. Use the browser's **Print** function to print the page; the print stylesheet renders mock M9 (friendly name prominent, scannable QR, fallback short URL, instruction line).
4. Laminate the sheet (A5 is comfortable; A4 if your wall has space) and post it at the kas entrance.

If `public_base_url` was unset when you generated the QR, decoding the printed sign will yield a URL with the request-derived hostname — re-print after setting the config key.

### Review the seeded taxonomy

The 5 launch categories with their default tags from `[OS §3]` are pre-seeded on first run (TDS-STO-130). Display names are Dutch; internal keys stay English.

**Recommendation**: leave the launch taxonomy alone for the first 2-month verification cycle (A7). Mid-cycle taxonomy changes hurt analyst comparability between operators and across time.

## 4. Daily administration

### Login / logout

- **Login** — admin URL → `admin_name` + password. Brute-force resistance: after 5 wrong attempts from one IP the IP is locked out for 60 s, doubling on each further failure up to a 1 h cap (TDS-AUTH-070).
- **Logout** — `"Uitloggen"` link in the admin header.
- The session times out after `admin_session_timeout` seconds of inactivity (FR-ADM-030).

### Change your own password

Admin GUI → `"Wachtwoord wijzigen"` → enter current + new + confirm new. The new password is hashed and stored; you stay logged in (or are forced to re-login depending on the implementation choice, per FR-SEC-030).

### Glance at activity

- Admin home shows recent observations across all greenhouses.
- `"Gebruikers"` lists the operator community with observation counts and last-seen timestamps.
- `"Observaties"` lists all observations with filters.
- `"Audit log"` shows admin destructive actions (Step 2).

## 5. Greenhouses

`"Kassen"` in the admin GUI. CRUD operations on greenhouse records. See §3 for the create flow.

### Editing an existing greenhouse

- You **can** change the greenhouse ID — but then the previously-printed QR sign points to the wrong place. Re-print and replace the wall sign at the same time.
- You **can** change the friendly name freely. Operators see the new name on their home screen on the next page load.

### Deleting a greenhouse — the safeguard

You **cannot delete a greenhouse that has observations** (FR-GH-060). The delete attempt returns a Dutch error citing the observation count. To proceed:

1. Filter `"Observaties"` by that greenhouse.
2. Either delete those observations or re-assign them (admin observation modify supports changing `greenhouse_id`).
3. Once the greenhouse has zero referencing observations, delete works.

This safeguard exists deliberately — accidental cascade deletes are catastrophic for a multi-year observation log.

## 6. Users

`"Gebruikers"` in the admin GUI.

### What you can do

- **List** all users — handle, internal ID, creation date, observation count, last-seen.
- **View** a user — their profile + all their observations.
- **Forget** a user — invalidates their cookie. Next time they open the app they appear as a new user. **The user record and their past observations remain stored.** This is what an operator means when they say "I want to start over on this phone".

### Auto-cleanup

Orphan users (cookie invalidated AND zero remaining observations after the retention sweep) are deleted automatically on the next sweep cycle (FR-USR-060). You don't normally manage user-row deletion yourself.

### Common operator requests and how to handle them

| Request | What to do |
|---|---|
| "I want to change my name" | Tell them to use `"Naam wijzigen"` in Settings. You don't need to act. |
| "I forgot which name I picked" | Check `"Gebruikers"` — their handle is shown. |
| "My partner used to record under my account, I want to separate" | Tell them to use `"Vergeet mij"`; they then register fresh as themselves. The old name's history stays attributed to the old name. |
| "I want my data deleted entirely (GDPR)" | See §10 below — the workflow is: delete observations → forget user → orphan-cleanup completes on next sweep. |

## 7. Observations

`"Observaties"` in the admin GUI.

### List with filters

Filter by greenhouse, user, date range, category, tag. Combining filters narrows further (AND). Page loads ≤ 1 s for up to 1 000 rows (FR-OBS-010).

### View detail

Click any row to see all fields. If an observation has a photo, the page shows the full-resolution image (not the thumbnail) — FR-OBS-020.

### Modify

You can modify **any field** of any observation, **including timestamp, category, tag, severity, note, and photo**. Admin edits override the 24-hour user edit window (FR-OBS-030). Use this for:

- Late corrections requested by the operator past their own 24 h window.
- Wrong category/tag attribution.
- Test or spam observations you want to clean up before exporting.

### Delete

Permanent (no soft-delete in Step 1). The photo files are unlinked synchronously via TDS-STO-120; the row vanishes from user and admin lists; subsequent GET on the URL returns 404.

## 8. Taxonomy

`"Taxonomie"` in the admin GUI. The 5 launch categories with their default tags are pre-seeded (TDS-STO-130).

### Add a category or tag

- For a new category: pick an English `internal_key` (lowercase, underscore-separated, e.g. `pest_monitoring`) + a Dutch display name (`"Plaag-monitoring"`).
- For a new tag: select the parent category, then `internal_key` + display name.

### Rename (display name only)

The `internal_key` is **immutable** — observations reference it stably. Renaming the display name updates how the category/tag appears in the recording flow and on detail views; the analyst's CSV `category_key`/`tag_key` columns are unaffected (FR-TAX-020, FR-TAX-030).

### Archive vs hard-delete

- **Archive** (preferred): the item disappears from the recording flow (mocks M3/M4) but historical observations still render with the original display name. Reversible.
- **Hard-delete** (caveat): the row is removed; affected observations retain their `category_id`/`tag_id` value, which becomes an orphan key. User-facing views fall back to the internal key as the label. The GUI warns you with the affected observation count before the action proceeds (FR-TAX-050).

**Strong recommendation: archive, don't hard-delete.** Once the analyst has built a model on the existing taxonomy, breaking it is expensive.

### Tag limit per category in the recording flow

Mock M4 renders at most 6 tags per category (FR-TAX-060). If you have more than 6 tags under a category, the admin GUI shows a checkbox list capped at 6 ticked — you choose which 6 are active in the operator flow. The others remain valid for historical observations and can be reactivated later.

## 9. CSV export

`"Export"` in the admin GUI.

### Filter, then export

1. Set filters (greenhouse, date range, category, tag, user) — same filter set as `"Observaties"`.
2. Choose dialect:
   - **`"CSV"`** — RFC 4180, the format Python's `csv.reader` parses with no arguments. Use this for analyst tooling.
   - **`"CSV voor Excel"`** — `;`-separated, UTF-8 with BOM, space-separated date-times. Use this when the recipient will double-click the file in Dutch Excel.
3. Click `"Exporteer"`. Download completes within 30 s for ≤ 1 000 observations.

The downloaded filename for the Excel variant carries an `_excel.csv` suffix so both dialects coexist in the same folder without overwriting (TDS-CSV-030).

### Photo archive

If the filtered set has photos, `"Foto's downloaden"` produces a separate ZIP within 2 min for ≤ 100 photos. The ZIP preserves the date-shard layout (`YYYY/MM/...`).

### User self-export

Users can download their own data via their Settings → `"Download mijn gegevens"`. **You do not handle these requests manually.** The user export is always in CSV (RFC 4180) for max portability; it contains the user's observations, account metadata, and their photos.

## 10. Privacy and GDPR

This is a Dutch deployment — GDPR applies.

### What is stored

- **Personal data**: the handle the user typed + a device cookie. That's it.
- **Observations**: timestamp, category, tag, optional note, optional photo.
- **Photos**: EXIF metadata (including GPS) is stripped on upload (TDS-STO-070). Stored outside the web root, served via a controlled endpoint (TDS-STO-040).

### Retention

- Default **365 days** for observations and photos (`retention_days` in config).
- Fixed **90 days** for the admin audit log.
- Set `retention_days = 0` to disable automatic deletion entirely (both observations and audit log).
- The sweep runs at most once per admin login, throttled to once per hour (TDS-STO-090).

### User rights and how to handle them

| Right | How |
|---|---|
| **Right of access** | Self-service: user clicks `"Download mijn gegevens"` in Settings. No admin action needed. |
| **Right to rectification** | User edits within 24 h themselves; you handle older corrections via `"Observaties"` modify. |
| **Right to be forgotten (cookie only)** | Self-service: user clicks `"Vergeet mij"` — cookie invalidated, observations preserved. |
| **Right to be forgotten (full erasure)** | Admin action: filter the user's observations, delete all of them, then `"Vergeet"` the user. The next sweep cleans up the orphan user row. |
| **Privacy notice** | Auto-published at `/privacy`. Update `admin_contact` in `config.php` so the notice carries your real email. |

### Privacy notice page

The notice lives at `/privacy` (linked from the operator home footer and the admin login footer). It covers: what data is collected, the lawful basis, retention, user rights, and your admin contact details (from `admin_contact` in config). Review it after install — it speaks on your behalf.

## 11. Operations: backup, hardening, monitoring

### Backup — your responsibility, not the app's

A1 puts backup on you. Recommended recipe (nightly cron):

```bash
#!/bin/bash
set -euo pipefail
DBFILE=/var/lib/obs-app/db/observations.sqlite
PHOTODIR=/var/lib/obs-app/photos
BACKUPDIR=/var/backups/obs-app
DATE=$(date +%Y%m%d)
mkdir -p "$BACKUPDIR/photos"
sqlite3 "$DBFILE" ".backup $BACKUPDIR/db-$DATE.sqlite"
rsync -a --delete "$PHOTODIR/" "$BACKUPDIR/photos/"
find "$BACKUPDIR" -name 'db-*.sqlite' -mtime +14 -delete
```

Why `sqlite3 .backup` instead of `cp`: it's atomic and safe even while the app is serving traffic. A naked `cp` can capture a half-written transaction.

Restore drill: do this at least once. Stop the web server, copy the most recent DB backup into place, copy the photo directory back, restart. Open the admin URL and verify the data is intact.

### HTTP security headers — your web-server config

The app deliberately does **not** emit security headers itself; you set them in nginx or Apache. Recommended values are in TDS §12.1 — at minimum:

```nginx
add_header Content-Security-Policy "default-src 'self'; img-src 'self' data:; style-src 'self' 'unsafe-inline'; script-src 'self'" always;
add_header X-Content-Type-Options "nosniff" always;
add_header X-Frame-Options "DENY" always;
add_header Referrer-Policy "same-origin" always;
add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;  # HTTPS only
```

### PHP runtime hardening

In `php.ini` or your PHP-FPM pool:

```ini
expose_php = Off
upload_max_filesize = 8M
post_max_size = 12M
session.cookie_httponly = 1
session.cookie_samesite = Lax
default_charset = "UTF-8"
```

The app forces `display_errors = 0` at runtime regardless (TDS-UI-090), so a misconfigured host doesn't leak stack traces to operators.

### Monitoring

- `/health` returns HTTP 200 + `{"status":"ok",…}` on a healthy install, HTTP 503 + `{"status":"degraded",…}` when something is wrong (FR-INST-060). The body shape is documented in the FDS.
- Point UptimeRobot, Healthchecks.io, or a simple cron + curl at it. A check every 5 minutes is plenty.

### Application logs

- PHP errors go to `error_log_path` (config) or PHP's default `error_log`.
- The webserver has its own access + error log — useful for debugging the rare HTTP-layer issues (413, 502 behind proxy, etc.).
- The admin audit log (Step 2) lives inside SQLite — viewable from `"Audit log"` in the admin GUI.

## 12. Updates and migrations

- **Updates**: replace the application source tree with the new release archive. Leave `config.php` in place — it is **not** in the distribution archive (FR-INST-010).
- **Schema changes** ship as numbered SQL files in `migrations/` (e.g. `0001_initial.sql`, `0002_add_audit_log.sql`). On the first request after restart, the app applies any migration with a version higher than the recorded `schema_meta.version` (TDS-STO-080). No manual SQL is needed.
- **Always back up before updating** (§11) and read the release notes for breaking changes.
- The deployed version is shown in the admin GUI footer and in the `/health` JSON.

## 13. Lost-password recovery

There is **no web-based admin password recovery**. The recovery procedure requires shell access to the host:

1. SSH into the host (or open the SQLite file in a local viewer if you can copy it down).
2. `sqlite3 /var/lib/obs-app/db/observations.sqlite`
3. `DELETE FROM admin;`
4. `.quit`
5. Open the admin URL in a browser — the setup wizard re-appears (TDS-AUTH-090).
6. Set a new password.

Operators' data is not affected by this procedure. The audit log records that a password was set, but obviously not who set it — keep the host shell access auditable through other means (SSH log, sudo log).

## 14. Troubleshooting

| Symptom | Likely cause | Fix |
|---|---|---|
| Every request returns the setup-required page | `config.php` missing or `admin_name` empty | Check the file exists, the value is non-empty (FR-INST-020) |
| Every request returns HTTP 500 "unsafe storage location" | `db_path` or `photo_root` inside the document root | Move them outside; the per-request check refuses to operate (FR-INST-040, TDS-STO-050) |
| Boot fails naming a missing config key | `validate_config()` rejected the configuration | Fix the named key per FR-INST-080 / TDS-CFG-080 |
| QR doesn't decode to the right URL behind a reverse proxy | `public_base_url` unset; app derives from `HTTP_HOST` which is internal | Set `public_base_url` to the canonical public URL (TDS-CFG-040, TDS-URL-040) |
| Operators land on "scan een QR-code" instead of the recording flow | The greenhouse-id in the URL doesn't match a greenhouse record, OR no greenhouse exists yet | Check `"Kassen"`; create the missing greenhouse, re-print the sign |
| Operator says "Excel opens the CSV as one column" | They downloaded the `"CSV"` dialect; Dutch Excel needs `;` | Re-export with `"CSV voor Excel"` |
| Operator's photo upload fails with HTTP 413 | Photo larger than `upload_max_filesize` | Ask them to share a lower-resolution photo. If this is common, raise the limit in php.ini and the matching `upload_max_filesize` mirror in TDS-STO-060 |
| HEIC photo upload rejected | ImageMagick not built with `libheif` on this host | Either install `libheif` and rebuild ImageMagick, or instruct iPhone users to share as JPEG (Settings → Camera → Formats → Most Compatible) |
| Admin login fails: "too many attempts" | Rate-limit lockout after 5 wrong attempts | Wait it out (60 s → 120 s → ... up to 1 h), OR `DELETE FROM admin_login_attempts WHERE ip = '<your-ip>'` |
| Admin session keeps expiring | `admin_session_timeout` too low for your work pattern | Raise it in `config.php` and restart |
| The retention sweep doesn't run | Sweep is triggered on admin login (throttled to once per hour). Force it from the admin GUI's `"Run retention now"` button | If still not running, check `retention_days > 0` |
| `/health` returns 503 even though the app seems fine | The DB-reachability probe failed (file moved, permissions changed) | Check `db_path` exists and is writable by the web-server user |
| Operators report a "white page" | PHP fatal error; `display_errors` is off so the screen is blank | Check `error_log_path`; the error and stack trace are there |

---

*Found something this manual doesn't cover, or a step that confused you? Open an issue at `https://github.com/pe1mew/greenhouse-Observation-App/issues` so the next admin has it easier.*
