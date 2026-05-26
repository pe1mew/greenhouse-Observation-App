# Test Plan — Greenhouse Observation App

| Field | Value |
|---|---|
| Document | Test Plan |
| Project | Greenhouse Observation App |
| Status | Draft — 2026-05-24 |
| Inputs | `functionalRequirements.md` (FDS), `technicalDesignSpecification.md` (TDS), `implementationPlan.md` (companion build plan) |
| Scope | Comprehensive verification of every FR and TDS item against a deployed instance of the web app, using **Hardware-in-the-Loop (HIL) probes** — external HTTP requests and SSH-driven inspection of the server's filesystem, database, and logs. |

---

## 1. Purpose

Define the verification work that gates each implementation-plan iteration and the final acceptance of each environment (Dev → Acceptance → Production).

**Every FR and every TDS item appears in at least one test case** — see the coverage matrix in §10. No requirement ships unverified.

## 2. References

| Ref | Document |
|---|---|
| [FDS] | `functionalRequirements.md` — source of FR IDs and their own SMART acceptance criteria |
| [TDS] | `technicalDesignSpecification.md` — source of TDS IDs and their own ACs (many inherit FR ACs) |
| [IP]  | `implementationPlan.md` — maps each test-plan section to an implementation iteration |

The FDS / TDS ACs are the authoritative pass/fail conditions. This plan operationalises them into executable probe sequences.

## 3. HIL approach

"HIL" here means **test the running system from outside**, not run PHPUnit inside it. Probes run from Claude's host (or any operator's laptop) against the deployed app:

| Probe channel | Purpose | Example |
|---|---|---|
| **HTTP via `curl`** | Exercises every user-visible behaviour; inspects status codes, headers, body | `curl -is http://192.168.20.232/webapp/` |
| **SSH + `sqlite3`** | Inspects database state, runs schema introspection, verifies row counts and column values | `ssh deploy@dev "sqlite3 /var/www/webapp_data/observations.sqlite '...'"` |
| **SSH + `ls` / `stat` / `find`** | Inspects filesystem layout, file presence/absence, permissions | `ssh deploy@dev "ls -la /var/www/webapp_data/photos/2026/05/"` |
| **SSH + `tail` / `grep` of logs** | Inspects PHP error log, Apache access/error log, app log file | `ssh deploy@dev "tail -200 /var/log/apache2/error.log"` |
| **SSH + `exiftool` / `file` / `identify`** | Inspects uploaded photos for EXIF, dimensions, format | `ssh deploy@dev "exiftool /var/www/webapp_data/photos/2026/05/X.jpg"` |
| **SSH + `ab` (Apache Bench)** | Light concurrency probe — verifies WAL + busy_timeout under load | `ssh deploy@dev "ab -n 100 -c 10 http://localhost/webapp/"` |
| **SSH + `php -r`** | Runs a one-liner against the codebase (e.g. CSRF-token generation entropy check) | `ssh deploy@dev "cd webapp && php -r 'require ... ; echo ...;'"` |

No probe modifies the application code; only application **state** (config, DB, photo files) is mutated during setup / teardown.

## 4. Test environments

Mirror of `[IP §3]`:

| # | Name | Base URL | Used for |
|---|---|---|---|
| 1 | **Dev** | `http://192.168.20.232/webapp/` | Phase-1 iteration gating; every TC runs here first |
| 2 | **Acceptance** | `https://pe1mew.nl/webapp/` (TBD) | TC subsets re-run after deploy; the operator-UAT smoke set (`§9.1`) is the gate |
| 3 | **Production** | `https://rfsee.net/webapp/` (TBD) | Smoke-only (`§9.1`); no destructive probes against live data |

Set the base URL via the `$BASE` environment variable in probe commands. Pass/fail criteria are environment-independent.

## 5. Test fixtures

Reusable seed data and config used across many TCs:

| Fixture | Definition | Used by |
|---|---|---|
| **`F-CONFIG-VALID`** | A `config.php` with every required key set to a working value, `timezone='Europe/Amsterdam'`, `retention_days=365`, `public_base_url='http://192.168.20.232/webapp'` | All probe sequences post-iteration-1 |
| **`F-CONFIG-EMPTY-ADMIN`** | As above but `admin_name=''` | INST-cfg cases |
| **`F-CONFIG-BAD-TZ`** | As above but `timezone='Europe/Berlin42'` | INST-validation cases |
| **`F-CONFIG-PHOTO-INSIDE-ROOT`** | As above but `photo_root='/var/www/html/webapp/photos'` (inside doc root) | INST-safety cases |
| **`F-GH-1`** | One greenhouse: id `5E3F`, name `Kas Willemshoeve` | All non-INST cases |
| **`F-GH-2`** | Two greenhouses: `5E3F` (Willemshoeve) + `A2B1` (De Linden) | Multi-greenhouse cases |
| **`F-USR-MARJA`** | One user, handle `Marja`, cookie issued, no observations | IDU + REC cases |
| **`F-OBS-RECENT`** | 5 observations across `F-USR-MARJA` and a second user, `ts` spread over the last 24 h | REV cases |
| **`F-OBS-RETENTION`** | 10 observations: 5 with `ts < now() − 400 days`, 5 with `ts < now() − 30 days` | SEC-retention cases |
| **`F-ADMIN-SET`** | Admin password set via setup wizard to `test-admin-pwd-do-not-reuse` | All admin cases |

Setup helpers (run via SSH on the server):

```bash
# Reset to clean state — used at the start of every iteration's test run
reset() {
  ssh deploy@dev "rm -rf /var/www/webapp_data/* && rm -f webapp/config.php"
}

# Apply a config fixture
apply_config() {
  scp fixtures/$1.php deploy@dev:webapp/config.php
}

# Seed data
seed_greenhouses() {
  ssh deploy@dev "sqlite3 /var/www/webapp_data/observations.sqlite < fixtures/$1.sql"
}
```

## 6. Test case template

Every TC has the same shape:

```
### TC-<area>-<short-name> — <one-line summary>
- **Verifies**: FR-X-NNN [, …]; TDS-Y-NNN [, …]
- **Setup**: <fixture(s) or precondition>
- **Probe**: <one or more commands, in order>
- **Expected**: <objective pass condition — usually a status code, a substring, a row count, a header value, a file existence>
- **Teardown** (if not implied by setup of the next TC): <state restoration>
```

The probe + expected are written to be unambiguously executable: a tester (human or Claude) types the command and reads off the result.

## 7. Test phases

Tests run in three waves per environment:

| Wave | What | When |
|---|---|---|
| **Smoke** | `§9.1` — the 5 critical end-to-end scenarios | Every deploy to every environment, immediately after rollout |
| **Per-iteration** | `§8.<area>` cases for the FRs / TDS items delivered in the current iteration | After each Phase-1 iteration (Dev only) |
| **Full regression** | All of `§8` + all of `§9` | Before Phase-1 → Phase-2a promotion (Dev); before 2a → 2b promotion (Acceptance) |

## 8. Test cases by area

Each subsection covers one FR area. TCs are grouped where one probe sequence efficiently verifies multiple FRs / TDS items.

### 8.1 Installation and configuration (`TC-INST-*`)

#### TC-INST-shipping — Distribution ships template, not config
- **Verifies**: FR-INST-010; TDS-CFG-010, CFG-020, CFG-060
- **Setup**: Fresh extraction of the release archive into a scratch directory.
- **Probe**: `ls archive/webapp/template_config.php archive/webapp/config.php 2>&1`
- **Expected**: `template_config.php` exists; `config.php` produces "No such file". The template file starts with `<?php return [`.

#### TC-INST-setup-page — Empty config triggers setup page on every path
- **Verifies**: FR-INST-020; TDS-CFG-030
- **Setup**: `F-CONFIG-EMPTY-ADMIN`.
- **Probe**: For each path in `/, /5E3F/, /5E3F/observation/new, /management/, /health, /photo/1`: `curl -is -o /dev/null -w "%{http_code} %{size_download}\n" $BASE<path>`
- **Expected**: All 6 probes return HTTP 503 within 1 s; response body contains `setup` (Dutch text); no other endpoint serves application content.

#### TC-INST-validation — Boot-time config validation
- **Verifies**: FR-INST-030, FR-INST-080; TDS-CFG-040, CFG-050, CFG-070, CFG-080
- **Setup**: Cycle through `F-CONFIG-BAD-TZ`, `F-CONFIG-PHOTO-INSIDE-ROOT`, and a hand-crafted config with `retention_days=-1`.
- **Probe**: For each: `apply_config $name; curl -is $BASE/` and grep the response body.
- **Expected**: Each invalid value produces an HTTP 503 response naming the failing key in Dutch. With `F-CONFIG-VALID`, `curl -is $BASE/` returns ≠ 503 (300- or 200-range). The admin GUI rendered HTML contains zero form fields whose `name` references the edit window.

#### TC-INST-storage-safety — DB and photo paths must be outside doc root
- **Verifies**: FR-INST-040; TDS-STO-010, STO-050
- **Setup**: `F-CONFIG-PHOTO-INSIDE-ROOT` (a variant for `db_path` as well).
- **Probe**: `curl -is $BASE/` and check for HTTP 500 within 1 s. Then `ssh deploy@dev "grep 'unsafe storage' /var/log/apache2/error.log | tail -5"`.
- **Expected**: HTTP 500 returned within 1 s; "unsafe storage location" message in body; no SQL queries in the app log.

#### TC-INST-init-and-seed — Schema auto-init + taxonomy seed are idempotent
- **Verifies**: FR-INST-050, FR-INST-070; TDS-STO-020, STO-080, STO-130
- **Setup**: `reset; apply_config F-CONFIG-VALID`.
- **Probe**: `curl -is $BASE/health` (first request, completes within 5 s). Then `ssh deploy@dev "sqlite3 .../observations.sqlite 'SELECT COUNT(*) FROM category; SELECT COUNT(*) FROM tag; SELECT version FROM schema_meta WHERE id=1;'"`. Then `curl -is $BASE/health` (second request, completes within 1 s). Then run the SQL counts again.
- **Expected**: Category count = 5; tag count = launch-taxonomy total per [OS §3]; `schema_meta.version` = 1; second probe's counts and version unchanged; second request faster than first.

#### TC-INST-health — Health endpoint returns degraded state on failure
- **Verifies**: FR-INST-060
- **Setup**: `F-CONFIG-VALID`. Then break the DB by `ssh deploy@dev "chmod 000 .../observations.sqlite"`.
- **Probe**: `curl -is $BASE/health` (with valid state, then with broken DB).
- **Expected**: Valid → HTTP 200 + JSON `{"status":"ok","version":"…","db":"reachable",…}`. Broken → HTTP 503 + JSON `{"status":"degraded","…":"…"}` with **no filesystem paths or stack traces** in the body. **Teardown**: restore DB permissions.

### 8.2 User identification (`TC-IDU-*`)

#### TC-IDU-first-visit — Splash → registration → cookie issued
- **Verifies**: FR-IDU-010, IDU-020, IDU-030, IDU-040; TDS-AUTH-010, AUTH-020, AUTH-030
- **Setup**: `F-CONFIG-VALID + F-GH-1`. Discard any prior cookie.
- **Probe**:
  1. `curl -is $BASE/5E3F/` (no cookie) → expect M1 splash within 1 s.
  2. `curl -is -c cookies.txt -d "handle=Marja" $BASE/5E3F/register` → expect 303 to `$BASE/5E3F/`; expect `Set-Cookie` with HttpOnly, SameSite=Lax, Max-Age ≥ 315360000 (Secure when HTTPS).
  3. `curl -is -b cookies.txt $BASE/5E3F/` → expect M2 home with `Marja` rendered in the greeting.
  4. Verify cookie value is ≥ 32 hex chars and contains neither the user's DB id nor "Marja" as a substring.
  5. `ssh deploy@dev "sqlite3 .../observations.sqlite 'SELECT id, handle, length(current_cookie_token) FROM user;'"` → expect 1 row, handle=Marja, token length ≥ 32.

#### TC-IDU-handle-uniqueness — Case-insensitive uniqueness + length
- **Verifies**: FR-IDU-010 (uniqueness branch); TDS-AUTH-110
- **Setup**: `F-USR-MARJA` already registered.
- **Probe**: With fresh cookie jar: `curl -is -d "handle=MARJA" $BASE/5E3F/register`; then with handle of 50 chars; then with empty handle.
- **Expected**: All three rejected; response re-renders M1 with Dutch error; row count of `user` unchanged.

#### TC-IDU-change-handle — Handle change preserves user id
- **Verifies**: FR-IDU-050; TDS-AUTH-110 (rotation)
- **Setup**: `F-USR-MARJA` with at least one observation attributed.
- **Probe**: `curl -b cookies.txt -d "handle=Marja-new" $BASE/5E3F/settings/handle` → expect 303 to settings. SQL check: `SELECT id, handle FROM user`; check observation join shows the new display name.
- **Expected**: User row's `id` unchanged before/after; `handle` updated; observation detail view now shows `Marja-new`.

#### TC-IDU-forget — Self-forget invalidates cookie, preserves data
- **Verifies**: FR-IDU-060; TDS-AUTH-040 (path used for user-side)
- **Setup**: `F-USR-MARJA` with observations.
- **Probe**: Pre-count: `SELECT COUNT(*) FROM user; SELECT COUNT(*) FROM observation`. Then `curl -b cookies.txt -d "" $BASE/5E3F/forget`. Then `curl -is -b cookies.txt $BASE/5E3F/` (with old cookie). Post-count.
- **Expected**: After forget, GET with old cookie returns M1 splash (cookie no longer authenticates); user count and observation count unchanged.

#### TC-IDU-no-admin-link — User GUI contains zero admin-URL occurrences
- **Verifies**: FR-IDU-070
- **Setup**: `F-USR-MARJA`. Default config uses `admin_url_path='management'`.
- **Probe**: For each user-side URL (`/, /5E3F/, /5E3F/observation/new, /5E3F/observation/<id>, /5E3F/settings, /privacy, /health`): `curl -s -b cookies.txt $BASE<path> | grep -c "management"`.
- **Expected**: Every probe returns count = 0.

### 8.3 Recording (`TC-REC-*`)

#### TC-REC-two-tap — End-to-end two-tap commit
- **Verifies**: FR-REC-010 (core), FR-REC-020, FR-REC-030, FR-REC-090; FR-UI-040, UI-050; TDS-UI-100 (PRG)
- **Setup**: `F-USR-MARJA + F-GH-1`. Empty observation table.
- **Probe**: 
  1. `curl -s -b cookies.txt $BASE/5E3F/observation/new` → assert mock-M3 HTML with 5 category rows.
  2. `curl -s -b cookies.txt $BASE/5E3F/observation/new?cat=wellbeing` → assert mock-M4 HTML with ≤ 6 tag rows; record opening time.
  3. `curl -is -b cookies.txt -d "csrf=<token>&tag=all_good" $BASE/5E3F/observation/commit` → expect 303 to M5.
  4. `sqlite3 ... 'SELECT id, greenhouse_id, user_id, category_id, tag_id, ts FROM observation;'` → expect exactly one row with the correct foreign keys.
- **Expected**: All HTTP responses within their FR-mandated budgets; one new row in `observation` with `greenhouse_id` matching `5E3F`; `ts` within 5 s of probe time; refresh of M5 page does not duplicate the row.

#### TC-REC-greenhouse-context — Observations attach to the active greenhouse
- **Verifies**: FR-REC-090, FR-GH-030, FR-GH-040
- **Setup**: `F-USR-MARJA + F-GH-2`.
- **Probe**: Register handle by visiting `/5E3F/` first. Then commit one observation. Then visit `/A2B1/`. Commit another observation.
- **Expected**: SQL shows two observations: one with `greenhouse_id` for 5E3F, one for A2B1. The user's `current_greenhouse_id` is the most recently visited (A2B1).

#### TC-REC-photo — Photo upload, EXIF strip, thumbnail, controlled serve (Step 2)
- **Verifies**: FR-REC-010 (photo), FR-REC-070, FR-REC-100; TDS-STO-030, STO-040, STO-060, STO-070, STO-120, STO-140, UI-050
- **Setup**: `F-USR-MARJA`. Test fixture: a portrait JPEG with EXIF orientation 6 and GPS tag.
- **Probe**:
  1. Multipart POST upload via `curl -F "photo=@portrait.jpg"` against the photo endpoint.
  2. `ssh deploy@dev "find /var/www/webapp_data/photos -type f"` → expect 2 files (full + thumb) under `<YYYY>/<MM>/`.
  3. `ssh deploy@dev "exiftool .../X.jpg"` → expect zero tags.
  4. `ssh deploy@dev "identify .../X.jpg"` → expect orientation upright; long edge ≤ 2048 px.
  5. `ssh deploy@dev "identify .../X_thumb.jpg"` → long edge ≤ 400 px.
  6. `curl -is $BASE/photo/<obs-id>` (no cookie) → expect 403 within 1 s.
  7. `curl -is -b cookies.txt $BASE/photo/<obs-id>` → expect 200 with `Cache-Control: private, max-age=86400, immutable` and non-empty `ETag`.
  8. Replay with `If-None-Match: <etag>` → expect 304.
  9. Delete the owning observation. `find` → expect both files gone.
- **Expected**: Each step passes; refusing to leak EXIF/GPS; cleanup is synchronous.

#### TC-REC-limits — Upload limit enforcement
- **Verifies**: FR-REC-070 (limits side); TDS-STO-060
- **Setup**: `F-USR-MARJA`. Fixtures: 9 MB JPEG, 4 MB GIF (renamed `.jpg`), 4 MB real `.gif`, 9000×9000 JPEG.
- **Probe**: Upload each in turn.
- **Expected**: 9 MB → HTTP 413; renamed-GIF → HTTP 415 (server inspects bytes not extension); real GIF → HTTP 415; 9000² → rejected. No partial files left in `photos/`.

#### TC-REC-severity-note — Optional fields
- **Verifies**: FR-REC-010 (sev/note), FR-REC-040, FR-REC-060
- **Setup**: `F-USR-MARJA`.
- **Probe**: Commit observation with `severity=3` and note "test"; with `severity=` (empty); with `severity=99`.
- **Expected**: Stored `severity` = 3 (int), then SQL NULL (not 0), then HTTP 422 (validation error). CSV export shows `severity` column empty for the null-severity row.

#### TC-REC-timestamp — Default + adjustment
- **Verifies**: FR-REC-050; TDS-CFG-070
- **Setup**: `F-USR-MARJA`. Set server TZ to `Europe/Amsterdam` (per `F-CONFIG-VALID`).
- **Probe**: Commit observation with default ts. Commit another with explicit ts `now() − 30 min`.
- **Expected**: First row's `ts` (UTC stored, displayed as `+02:00` in summer) matches server clock ± 5 s. Second row's `ts` equals the supplied moment, correctly converted to UTC at write and back to `Europe/Amsterdam` at render.

#### TC-REC-offline — Service Worker offline queue (Step 2)
- **Verifies**: FR-REC-080; TDS-UI-070, UI-140
- **Setup**: `F-USR-MARJA`. Use Chrome DevTools (or `curl + sw-test` harness) to simulate offline.
- **Probe**: Simulate offline. POST observation commit. Inspect indicator. Go online. Wait ≤ 30 s.
- **Expected**: "Offline — queued" indicator appears within 1 s. After reconnect, the observation appears in DB; indicator shows "synced". Service Worker scope is `/`; replayed POST hits the correct `/5E3F/...` endpoint.

### 8.4 Review (`TC-REV-*`)

#### TC-REV-recent — Recent panel windowing + empty state
- **Verifies**: FR-REV-010
- **Setup**: `F-USR-MARJA + F-OBS-RECENT` plus a 6th observation dated `now() − 25 h`.
- **Probe**: `curl -s -b cookies.txt $BASE/5E3F/ | grep -E "obs-row|placeholder"`.
- **Expected**: 5 observation rows rendered (the in-window ones), ordered DESC by ts; the > 24 h observation absent. With an empty observation table, the Dutch placeholder text "Nog geen waarnemingen…" appears and zero observation rows are rendered.

#### TC-REV-history — See-all full history (Step 2)
- **Verifies**: FR-REV-020
- **Setup**: `F-USR-MARJA` with 10 observations spanning 3 days, 2 of them with photos.
- **Probe**: `curl -s -b cookies.txt $BASE/5E3F/observations | grep -cE "day-header|photo-icon"`.
- **Expected**: 3 day headers; 2 photo icons; rows ordered DESC within each day group.

#### TC-REV-detail — Detail view + edit window + read-only after
- **Verifies**: FR-REV-030, REV-040, REV-050, REV-060
- **Setup**: `F-USR-MARJA` with an observation dated `now() − 12 h` (in edit window) and another dated `now() − 30 h` (past edit window). One observation belongs to user B.
- **Probe**:
  1. `curl -s -b cookies.txt $BASE/5E3F/observation/<recent-id>` → assert Edit + Delete buttons present.
  2. Same against the older observation → assert read-only footer text, Edit / Delete absent.
  3. `curl -is -b cookies.txt $BASE/5E3F/observation/<user-B-id>` → expect 403 within 1 s.

### 8.5 Greenhouse context and administration (`TC-GH-*`)

#### TC-GH-admin-crud — Greenhouse CRUD + format validation
- **Verifies**: FR-GH-010, FR-GH-060; TDS-URL-050
- **Setup**: `F-ADMIN-SET`, logged in. Empty greenhouse table.
- **Probe**:
  1. Create greenhouse with id `5E3F`, name `Willemshoeve` → expect success, row in DB.
  2. Create with id `5e3f` (lowercase) → expect accepted, stored as `5E3F` (collision against existing).
  3. Create with id `5g3f` → expect Dutch validation error, zero rows added.
  4. Create with id `A2B1`, name `De Linden` → success.
  5. Attempt to delete `5E3F` after attaching an observation to it → expect Dutch error citing the observation count, row persists.
  6. Delete an empty greenhouse → expect success.

#### TC-GH-routing-canonicalisation — URL case normalisation
- **Verifies**: TDS-URL-010, URL-050; FR-GH-080
- **Setup**: `F-GH-1` (id `5E3F`).
- **Probe**: `curl -is $BASE/5e3f/`; `curl -is $BASE/5g3f/`; `curl -is $BASE/` with no cookie + no `current_greenhouse_id`.
- **Expected**: 5e3f → HTTP 301 to `/5E3F/`; 5g3f → HTTP 404 with Dutch "scan een QR-code" template; root → "Scan QR" page within 1 s (with ≤ 4 greenhouses configured, the list appears).

#### TC-GH-qr — QR auto-render + URL content + library
- **Verifies**: FR-GH-070, FR-UI-060; TDS-URL-040, UI-040, UI-060
- **Setup**: `F-ADMIN-SET + F-GH-2`. `public_base_url = 'http://192.168.20.232/webapp'`.
- **Probe**:
  1. `curl -s --cookie-jar adm.txt -d "name=admin&password=…&csrf=…" $BASE/management/login` → admin session.
  2. `curl -s -b adm.txt $BASE/management/greenhouses/5E3F` → assert QR `<img>` or `<svg>` present; **no "Generate" button in the HTML**.
  3. Extract the QR src, decode with `zbarimg` (or similar) → assert decoded URL equals `http://192.168.20.232/webapp/5E3F/`.
  4. Create a new greenhouse `7C7C`; open its detail page → QR already present (no admin click ever happened).

### 8.6 Admin authentication (`TC-ADM-*`)

#### TC-ADM-setup — Setup wizard on empty admin table
- **Verifies**: FR-SEC-020; TDS-AUTH-080, AUTH-090
- **Setup**: `F-CONFIG-VALID`, schema init done, `DELETE FROM admin` to ensure empty.
- **Probe**: `curl -is $BASE/management/` → expect setup wizard within 1 s. POST a password + confirm → expect 303 to login. Then `sqlite3 ... 'SELECT password_hash FROM admin'` → assert begins with `$2y$` or `$argon`. Grep all log files + DB dump for the plaintext → expect zero hits.

#### TC-ADM-login-and-logout — Login generic-error + session regeneration + logout
- **Verifies**: FR-ADM-020, ADM-030, ADM-050, ADM-070; TDS-AUTH-050, AUTH-060, AUTH-100, AUTH-120
- **Setup**: `F-ADMIN-SET`.
- **Probe**:
  1. POST wrong name → response A; POST wrong password → response B; diff A vs B excluding nonce tokens → expect byte-identical.
  2. POST correct → expect 303 to admin home; capture session cookie. Compare with the cookie before login → expect different session ID.
  3. POST `/management/logout` with CSRF token → expect 303 to login URL; cookie max-age=0.
  4. Replay the original session cookie → expect 302 to login (session destroyed).

#### TC-ADM-rate-limit — Per-IP exponential backoff
- **Verifies**: TDS-AUTH-070
- **Setup**: `F-ADMIN-SET`. Reset `admin_login_attempts` for the test IP.
- **Probe**: 5 wrong-password POSTs. 6th POST → expect HTTP 429 with `Retry-After: 60`. After 65 s, 7th wrong-pwd → expect 429 with `Retry-After: 120`.
- **Expected**: Backoff matches the spec; counter row exists in `admin_login_attempts`.

#### TC-ADM-csrf — CSRF rejection on every state-change
- **Verifies**: FR-SEC-010; TDS-AUTH-100
- **Setup**: Logged-in admin.
- **Probe**: POST to a destructive endpoint (e.g. user-forget) **without** the `_csrf` form field → expect 403 within 1 s; DB unchanged. Then same POST with a stale token (replayed after session rotation) → expect 403. Then with valid token → expect 303 success.

#### TC-ADM-audit — Audit log row per destructive admin action
- **Verifies**: FR-ADM-060, FR-OBS-050; TDS-STO-090 (the audit-prune side)
- **Setup**: Logged-in admin, `F-OBS-RECENT`.
- **Probe**: Perform user-forget, observation-modify, observation-delete, taxonomy-edit, greenhouse-edit. `sqlite3 ... 'SELECT action, target_kind, target_id FROM admin_audit ORDER BY id DESC LIMIT 10;'`.
- **Expected**: Exactly 5 new rows, one per action, with correct `target_kind` and `target_id`. The audit-log page in the GUI renders them newest-first within 1 s.

### 8.7 Admin — user management (`TC-USR-*`)

#### TC-USR-list-view — Admin lists and views users
- **Verifies**: FR-USR-010, USR-020
- **Setup**: Logged-in admin; 3 users + 8 observations across them.
- **Probe**: `curl -s -b adm.txt $BASE/management/users` → assert 3 rows + 5-column structure. Click into one user → assert all that user's observations listed, DESC by ts.
- **Expected**: Row count = `SELECT COUNT(*) FROM user`; per-user observation list count = `SELECT COUNT(*) FROM observation WHERE user_id=?`.

#### TC-USR-forget — Admin forget invalidates cookie, preserves data
- **Verifies**: FR-USR-030, FR-USR-040; TDS-AUTH-040
- **Setup**: User Marja, with cookie + observations.
- **Probe**: Admin "forget" Marja. Then `curl -is -b marja-cookies.txt $BASE/5E3F/` → expect M1 splash. Pre/post counts on `user` and `observation` unchanged.

#### TC-USR-orphan-cleanup — Auto-delete after retention sweep removes last observation
- **Verifies**: FR-USR-060; TDS-STO-090
- **Setup**: User with `cookie_invalidated_at IS NOT NULL` and exactly 1 observation, ts > 400 days ago. `retention_days = 365`.
- **Probe**: Trigger the retention sweep (admin "Run retention now" or admin login). SQL: assert observation deleted AND user row deleted.

#### TC-USR-filter — Filter user list by greenhouse (Step 2)
- **Verifies**: FR-USR-050
- **Setup**: 5 users, 2 contribute to 5E3F, 2 to A2B1, 1 to both. Logged in.
- **Probe**: Filter by 5E3F → assert 3 users (2 + the shared one). Clear filter → assert 5.

### 8.8 Admin — observation management (`TC-OBS-*`)

#### TC-OBS-list-filter — Admin observation list + filters
- **Verifies**: FR-OBS-010
- **Setup**: 20 observations across 2 greenhouses, 3 users, 2 categories.
- **Probe**: Open `/management/observations` → count rows; apply each filter dimension singly + combined.
- **Expected**: Counts match DB queries with the same WHERE clauses; AND semantics for combined filters.

#### TC-OBS-view-modify-delete — Full CRUD on one observation
- **Verifies**: FR-OBS-020, OBS-030, OBS-040
- **Setup**: One observation with a photo.
- **Probe**: View → assert all fields + full-res photo. Modify ts + tag → assert DB reflects within 1 s, even for an observation past the user edit window. Delete → assert row gone, GET returns 404, photo files unlinked (per `FR-REC-100`).

### 8.9 Taxonomy management (`TC-TAX-*`)

#### TC-TAX-add — Admin adds category + tag → appear in flow
- **Verifies**: FR-TAX-010
- **Setup**: Logged-in admin.
- **Probe**: Add category `extra` with tag `nieuwe_tag`. Reload `/5E3F/observation/new` and the tag picker for `extra`.
- **Expected**: New rows in `category` / `tag`; new entries appear in next page render (within 1 page load).

#### TC-TAX-rename-and-history — Display rename does not change identity
- **Verifies**: FR-TAX-020, FR-TAX-030, FR-TAX-070
- **Setup**: Existing tag `weather_storm` with display name "Storm"; one historical observation references it.
- **Probe**: Rename display to "Onweer". Then re-render the historical observation's detail page. Then SQL-check `internal_key` unchanged. Then SHA-256 the affected observation row before/after.
- **Expected**: Display shows "Onweer"; `internal_key` still `weather_storm`; row hash unchanged.

#### TC-TAX-archive-and-hard-delete — Archive vs hard-delete behaviour
- **Verifies**: FR-TAX-040, FR-TAX-050
- **Setup**: Tag with active observations.
- **Probe**: Archive → assert absent from M4 but present in historical detail. Hard-delete → assert confirmation dialog cites observation count; on confirm, tag row gone, historical observations now render internal_key as fallback.

#### TC-TAX-tag-limit — ≤ 6 tags per category in M4 (Step 2)
- **Verifies**: FR-TAX-060
- **Setup**: A category with 8 tags.
- **Probe**: Open `/management/taxonomy/<cat>` → assert selection control with hard cap of 6 ticked. Open `/5E3F/observation/new?cat=<cat>` → assert ≤ 6 tag rows rendered.

### 8.10 Export (`TC-EXP-*`)

#### TC-EXP-csv-both-dialects — Selector + dialect-correct output
- **Verifies**: FR-EXP-010, FR-EXP-020, FR-EXP-030; TDS-CSV-010, CSV-020, CSV-030, CSV-060
- **Setup**: 100 observations across 2 greenhouses, spanning the autumn DST boundary.
- **Probe**:
  1. Export dialect A (CSV) → save to `out_a.csv`. Inspect: first 3 bytes ≠ BOM; field sep `,`; `ts_iso8601` matches `T`-separated form with `+01:00` or `+02:00` correctly per row date; line ending CRLF.
  2. Export dialect B (CSV voor Excel) → save to `out_b.csv`. First 3 bytes = `EF BB BF`; field sep `;`; `ts_iso8601` matches space-separated form with offset; line ending CRLF.
  3. `python -c "import csv; rows=list(csv.reader(open('out_a.csv'))); print(len(rows))"` → expect rows count + 1 header.
  4. Filename headers: `out_a.csv` Content-Disposition matches `observations_<gh>_<from>_<to>.csv`; `out_b.csv` has `_excel.csv`.
  5. Empty-result export → file contains only the header row; HTTP 200.

#### TC-EXP-photo-zip — Photo archive download (Step 2)
- **Verifies**: FR-EXP-050; TDS-CSV-040
- **Setup**: 5 observations with photos.
- **Probe**: Trigger "Download photos" → expect ZIP within 2 min. `unzip -l` → expect 5 entries under `YYYY/MM/` paths. Filename matches `..._photos.zip`.

#### TC-EXP-determinism — Reproducible exports (Step 2)
- **Verifies**: FR-EXP-040
- **Setup**: Fixed observation set; no writes between exports.
- **Probe**: Export twice in succession; SHA-256 both; assert equal.

#### TC-EXP-user-self — User GDPR self-export
- **Verifies**: FR-SEC-050; TDS-CSV-050
- **Setup**: `F-USR-MARJA` with observations + photos.
- **Probe**: As Marja, request "Download mijn gegevens". `unzip -l` → assert exactly `observations.csv`, `account.txt`, and a `photos/` tree. The CSV is Dialect A (no BOM, `,` separator).

### 8.11 UI shape (`TC-UI-*`)

#### TC-UI-mobile-targets — Touch target sizing + viewport
- **Verifies**: FR-UI-010; TDS-UI-020
- **Setup**: Headless browser (e.g. Playwright) at viewport 360×640. Render `/5E3F/`.
- **Probe**: Assert every `<button>`, `<a>`, `<input>` has computed hit-box ≥ 44×44 CSS px. `<meta name="viewport">` present with `width=device-width, initial-scale=1`.

#### TC-UI-flow-structure — M1 / M2 / M3 / M4 / M5 / M7 / M8 DOM contracts
- **Verifies**: FR-UI-020, UI-030, UI-040, UI-050, UI-060
- **Setup**: Various seed states (no cookie, cookie + zero obs, cookie + obs).
- **Probe**: For each page, parse DOM and assert structural invariants per the relevant FR.

#### TC-UI-dutch — All user-visible text is Dutch
- **Verifies**: FR-UI-070; TDS-UI-110
- **Setup**: A representative sample of ≥ 20 strings drawn from M1, M2, M3, M4, M5, M7, M8, the admin login, the setup wizard, a 404 page, and a validation-error response.
- **Probe**: Render each + a static scan: assert no English-word leak (`grep -iE "submit|cancel|save|confirm|login|logout|forgot"` returns zero against user-visible text) and that validation messages source from `lang/nl.php`.

#### TC-UI-prg-and-version — PRG pattern + admin footer version
- **Verifies**: TDS-UI-100, UI-120
- **Setup**: Logged-in admin; observation form filled.
- **Probe**: Submit → assert 303 to GET URL. Refresh the resulting page → assert observation count unchanged. Inspect admin footer → assert version string matches `/health`'s JSON version.

#### TC-UI-multi-tab — Last-writer-wins greenhouse-id; tab-local submission
- **Verifies**: TDS-UI-130
- **Setup**: One user, `F-GH-2`.
- **Probe**: Open two tabs (`/5E3F/` and `/A2B1/`). Visit them in sequence; check `current_greenhouse_id`. Submit from the older tab → check new observation's `greenhouse_id`.

#### TC-UI-413 — Friendly Dutch 413 page on oversized upload
- **Verifies**: TDS-UI-150
- **Setup**: Apache config snippet active (per `[IP §P0]`).
- **Probe**: Upload 10 MB photo → assert HTTP 413 with body containing the Dutch templated text and the "8 MB" literal.

### 8.12 Security and operational (`TC-SEC-*`, `TC-OPS-*`)

#### TC-SEC-privacy — Privacy notice present and reachable
- **Verifies**: FR-SEC-040; TDS-UI-080
- **Setup**: `F-USR-MARJA`. Visit `/5E3F/`.
- **Probe**: `curl -s -b cookies.txt $BASE/5E3F/ | grep -c privacy`; `curl -s $BASE/privacy | grep -cE "Welke gegevens|Grondslag|Bewaartermijn|Uw rechten|Contact"`.
- **Expected**: Privacy link on M2; the page contains all five Dutch section headings.

#### TC-SEC-retention — Retention sweep deletes observations + audit + orphan users
- **Verifies**: FR-SEC-060, FR-USR-060; TDS-STO-090, STO-120
- **Setup**: `F-OBS-RETENTION`; `retention_days = 365`; an orphan-user candidate.
- **Probe**: Trigger sweep (admin login or "Run retention now"). SQL: count `observation` (older rows gone), `admin_audit` (rows > 90 d gone), `user` (orphan deleted). `find photos/` for the deleted observations' files.
- **Expected**: Each delete reflected in counts and on disk.

#### TC-OPS-error-pages — Dutch 404/403/413/429/500
- **Verifies**: TDS-UI-090
- **Setup**: Trigger each error: bad URL (404), wrong cookie (403), oversized upload (413), brute-force admin (429), poisoned route (500).
- **Probe**: For each, `curl -is $BASE/<path>` and assert Dutch template + no stack trace / file path in 500 body. `phpinfo()` or `ini_get` → `display_errors = '0'`.

#### TC-OPS-headers — Recommended HTTP security headers present
- **Verifies**: TDS §12.1 (operator recommendation)
- **Setup**: Apache config from `[IP §P0]` includes the recommended headers.
- **Probe**: `curl -I $BASE/5E3F/` → check `Content-Security-Policy`, `X-Content-Type-Options`, `X-Frame-Options`, `Referrer-Policy`, and (HTTPS only) `Strict-Transport-Security`.
- **Expected**: Each header present with the recommended value (or a hardened-equivalent value). On Production, also assert `Strict-Transport-Security`.

#### TC-OPS-wal — SQLite WAL + busy_timeout under light concurrency
- **Verifies**: TDS-STO-110; FR-REC-090
- **Setup**: `F-USR-MARJA`. Background a write loop via SSH.
- **Probe**: `ssh deploy@dev "sqlite3 .../observations.sqlite 'PRAGMA journal_mode;'"` → expect `wal`. Same for `busy_timeout` → expect `5000`. `ab -n 100 -c 10 http://localhost/webapp/5E3F/`. `grep -c "database is locked" /var/log/apache2/error.log`.
- **Expected**: WAL active; busy_timeout = 5000; zero "database is locked" errors during the load.

## 9. End-to-end scenarios

System-level tests that thread multiple FRs together. These are the **smoke set** that runs on every deploy (Dev / Acceptance / Production).

### 9.1 E2E-1 — First-time install to first observation
- **Covers**: P0 + iterations 1–5 in one walkthrough.
- **Steps**: Fresh server → install archive → populate `config.php` → first HTTP request → setup-required → admin sets credentials → admin creates greenhouse `5E3F` → operator scans QR → registers handle "Marja" → records `Wellbeing / all_good` in two taps → sees the entry in the recent panel.
- **Pass**: Each step's FR AC passes; total wall-clock under 5 minutes for a competent admin + operator.

### 9.2 E2E-2 — Acceptance verification arc
- **Covers**: The 2-month intensive cycle from A7 collapsed into one scripted day.
- **Steps**: Seed 600 observations across 15 users and 1 greenhouse with ts spread over 60 days (intensive cycle). Run all `§8` cases. Run CSV export both dialects. Verify Python `csv.reader` reads dialect A without arguments and Dutch Excel opens dialect B with auto-recognised date-time cells.
- **Pass**: All `§8` cases green; both exports parse cleanly in their target tool.

### 9.3 E2E-3 — Admin lifecycle end-to-end
- **Covers**: Admin password setup → rotation → audit log → user forget → retention sweep.
- **Steps**: Empty admin → setup wizard → login → rotate password → check audit log shows rotation → forget a user → wait for sweep → assert orphan-user cleanup happened → re-test login with old password fails, new password succeeds.
- **Pass**: Each transition observable in DB + audit log + behaviour.

### 9.4 E2E-4 — Offline-to-online operator
- **Covers**: FR-REC-080 + TDS-UI-070 + UI-140 end-to-end.
- **Steps**: Operator scans QR with Wi-Fi disabled → records 3 observations offline → indicator shows "queued" with count 3 → re-enables Wi-Fi → within 30 s, indicator shows "synced" → DB shows 3 new rows attributed to the right greenhouse.
- **Pass**: All 3 observations land; queue drains; indicator clears.

### 9.5 E2E-5 — Multi-greenhouse, multi-operator export
- **Covers**: FR-GH multi + FR-EXP filters + dialect selector.
- **Steps**: 2 greenhouses, 5 operators across them, 50 observations. Admin filters by greenhouse `5E3F`, date range yesterday-to-today, category Wellbeing. Exports both dialects. Re-imports into a Python script (CSV) and into Dutch Excel (CSV voor Excel). Spot-checks that filter narrowed correctly.
- **Pass**: Row counts match the filtered admin list; both files open in their target tool with column structure intact.

## 10. Coverage matrix

Every FR and every TDS item appears in at least one TC or E2E. Lookup table:

### FR → TCs

| FR area | FRs | TC(s) |
|---|---|---|
| `INST` | 010, 020, 030, 040, 050, 060, 070, 080 | TC-INST-shipping; TC-INST-setup-page; TC-INST-validation; TC-INST-storage-safety; TC-INST-init-and-seed; TC-INST-health |
| `IDU` | 010, 020, 030, 040, 050, 060, 070 | TC-IDU-first-visit; TC-IDU-handle-uniqueness; TC-IDU-change-handle; TC-IDU-forget; TC-IDU-no-admin-link |
| `REC` | 010, 020, 030, 040, 050, 060, 070, 080, 090, 100 | TC-REC-two-tap; TC-REC-greenhouse-context; TC-REC-photo; TC-REC-limits; TC-REC-severity-note; TC-REC-timestamp; TC-REC-offline |
| `REV` | 010, 020, 030, 040, 050, 060 | TC-REV-recent; TC-REV-history; TC-REV-detail |
| `GH` | 010, 020, 030, 040, 050, 060, 070, 080 | TC-GH-admin-crud; TC-GH-routing-canonicalisation; TC-GH-qr; (020/030/040 also via TC-REC-greenhouse-context); (050 via E2E-2) |
| `ADM` | 010, 020, 030, 040, 050, 060, 070 | TC-ADM-setup; TC-ADM-login-and-logout; TC-ADM-rate-limit; TC-ADM-csrf; TC-ADM-audit; (040 via TC-UI-flow-structure) |
| `USR` | 010, 020, 030, 040, 050, 060 | TC-USR-list-view; TC-USR-forget; TC-USR-orphan-cleanup; TC-USR-filter |
| `OBS` | 010, 020, 030, 040, 050 | TC-OBS-list-filter; TC-OBS-view-modify-delete; (050 via TC-ADM-audit) |
| `TAX` | 010, 020, 030, 040, 050, 060, 070 | TC-TAX-add; TC-TAX-rename-and-history; TC-TAX-archive-and-hard-delete; TC-TAX-tag-limit |
| `EXP` | 010, 020, 030, 040, 050 | TC-EXP-csv-both-dialects; TC-EXP-photo-zip; TC-EXP-determinism |
| `UI` | 010, 020, 030, 040, 050, 060, 070 | TC-UI-mobile-targets; TC-UI-flow-structure; TC-UI-dutch; TC-UI-prg-and-version; TC-UI-multi-tab; TC-UI-413 |
| `SEC` | 010, 020, 030, 040, 050, 060 | TC-ADM-csrf (010); TC-ADM-setup (020); (030 via TC-ADM-login-and-logout); TC-SEC-privacy (040); TC-EXP-user-self (050); TC-SEC-retention (060) |

### TDS → TCs

| TDS area | TDS items | TC(s) |
|---|---|---|
| `STK` | 010..080 | TC-INST-shipping; TC-INST-init-and-seed; TC-UI-flow-structure; static-scan checks during iteration 1 |
| `STO` | 010..140 | TC-INST-storage-safety; TC-INST-init-and-seed; TC-REC-photo; TC-REC-limits; TC-OPS-wal; TC-SEC-retention; TC-GH-admin-crud (STO-100); TC-USR-orphan-cleanup |
| `CFG` | 010..080 | TC-INST-shipping; TC-INST-setup-page; TC-INST-validation; TC-REC-timestamp |
| `URL` | 010..050 | TC-GH-routing-canonicalisation; TC-GH-qr; TC-ADM-login-and-logout (URL-020) |
| `AUTH` | 010..120 | TC-IDU-first-visit; TC-ADM-setup; TC-ADM-login-and-logout; TC-ADM-rate-limit; TC-ADM-csrf; TC-USR-forget |
| `CSV` | 010..060 | TC-EXP-csv-both-dialects; TC-EXP-photo-zip; TC-EXP-user-self |
| `UI` | 010..150 | TC-UI-mobile-targets; TC-UI-flow-structure; TC-UI-dutch; TC-UI-prg-and-version; TC-UI-multi-tab; TC-UI-413; TC-OPS-error-pages; TC-OPS-headers |

If a TDS-OPEN or future TDS item is added later, this matrix gets one more row.

## 11. Pass / fail criteria

| Outcome | Meaning |
|---|---|
| **Pass** | Probe output matches expected; recorded with timestamp + probe output snippet in `testResults/`. |
| **Fail** | Probe output diverges from expected — blocks the iteration's "Done" until fixed and re-tested. |
| **Blocked** | TC cannot run because a precondition / fixture / environment dependency is missing — escalate to the user. |
| **Skipped (justified)** | TC genuinely doesn't apply on the environment (e.g. HTTPS-only headers on a LAN-only deploy). Justification recorded inline. |

## 12. Defect handling

1. **Open** — a Fail outcome creates a defect record with the FR/TDS ID, the failing TC, and the probe output.
2. **Fix** — implementer corrects the code on Claude's host.
3. **Redeploy + re-run only the failing TC plus any TC that exercises the same code path.** No need to rerun green TCs.
4. **Close** — defect is closed once the TC's next run is Pass.
5. **Regression watch** — at the iteration gate, run the full `§8` set for the iteration's scope (not the failed TC alone) to catch nearby regressions.

## 13. Test execution log

Per environment, per iteration: `testResults/<env>/iteration-<N>.md` carries:

```
# Test results — Dev, iteration <N>, <date>
Run by: <Claude / admin>
Commit: <git sha>
Config: F-CONFIG-VALID

| TC ID | Status | Notes / probe excerpt |
|---|---|---|
| TC-INST-shipping | Pass | template_config.php present; config.php absent |
| TC-INST-setup-page | Pass | 6/6 probes returned 503 ≤ 700 ms |
| TC-INST-validation | Fail | bad-TZ probe returned 500 with English message — should be Dutch |
...
```

Three artefacts per run: the markdown summary above, a `raw/` directory of probe output verbatim, and a `defects.md` for any Fail rows.

---

*End of test plan. The plan and the FDS/TDS evolve together: an FR added in a later revision shall be paired with at least one new TC or assigned to an existing TC's `Verifies` list before the implementation iteration that delivers it.*
