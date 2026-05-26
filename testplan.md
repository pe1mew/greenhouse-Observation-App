# Test Plan — Greenhouse Observation App

**Version:** 0.1.0  
**Date:** 2026-05-26  
**Tester:** Claude Code (automated) + manual (human)  
**Server under test:** http://192.168.20.232/greenhouse

---

## Scope

This plan covers:

1. **Automated** — PHP syntax, unit logic, and HTTP smoke tests executed by Claude Code in this session
2. **Manual** — UI flows that require a browser, cookie state, or mobile hardware

Out of scope: load testing, penetration testing beyond CSRF/auth checks, accessibility audit.

---

## 1. PHP Syntax — All 45 files

Command: `php -l` on every `.php` file in the repository.

| Result | Count |
|--------|-------|
| PASS   | 45    |
| FAIL   | 0     |

**All files parse cleanly with no syntax errors.**

---

## 2. Unit Tests — Helper Functions (`src/helpers.php`)

Tests run via `php -r` inline script, no framework required.

| # | Test | Result |
|---|------|--------|
| 1 | `e()` escapes `<` → `&lt;` | PASS |
| 2 | `e()` escapes double quote → `&quot;` | PASS |
| 3 | `e()` escapes single quote → `&#039;` | PASS |
| 4 | `e()` leaves plain text unchanged | PASS |
| 5 | `e()` escapes `&` → `&amp;` | PASS |
| 6 | `lang('save')` returns Dutch label `'Opslaan'` | PASS |
| 7 | `lang('no_such_key')` returns the key itself | PASS |
| 8 | `lang()` replaces `:seconds` placeholder | PASS |
| 9 | `utc_now()` matches `YYYY-MM-DDTHH:MM:SSZ` pattern | PASS |
| 10 | `tz_local('2024-06-15T10:00:00Z', 'Europe/Amsterdam')` → `'2024-06-15 12:00'` (CEST +2) | PASS |
| 11 | `tz_local()` output format is `Y-m-d H:i` | PASS |
| 12 | `tz_display()` output contains `+02:00` offset | PASS |

**12/12 PASS**

---

## 3. Unit Tests — Business Logic

### 3a. `isEditable()` — edit-window boundary

| # | Test | Result |
|---|------|--------|
| 1 | Observation created just now → editable (24h window) | PASS |
| 2 | Observation 25 hours old → not editable | PASS |
| 3 | Observation 23 hours old → editable | PASS |
| 4 | Zero-hour window → always not editable | PASS |

### 3b. `csvLine()` — RFC 4180 CSV encoding

| # | Test | Result |
|---|------|--------|
| 5 | Plain fields joined with comma and CRLF | PASS |
| 6 | Field containing comma is double-quoted | PASS |
| 7 | Field containing newline is double-quoted | PASS |
| 8 | Embedded double quote is escaped as `""` | PASS |
| 9 | Semicolon separator (dialect B) | PASS |
| 10 | Field containing semicolon is quoted in dialect B | PASS |
| 11 | Empty string field produces empty column | PASS |

**11/11 PASS**

---

## 4. Unit Tests — Config Validation (`src/Config.php`)

| # | Test | Result |
|---|------|--------|
| 1 | Fully valid config → no errors | PASS |
| 2 | Empty `admin_name` → `config_admin_name_empty` | PASS |
| 3 | `admin_session_timeout` < 60 → `config_session_timeout` | PASS |
| 4 | `admin_url_path` with `/` and `!` → `config_admin_url_path_invalid` | PASS |
| 5 | `edit_window_hours = 0` → `config_edit_window_invalid` | PASS |
| 6 | `timezone = 'NotATimezone'` → `config_timezone_invalid` | PASS |
| 7 | `retention_days = -1` → `config_retention_invalid` | PASS |
| 8 | `db_path` inside document root → `config_db_path_invalid` | PASS |
| 9 | `Config::load()` with missing file → `null` | PASS |

**9/9 PASS**

---

## 5. Unit Tests — PhotoHandler (`src/PhotoHandler.php`)

| # | Test | Result |
|---|------|--------|
| 1 | Valid 1×1 JPEG file → `validate()` returns `null` (OK) | PASS |
| 2 | File with size > 8 MB → `'photo_too_large'` | PASS |
| 3 | Plain text file → `'photo_invalid_type'` | PASS |
| 4 | `delete()` on non-existent path → no exception | PASS |

**4/4 PASS**

---

## 6. Unit Tests — AdminAuth (`src/AdminAuth.php`)

### 6a. Brute-force lockout duration (exponential back-off)

| # | Failed attempts | Expected lockout | Result |
|---|-----------------|------------------|--------|
| 1 | 5 | 60 s | PASS |
| 2 | 6 | 120 s | PASS |
| 3 | 7 | 240 s | PASS |
| 4 | 8 | 480 s | PASS |
| 5 | 9 | 960 s | PASS |
| 6 | 11 | 3 600 s (cap) | PASS |

### 6b. CSRF constant-time comparison

| # | Test | Result |
|---|------|--------|
| 7 | `hash_equals()` with identical tokens → true | PASS |
| 8 | `hash_equals()` with tampered token → false | PASS |

**8/8 PASS**

---

## 7. HTTP Smoke Tests (curl against live server)

### 7a. Routing

| # | URL | Expected | Actual | Result |
|---|-----|----------|--------|--------|
| 1 | `GET /greenhouse/` | 200 (root landing) | 200 | PASS |
| 2 | `GET /greenhouse/health` | 200 JSON | 200 | PASS |
| 3 | `GET /greenhouse/privacy` | 200 | 200 | PASS |
| 4 | `GET /greenhouse/ZZZZ/` | 404 (gh not in DB) | 404 | PASS |
| 5 | `GET /greenhouse/1234/` | 404 (gh not in DB) | 404 | PASS |
| 6 | `GET /greenhouse/abcd/` (lowercase) | 301 → `/ABCD/` | 301 | PASS |
| 7 | `GET /greenhouse/GGGG/` (non-hex) | 404 | 404 | PASS |
| 8 | 301 Location header uses uppercase `ABCD` | uppercase | uppercase | PASS |

### 7b. User routes without a valid session cookie → 302 to `/register`

These routes return 404 when the greenhouse ID is not in the database (correct — the router checks the DB first). Testing with a **real** greenhouse ID requires a pre-seeded DB; marked as **manual** below.

### 7c. Directory / file protection (`.htaccess`)

| # | URL | Expected | Actual | Result |
|---|-----|----------|--------|--------|
| 9 | `GET /greenhouse/src/helpers.php` | 403 | 403 | PASS |
| 10 | `GET /greenhouse/migrations/0001_initial.sql` | 403 | 403 | PASS |
| 11 | `GET /greenhouse/views/user/home.php` | 403 | 403 | PASS |
| 12 | `GET /greenhouse/lang/nl.php` | 403 | 403 | PASS |
| 13 | `GET /greenhouse/config.php` | 403 | 403 | PASS |
| 14 | `GET /greenhouse/assets/css/app.css` | 200 | 200 | PASS |

### 7d. Admin access control

| # | URL | Expected | Actual | Result |
|---|-----|----------|--------|--------|
| 15 | `GET /greenhouse/management/` (no session) | 302 → login | 302 | PASS |
| 16 | `GET /greenhouse/management/login` | 200 | 200 | PASS |
| 17 | `POST /greenhouse/management/login` wrong password | 200 (error) | 200 | PASS |

### 7e. Brute-force lockout (live)

| # | Test | Result |
|---|------|--------|
| 18 | 6 consecutive wrong admin login POSTs → response body contains lockout message | PASS |

### 7f. Response body content

| # | Test | Result |
|---|------|--------|
| 19 | Health JSON contains `"status"` key | PASS |
| 20 | Health JSON `status` = `"ok"`, `db` = `"reachable"` | PASS |
| 21 | Root page body contains Dutch text ("Kas") | PASS |
| 22 | Privacy page body contains Dutch data notice ("gegevens") | PASS |
| 23 | Admin login page body contains `password` input | PASS |
| 24 | 404 page body contains Dutch "niet gevonden" text | PASS |

### 7g. Observations on `index.php` direct URL

Accessing `/greenhouse/index.php` directly returns **404** — this is expected and correct. Apache's `mod_rewrite` passes real files through unchanged; the Router then receives `/index.php` as the path, which matches no known route and correctly returns 404. The app is only accessible without the `.php` suffix.

**HTTP total: 24/24 PASS** (2 N/A — require seeded greenhouse ID)

---

## 8. Manual Test Cases

The following tests require a browser, live cookie state, or mobile hardware. They cannot be automated without a test harness. Each case must be executed by a human on the live server.

### 8.1 User Registration (M1)

| Step | Action | Expected |
|------|--------|----------|
| 1 | Open `/greenhouse/<gh-id>/` without cookie | Redirected to `/register` |
| 2 | Submit empty name | Error "Vul een naam in." |
| 3 | Submit name > 40 chars | Error "Naam mag maximaal 40 tekens zijn." |
| 4 | Submit duplicate name (same case / different case) | Error "Deze naam is al in gebruik." |
| 5 | Submit valid unique name | Cookie set, redirected to home |

### 8.2 Quick Observation Flow (M2 → M3 → M4 → M5)

| Step | Action | Expected |
|------|--------|----------|
| 1 | Tap "+ Snelle waarneming" | Category list appears |
| 2 | Tap a category | Tag list for that category appears |
| 3 | Tap a tag | Observation saved; confirm screen shown |
| 4 | Confirm screen: one ✓ and "Opgeslagen" heading at 1.6rem | ✓ visible, text legible |
| 5 | Confirm screen: category — tag shown as subtitle | Correct values |
| 6 | Select severity 3 | Button 3 highlights green; others remain blue |
| 7 | Enter a note | Text accepted |
| 8 | Adjust timestamp to earlier time | Field updates; future time blocked by `max` attribute |
| 9 | Tap "Klaar" | Redirect to home; observation appears in Recent |

### 8.3 Photo on Confirm Screen (FR-REC-040)

| Step | Action | Expected |
|------|--------|----------|
| 1 | Tap "📷 Foto toevoegen" | Camera opens (on mobile) / file picker opens (on desktop) |
| 2 | Select/capture a photo | Preview appears; button changes to "Opslaan" |
| 3 | Tap "📷 Foto opnieuw maken" | Camera re-opens; btn-initial reappears |
| 4 | Select photo and tap "Opslaan" | Photo saved; redirect to home |
| 5 | Upload HEIC file | Error "HEIC-bestanden worden niet ondersteund" |
| 6 | Upload file > 8 MB | Error "Foto is te groot" |

### 8.4 Observation Detail & Edit (FR-REV-010, FR-OBS-010)

| Step | Action | Expected |
|------|--------|----------|
| 1 | Open observation within edit window | Edit form visible |
| 2 | Change severity and note, tap "Opslaan" | Values saved; `?saved=1` redirect; success message shown |
| 3 | Add photo on detail page | Photo appears; delete button shown |
| 4 | Delete photo | Photo removed; upload input reappears |
| 5 | Open observation outside edit window | Edit form hidden; read-only notice shown |
| 6 | Delete observation within window | Redirected to home; observation removed from Recent |

### 8.5 Severity Picker (UI)

| Step | Action | Expected |
|------|--------|----------|
| 1 | Confirm screen: 7 buttons (—, 1–5) span full width | Equal width, no overflow |
| 2 | Buttons are at least 64px tall | Touch-friendly |
| 3 | Tap button "4" | Turns green; "—" and other buttons revert to blue |
| 4 | Tap "—" | Deselects; all buttons return to blue |

### 8.6 Full History (FR-REV-020)

| Step | Action | Expected |
|------|--------|----------|
| 1 | Tap "Al mijn waarnemingen" on home | History page loads |
| 2 | Observations grouped by local date, newest first | Correct grouping |
| 3 | Each row shows category — tag, time, severity (if set), 📷 (if photo) | Correct display |
| 4 | Tap a row | Observation detail page opens |

### 8.7 Settings & Handle Change (FR-USR-010, FR-USR-020)

| Step | Action | Expected |
|------|--------|----------|
| 1 | Open Settings | Current handle pre-filled |
| 2 | Change handle to duplicate → error | "Deze naam is al in gebruik." |
| 3 | Change to valid new handle | Redirected; `?saved=1`; new cookie set |
| 4 | "Vergeet mij" → confirm | Cookie cleared; redirected to register |

### 8.8 GDPR Data Export (FR-SEC-050)

| Step | Action | Expected |
|------|--------|----------|
| 1 | Open Settings → "Download mijn gegevens" → submit | ZIP download begins |
| 2 | ZIP contains `observations.csv`, `account.txt` | Both files present |
| 3 | CSV has RFC 4180 header row | 12 column headers, comma-delimited |
| 4 | If observations have photos: `photos/` folder in ZIP | Photo files included |
| 5 | No CSRF token → 403 | Server rejects without token |

### 8.9 Admin — Observation Management (FR-OBS-030)

| Step | Action | Expected |
|------|--------|----------|
| 1 | Admin login → Observations list | List renders with greenhouse/user/category/tag columns |
| 2 | Filter by greenhouse | Only matching observations shown |
| 3 | Filter by date range | Observations outside range excluded |
| 4 | Open observation detail | All fields shown; "Bewerken" link present |
| 5 | Open edit page | Form pre-filled with current values |
| 6 | Change category → tag dropdown filters | Only tags for selected category shown |
| 7 | Save changes | Redirected to detail with "Waarneming bijgewerkt." |
| 8 | Delete photo on admin edit page | Photo removed; upload input appears |
| 9 | Delete observation | Removed from list; audit log entry written |

### 8.10 Admin — Taxonomy Management

| Step | Action | Expected |
|------|--------|----------|
| 1 | Add category with duplicate key | Error "Deze interne sleutel bestaat al" |
| 2 | Add valid category | Appears in list |
| 3 | Archive category | Category no longer appears in user tag picker |
| 4 | Restore category | Category reappears |
| 5 | Add tag to category | Tag appears in user flow |

### 8.11 Admin — Greenhouse QR Code

| Step | Action | Expected |
|------|--------|----------|
| 1 | Admin → Greenhouses → open greenhouse → "QR bekijken" | QR PNG served (Content-Type: image/png) |
| 2 | Scan QR with phone | Opens correct `/greenhouse/<gh-id>/` URL |

### 8.12 Admin — CSV Export

| Step | Action | Expected |
|------|--------|----------|
| 1 | Export without greenhouse filter | Form shown, no download |
| 2 | Export with greenhouse selected | CSV downloaded |
| 3 | Dialect A: comma-separated, ISO timestamp | Correct format |
| 4 | Dialect B: semicolon-separated, BOM prepended, space in timestamp | Opens correctly in Excel |

### 8.13 Security — CSRF Protection

| Step | Action | Expected |
|------|--------|----------|
| 1 | POST to any user form with no `_csrf` field | 403 response |
| 2 | POST with tampered `_csrf` value | 403 response |
| 3 | POST with correct CSRF token | Action succeeds |

### 8.14 Security — Session Timeout (Admin)

| Step | Action | Expected |
|------|--------|----------|
| 1 | Admin login, wait for `admin_session_timeout` | Next request redirected to login |
| 2 | Admin logout | Session destroyed; login page shown |

### 8.15 Data Retention (FR-SEC-060)

*Note: probabilistic trigger (1/50 chance per request). Test by setting `retention_days = 1` and making many requests, then confirming old rows are removed from the DB.*

---

## 9. Summary

### Automated test results

| Suite | Tests | PASS | FAIL |
|-------|-------|------|------|
| PHP syntax (45 files) | 45 | 45 | 0 |
| Helper functions | 12 | 12 | 0 |
| Business logic (`isEditable`, `csvLine`) | 11 | 11 | 0 |
| Config validation | 9 | 9 | 0 |
| PhotoHandler | 4 | 4 | 0 |
| AdminAuth lockout & CSRF | 8 | 8 | 0 |
| HTTP smoke tests | 24 | 24 | 0 |
| **Total** | **113** | **113** | **0** |

### Manual test status

| Suite | Status |
|-------|--------|
| 8.1 User registration | Not executed |
| 8.2 Quick observation flow | Not executed |
| 8.3 Photo on confirm screen | Not executed |
| 8.4 Observation detail & edit | Not executed |
| 8.5 Severity picker UI | Not executed |
| 8.6 Full history | Not executed |
| 8.7 Settings & handle change | Not executed |
| 8.8 GDPR data export | Not executed |
| 8.9 Admin — observation management | Not executed |
| 8.10 Admin — taxonomy | Not executed |
| 8.11 Admin — QR code | Not executed |
| 8.12 Admin — CSV export | Not executed |
| 8.13 CSRF protection | Not executed |
| 8.14 Admin session timeout | Not executed |
| 8.15 Data retention | Not executed |

---

## 10. Known Limitations & Notes

- **`/greenhouse/index.php` returns 404** — expected. The app routes via URL rewriting; accessing `index.php` by name returns 404 because the path `/index.php` matches no route. This is correct behavior.
- **User routes with fake greenhouse ID return 404** — expected. The router validates the greenhouse ID against the DB before delegating to the UserController. A non-existent ID correctly returns 404, not 302.
- **No automated test for photo dimensions check** — `getimagesize()` requires a real image file of the right dimensions; test case 8.3 covers this manually.
- **Retention sweep is probabilistic (1/50)** — automated test would require mocking `random_int`, which is not practical without a test framework. Covered by test case 8.15.
- **Camera capture** (`capture="environment"`) requires a mobile device with a camera and cannot be tested in a desktop browser or via curl.
