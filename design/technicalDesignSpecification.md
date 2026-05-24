# Technical Design Specification — Observation Web App

| Field | Value |
|---|---|
| Document | Technical Design Specification (TDS) |
| Project | Greenhouse Observation App |
| Status | Draft — implementation choices locked 2026-05-23 |
| Scope | All implementation-level decisions that realise the Functional Design Specification. The FDS specifies *what* the system must do; this TDS specifies *how* it shall be built. |
| Related | `functionalRequirements.md` (the binding FDS), `behaviouralDescription.md`, `operatorObservationStrategy.md`, `webguiExample/` (visual style source) |

---

## 1. Purpose

Capture every implementation choice the FDS deliberately leaves abstract. Each TDS item carries a stable ID, a back-reference to the FR(s) it serves, and a **SMART acceptance criterion** (`Acceptance / Verification` column) — a single observable pass/fail check that proves the implementation actually realises the decision. The traceability matrix in §11 inverts the FR→TDS lookup.

This document is **normative for implementation**. Where it locks a choice (PHP, SQLite, path-segment URL, European Excel CSV, etc.) the implementer shall follow it. Where an acceptance criterion just inherits the FR's own AC, the row reads `Inherits [FDS FR-X-NNN]` rather than restating the check — the FDS remains the source of truth.

ID convention: `TDS-<area>-<nnn>`, mirroring the FR ID scheme so cross-references read naturally.

## 2. References

| Ref | Document | Used for |
|---|---|---|
| [FDS] | `design/functionalRequirements.md` | Binding functional requirements that this TDS realises. |
| [BD]  | `design/behaviouralDescription.md` | Original behavioural rules. |
| [OS]  | `design/operatorObservationStrategy.md` | Strategy decisions, UX mock-ups M1–M9, rollout phasing. |

Inline references use `[FDS FR-X-NNN]`, `[BD §n]`, `[OS §n]`.

## 3. Technology stack (`TDS-STK`)

| ID | Decision | Acceptance / Verification | Serves |
|---|---|---|---|
| TDS-STK-010 | The backend is implemented in **PHP**. No other server-side language is allowed. | `composer.json` requires PHP; the HTTP entry-point file ends in `.php`; `src/` contains no `.py`, `.rb`, `.go`, or Node entry-point files. | [BD §1] |
| TDS-STK-020 | Persistent storage is **SQLite** (file-based, single-writer). No MySQL/MariaDB/PostgreSQL/external DBMS. | `composer.json` declares `ext-pdo_sqlite`; declares no `pdo_mysql`/`pdo_pgsql`; the runtime DSN starts with `sqlite:`. | [BD §2], FR-INST-040, FR-INST-050 |
| TDS-STK-030 | The web tier shall run behind **both Apache (≥ 2.4) and nginx (≥ 1.18)** without code changes; only web-server configuration may differ. | CI matrix runs the integration test suite under both Apache 2.4 and nginx 1.18 with identical PHP code; both pipelines pass. | [BD §3] |
| TDS-STK-040 | The deployment target is **any conventional PHP hosting environment**: shared LAMP/LEMP, managed PHP host, or self-hosted VPS. No platform-specific dependencies. | `composer.json` declares no platform-specific deps (AWS SDK, GCP SDK, systemd helpers, etc.); the app starts on a stock PHP 7.4 + nginx Docker image with only the configured `db_path` and `photo_root` mounted. | [OS §10.1] |
| TDS-STK-050 | The runtime depends on **no external services** (no Redis, no message queue, no S3, no separate auth server). Everything ships in the PHP + SQLite single-machine deploy. | The app boots and serves a full happy-path request flow with **all outbound network blocked** (firewall rule or `unshare -n`); only loopback to itself is permitted. | derives from [OS §10.1] |
| TDS-STK-060 | The minimum supported PHP version is **PHP 7.4**. The codebase declares this in its `composer.json` (or equivalent) and avoids syntax requiring PHP 8.x. **Note for operators:** PHP 7.4 reached upstream end-of-life in November 2022 and receives no further language-level security updates; this choice deliberately prioritises compatibility with legacy shared-hosting environments. Operators of public-internet deployments should account for this in their threat model (A1 — transport security is the administrator's responsibility) and either deploy behind a hardened web layer, restrict access, or run on a PHP version that still receives upstream security updates. | `composer.json` carries `"php": "^7.4"`; the CI matrix runs the full test suite against PHP 7.4 and passes; PHP 8.x syntax-only constructs (named args, enums, readonly properties, `match`) produce a parse error and break CI. | [OS §10.1] broad hosting compat |
| TDS-STK-070 | SQLite is accessed via the **`PDO_SQLite`** extension. Every query uses prepared statements with parameter binding; no raw string interpolation into SQL. The `sqlite3` extension is not used. | Static scan of `src/`: every `PDO::query`/`exec` call binds parameters; `grep -rE "->(query\|exec)\(.*\\\$" src/` returns **zero** matches involving user input; the `sqlite3` extension is not in `composer.json`. | FR-INST-040, FR-INST-050; portability and standard idiom |
| TDS-STK-080 | HTML views are **plain PHP files** (no templating engine). Every variable interpolation in a view shall pass through `htmlspecialchars($v, ENT_QUOTES \| ENT_SUBSTITUTE, 'UTF-8')` (or an equivalent helper) before being emitted. URL components use `rawurlencode`; attribute values are quoted with `"`. | Composer has no templating-engine dep (Twig, Plates, Blade); every `<?=` in `views/` either calls the escape helper or `htmlspecialchars()` directly — verified by static scan; an XSS probe with `<script>alert(1)</script>` in a handle renders escaped, not executed. | FR-UI-*; matches the plain HTML/CSS/JS shape of the `webguiExample` bundle (TDS-UI-010) |

## 4. Storage design (`TDS-STO`)

| ID | Decision | Acceptance / Verification | Serves |
|---|---|---|---|
| TDS-STO-010 | The SQLite database file path is configured in `config.php` (see TDS-CFG-040). The app shall **verify on every request** that the path resolves to a location **outside the document root** and shall refuse to serve content otherwise. | Inherits [FDS FR-INST-040]. | FR-INST-040 |
| TDS-STO-020 | On first run the app shall create the SQLite schema (tables and indices) idempotently. On subsequent runs it detects the existing schema and does nothing. | Inherits [FDS FR-INST-050]. | FR-INST-050 |
| TDS-STO-030 | Photo files are stored under a **date-sharded layout**: `<photo_root>/<YYYY>/<MM>/<observation-id>.<ext>`, where `<photo_root>` is the path configured in `config.php`. | After uploading a photo for observation `X` whose `ts` falls in May 2026, the file is present at `<photo_root>/2026/05/X.<ext>` on disk; no photo file ever exists outside the `YYYY/MM/` shard structure. | FR-REC-070 |
| TDS-STO-040 | Photos are served via a **controlled endpoint** that enforces ownership (owner user) or admin role. Photo URLs shall not be guessable. | Inherits [FDS FR-REC-070]. | FR-REC-070 |
| TDS-STO-050 | `<photo_root>` shall also resolve to a location **outside the document root**. The same per-request safety check as TDS-STO-010 applies. | Misconfiguring `photo_root` inside the document root causes every HTTP request to return HTTP 500 within 1 s with an "unsafe storage location" message — same probe pattern as FR-INST-040 AC. | FR-REC-070 |
| TDS-STO-060 | Photo upload limits enforced server-side: maximum file size **8 MB**; accepted MIME types **`image/jpeg`, `image/png`, `image/webp`** plus **`image/heic` / `image/heif`** (each validated by inspecting the file's bytes, not by trusting the client `Content-Type` header); maximum input dimensions before downscale **8192 × 8192**. HEIC/HEIF support requires ImageMagick compiled with `libheif`; if unavailable, the server shall reject HEIC uploads with a clear message asking the user to share as JPEG. Uploads exceeding any limit are rejected with a user-facing error. | Posting a 9 MB JPEG returns HTTP 413; posting a `.gif` (a `.jpg` renamed to `.gif`, or a real GIF) returns HTTP 415; posting a 9000 × 9000 JPEG is rejected; without `libheif`, posting a HEIC returns HTTP 415 with the "share as JPEG" message. | FR-REC-070 |
| TDS-STO-070 | On upload, after MIME validation, every photo shall be processed as follows: (1) **read EXIF orientation** and rotate the pixel data so the saved image is upright (else portrait phone-photos render sideways once EXIF is stripped); (2) **strip all EXIF metadata** including GPS, camera model, and timestamps, for operator privacy; (3) if the long edge exceeds **2048 px**, downscale preserving aspect ratio (Lanczos or equivalent resampling); (4) generate a **thumbnail** with long edge **400 px**, stored alongside the full image as `<obs-id>_thumb.<ext>` in the same date-sharded directory (TDS-STO-030). The thumbnail is used by recent-observation lists (FR-REV-010, FR-REV-020); the full image is used by the detail view (FR-REV-030). | Uploading a portrait JPEG with EXIF orientation `6` and GPS tag stores an upright-oriented image; `exiftool <stored.jpg>` reports zero metadata tags; the long edge of the stored full image is ≤ 2048 px; a `<obs-id>_thumb.<ext>` file exists in the same shard directory with long edge ≤ 400 px. | FR-REC-070, FR-REV-010, FR-REV-020 |

**Backup of the SQLite database and the photo store is the administrator's responsibility** (assumption A1, [BD §6]); the TDS does not prescribe a mechanism. Operator documentation should provide a recommended recipe (typical pattern: nightly `sqlite3 dbfile .backup backup.db` plus `rsync` of `<photo_root>` to a separate volume, with a retention policy that fits the host).

| ID | Decision | Acceptance / Verification | Serves |
|---|---|---|---|
| TDS-STO-080 | Schema changes after launch are managed by **versioned SQL files**. A `schema_meta` table holds the highest applied version as a single row (`id=1, version=<n>`). On startup the app reads the version, scans a `migrations/` directory for files named `0001_<slug>.sql`, `0002_<slug>.sql`, ..., and applies any with a version higher than the recorded one, in numeric order, inside a transaction. Each migration ends with `INSERT OR REPLACE INTO schema_meta (id, version) VALUES (1, <n>);`. Step 1 ships with the initial schema as `0001_initial.sql` (version 1); later schema changes are added as `0002_*.sql`, `0003_*.sql`, etc. | After first run, `SELECT version FROM schema_meta WHERE id=1` returns `1`. Adding `migrations/0002_test.sql` and restarting the app promotes the value to `2`. A third restart with no new file is a no-op (version unchanged, no DDL executed — verified by SQLite query-log probe). | FR-INST-050 (extends auto-init beyond first-run) |
| TDS-STO-090 | **Periodic data sweep** (FR-SEC-060, FR-USR-060) runs at most once per **admin login**, throttled to at most once per **hour** (timestamp persisted in `schema_meta` or a sibling table), and optionally via an admin "Run retention now" button. When `retention_days > 0` the sweep performs three operations in order, each inside its own transaction: <br><br> (1) `DELETE FROM observation WHERE ts < datetime('now', '-' || :retention_days || ' days')` — the matching photo files (full + thumbnail per TDS-STO-070) are unlinked via the FR-REC-100 helper. <br> (2) `DELETE FROM admin_audit WHERE ts < datetime('now', '-90 days')` — fixed 90-day window per FR-SEC-060. <br> (3) `DELETE FROM user WHERE cookie_invalidated_at IS NOT NULL AND id NOT IN (SELECT user_id FROM observation)` — orphan-user cleanup per FR-USR-060. <br><br> With `retention_days = 0` the sweep is a no-op for all three (audit-log and orphan-user cleanup also stop, because they are part of the same "retention enabled" feature). | Inherits [FDS FR-SEC-060] + [FDS FR-USR-060]. | FR-SEC-060, FR-USR-060 |
| TDS-STO-100 | **Greenhouse delete safeguard** (FR-GH-060): a delete of a `greenhouse` row first runs `SELECT COUNT(*) FROM observation WHERE greenhouse_id = ?`; if `> 0`, the operation is aborted at the application layer with an error citing the observation count. Implemented in PHP (not via SQL trigger) so the error message can be friendly Dutch. The schema does **not** use `ON DELETE CASCADE` for the `observation → greenhouse` relationship. | Inherits [FDS FR-GH-060]. Schema inspection: `PRAGMA foreign_key_list(observation)` shows the FK to `greenhouse` with `on_delete = 'NO ACTION'`. | FR-GH-060 |
| TDS-STO-110 | **SQLite is opened in WAL journal mode** (`PRAGMA journal_mode = WAL;`) on connection initialisation. WAL allows readers to proceed without blocking the writer, which materially improves perceived latency on shared hosting where multiple operators may submit observations concurrently. WAL also reduces the chance of "database is locked" errors. The implementer shall also set `PRAGMA busy_timeout = 5000;` so transient lock waits do not surface as hard errors. | `sqlite3 <db_path> "PRAGMA journal_mode;"` returns `wal`; `PRAGMA busy_timeout;` returns `5000`; a concurrent load test of 10 readers + 1 writer for 30 s completes with zero "database is locked" errors. | FR-REC-090 (concurrency at write time) |
| TDS-STO-120 | **Photo file lifecycle — synchronous unlink** (FR-REC-100): every code path that deletes an observation row (user delete-within-window FR-REV-040, admin delete FR-OBS-040, retention sweep TDS-STO-090) calls a helper `delete_observation_photos($obs_id)` immediately after the row delete inside the same logical operation; the helper `unlink()`s both `<obs-id>.<ext>` and `<obs-id>_thumb.<ext>` under the date-shard directory (TDS-STO-030). Photo replacement (FR-REC-040 add-photo, FR-OBS-030 admin edit) writes the new file first, updates the row, then unlinks the previous file path. `unlink()` failure is logged via `error_log()` (TDS-UI-090) but does **not** undo the row change — the orphan-file cost of one log entry is preferable to the inconsistency cost of a half-applied delete. | Inherits [FDS FR-REC-100]. | FR-REC-100 |
| TDS-STO-130 | **Launch-taxonomy seed** (FR-INST-070): immediately after schema init (TDS-STO-020), the app checks whether the `category` table is empty; if so, it inserts the five launch categories from [OS §3] with internal keys `wellbeing`, `environment`, `crop`, `sensor_control`, `maintenance`, and Dutch display names ("Welzijnscheck", "Omgeving", "Gewas", "Sensor/regeling", "Onderhoud"). For each category it inserts the default tags from [OS §3 Tag taxonomy] (e.g. `all_good` → "Alles goed", `weather_storm` → "Storm", etc.). Internal keys stay English (FR-TAX-030 stable identity, FR-UI-070 exception for identifiers). The seed is **strictly conditional on empty tables** so admin edits in later runs are preserved. | Inherits [FDS FR-INST-070]. Internal-key spot check: `SELECT internal_key FROM category ORDER BY id` returns exactly `wellbeing, environment, crop, sensor_control, maintenance`. | FR-INST-070 |
| TDS-STO-140 | **Photo serving cache headers** (`/photo/<obs-id>`): the response carries `Cache-Control: private, max-age=86400, immutable` and a strong `ETag` derived from the file's `(size, mtime)`. The `private` directive prevents shared caches from storing the photo. `immutable` is safe because the URL path embeds the observation id, and FR-OBS-030 photo replacement is rare; on replacement the on-disk path stays the same but the ETag changes so conditional requests revalidate correctly. | GET `/photo/<obs-id>` response headers include `Cache-Control: private, max-age=86400, immutable` and a non-empty `ETag`. A subsequent request with `If-None-Match: <etag>` returns HTTP **304** with no body. After admin photo replacement (FR-OBS-030), the next request with the previous `If-None-Match` returns HTTP **200** with the new bytes (ETag differs). | FR-REC-070, FR-REV-010 (thumbnails in list views) |

## 5. Logical data model (`TDS-DM`)

The entity-relationship diagram below is normative for structure; schema-level detail (column types, NULL constraints, indices) is the implementer's call within the constraints of the FRs. No row-level AC — the diagram is validated by the existence of the corresponding tables and columns after `0001_initial.sql` runs (TDS-STO-080).

```
greenhouse (id PK, name, notes, created_at)
   │
   │ 1..*
   ▼
observation (id PK, greenhouse_id FK, user_id FK,
             ts, category_id FK, tag_id FK,
             severity NULL, note NULL, photo_path NULL,
             created_at, updated_at)
   ▲
   │ *..1
   │
user (id PK, handle, current_cookie_token, csrf_token,
      current_greenhouse_id FK NULL,
      created_at, last_seen_at, cookie_invalidated_at NULL)

category (id PK, internal_key UNIQUE, display_name,
          active_flag, sort_order)
tag      (id PK, category_id FK, internal_key UNIQUE,
          display_name, active_flag, sort_order)

admin (id PK CHECK(id=1), password_hash, password_updated_at)

admin_login_attempts (ip PK, last_ts, count, locked_until)

admin_audit (id PK, ts, action, target_kind, target_id, details)   -- Step 2
```

Notes:

- All `ts`, `created_at`, `updated_at`, `password_updated_at`, etc. columns are stored as **UTC** TEXT (`YYYY-MM-DDTHH:MM:SSZ`) per TDS-CFG-070; conversion to the configured timezone happens at render time.
- `internal_key` for category and tag is the stable identity required by FR-TAX-030. Display names map to internal keys; observations persist only the internal key.
- `user.current_cookie_token` is the column updated by FR-USR-030 (admin forget) and FR-IDU-060 (user forget). The user row itself is preserved (FR-USR-040).
- `user.csrf_token` (TDS-AUTH-100) is rotated whenever `current_cookie_token` rotates, plus on handle change (FR-IDU-050).
- `admin` is a single-row table (the `CHECK(id=1)` constraint enforces it); see TDS-AUTH-080 for the lifecycle (setup wizard, rotation, recovery by row deletion).
- `observation.photo_path` is a relative path under `<photo_root>` (see TDS-STO-030), not a public URL.

## 6. Configuration design (`TDS-CFG`)

| ID | Decision | Acceptance / Verification | Serves |
|---|---|---|---|
| TDS-CFG-010 | The configuration file is `config.php`, a PHP file at a fixed location next to the application entry-point. Its internal shape is locked by TDS-CFG-060. | `config.php` exists at the application root; the bootstrap loads it on every request; deleting `config.php` triggers the FR-INST-020 setup-required page. | FR-INST-010 |
| TDS-CFG-020 | The distribution ships `template_config.php` as a starter template with documented keys and safe defaults. It does **not** ship a populated `config.php`. | Inherits [FDS FR-INST-010]. | FR-INST-010 |
| TDS-CFG-030 | On first request, the app verifies that `config.php` exists and that `admin_name` and `admin_password` are both **non-empty**; otherwise it renders an informative setup-required page and refuses other operations. | Inherits [FDS FR-INST-020]. | FR-INST-020 |
| TDS-CFG-040 | Required configuration keys: `admin_name`, `admin_session_timeout` (seconds), `db_path` (SQLite file path), `photo_root` (photo storage directory), `admin_url_path` (default `management`), `edit_window_hours` (default `24`), `timezone` (IANA name, default `Europe/Amsterdam`), `retention_days` (positive integer days, or `0` to disable; **default `365`** — bounds the persistent data without forcing a value). Optional but **strongly recommended** for QR-code correctness: `public_base_url` (canonical public URL of the deployment, e.g. `https://obs.example.com`, no trailing slash). When `public_base_url` is unset, the app derives the base URL from `$_SERVER` per TDS-URL-040; reverse-proxy deployments **shall** set it explicitly so the QR matches the operator-facing URL. Additional keys may be added. **Note**: `admin_password` is deliberately **not** a config key — it is set via the setup wizard (FR-SEC-020) and stored as a hash in the database per TDS-AUTH-080. | Inherits [FDS FR-INST-030] + [FDS FR-INST-080]. The shipped `template_config.php` lists all required keys with default values where defined. | FR-INST-030, FR-SEC-020, FR-SEC-060, FR-GH-070 |
| TDS-CFG-050 | The `edit_window_hours` value shall be administered **only** in `config.php`; the admin GUI shall not expose a control to override it. | Inherits [FDS FR-INST-030] (zero edit-window form fields in admin GUI HTML). | FR-INST-030 |
| TDS-CFG-060 | `config.php` shall be a PHP file that **returns an associative array** of configuration values: `<?php return ['admin_name' => '...', 'db_path' => '...', ...];`. Defaults shall be merged in code from a separate baseline array (e.g. `array_replace(default_config(), require __DIR__ . '/config.php')`). The file shall not use `define()` constants or a `Config` class. | `grep -E "\bdefine\s*\(" config.php template_config.php` returns zero matches; the parsed value of `require 'config.php'` is an `array`; no `Config` class exists in `src/`. | FR-INST-010, TDS-CFG-010 |
| TDS-CFG-070 | All timestamp columns are stored as **UTC** in TEXT, formatted `YYYY-MM-DDTHH:MM:SSZ` (internal canonical form — never user-visible). All read-time conversion to the configured `timezone` (TDS-CFG-040) happens at the rendering layer via PHP `DateTimeImmutable` + `DateTimeZone`. User-facing pages display timestamps in the configured TZ. CSV export (TDS-CSV-020 `ts_iso8601`) writes timestamps with the equivalent explicit offset; the date-time separator is **`T`** in Dialect A (e.g. `2026-05-23T14:30:00+02:00`) and **a space** in Dialect B for Excel compatibility (e.g. `2026-05-23 14:30:00+02:00`) per TDS-CSV-010. DST transitions are handled correctly by tzdata. | A freshly-inserted observation row's `ts` matches `^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}Z$`; the rendered detail page shows the timestamp in the configured TZ with `±HH:MM` offset; a row inserted on either side of the autumn DST boundary renders with the correct offset (`+02:00` vs `+01:00` for `Europe/Amsterdam`). | FR-REC-050, FR-EXP-020, A5 |
| TDS-CFG-080 | **Configuration validation at boot** (FR-INST-080): a `validate_config(array $cfg): array` helper runs on every entry-point bootstrap and returns the list of failed rules, if any. Rules: `admin_name` non-empty string; `admin_session_timeout` integer ≥ 60; `db_path` resolves to writable file or creatable file outside doc root; `photo_root` resolves to writable directory outside doc root; `admin_url_path` matches `^[A-Za-z0-9_-]+$`; `edit_window_hours` integer ≥ 1; `timezone` accepted by `new DateTimeZone($v)` without exception; `retention_days` integer ≥ 0. If any rule fails, the bootstrap renders the Dutch setup-required page (same template as FR-INST-020) listing all failed rules and aborts. | Inherits [FDS FR-INST-080]. Unit-test fixtures with each invalid key in turn return the failed rule name from `validate_config()`. | FR-INST-080 |

## 7. URL design (`TDS-URL`)

| ID | Decision | Acceptance / Verification | Serves |
|---|---|---|---|
| TDS-URL-010 | The greenhouse ID is encoded as a **path segment** immediately after the application base path: `<base>/<gh-id>/...`. Query parameters are not used for this purpose. | GET `/5E3F/` resolves to the greenhouse home (HTTP 200); GET `/?gh=5E3F` returns HTTP 404 (no query-parameter route is registered). | FR-GH-020 |
| TDS-URL-020 | The admin GUI is served from `<base>/<admin_url_path>/`, where `admin_url_path` is the configuration key (default value `management`, per TDS-CFG-040). The admin path is **not linked** from the user GUI. | With default config, GET `/management/` returns the admin login page; changing `admin_url_path = 'beheer'` and restarting makes GET `/management/` return HTTP 404 and GET `/beheer/` return the admin login. Cross-checked with FR-IDU-070 AC (zero occurrences of the path in user-side HTML). | FR-ADM-010 |
| TDS-URL-030 | Indicative route map (final names are the implementer's call): `/` (root, may redirect to most-recent greenhouse), `/<gh-id>/` (home for that greenhouse), `/<gh-id>/observation/new`, `/<gh-id>/observation/<obs-id>`, `/<gh-id>/forget` (cookie self-clear), `/photo/<obs-id>` (controlled photo endpoint), `/<admin_url_path>/...` (admin tree). | On a fresh install with seed data, each documented route either returns its intended content or — for the few routes whose behaviour depends on state (FR-GH-080 root fallback, etc.) — the corresponding state-conditioned response. None of the listed routes returns HTTP 404 with a configuration that should support it. | FR-* |
| TDS-URL-040 | The QR-code sign encodes the URL `<public_base_url>/<gh-id>/`, where `<public_base_url>` is the value of the `public_base_url` configuration key (TDS-CFG-040). When `public_base_url` is unset, the app derives the base URL from `$_SERVER` at QR-render time: scheme from `HTTPS` (`on` ⇒ `https`, else `http`), host from `HTTP_HOST`, path-prefix from `dirname($_SERVER['SCRIPT_NAME'])`. **Reverse-proxy deployments shall set `public_base_url` explicitly** because `HTTP_HOST` then reflects the internal hostname, not the public one operators scan. The QR is generated automatically per FR-GH-070 — there is no admin action that triggers it. | With `public_base_url = 'https://obs.example.com'`, decoding the rendered QR for greenhouse `5E3F` yields exactly `https://obs.example.com/5E3F/`. With the key unset, the decoded URL matches the request-derived base (`https://<HTTP_HOST><script-dirname>/5E3F/`). | FR-GH-070 |
| TDS-URL-050 | **Greenhouse ID format validation** (FR-GH-010, FR-GH-060): the canonical greenhouse-id form is **uppercase** hex, matching `^[0-9A-F]{4}$`. The routing layer accepts `^[0-9A-Fa-f]{4}$` (case-insensitive) in the `/<gh-id>/...` segment and, on a lowercase or mixed-case match, issues an HTTP **301** redirect to the uppercase canonical URL so QR scans, hand-typed URLs, and Service Worker replays all converge on one path. Any other shape returns HTTP **404** with the Dutch "scan een QR-code" template (FR-GH-080). The admin create/edit form (FR-GH-060) validates on submit using the case-insensitive regex and **normalises lowercase input to uppercase** before insert; non-conforming input is rejected with a Dutch error message and a hint "(meestal de laatste 2 bytes van het MAC-adres van de regelaar, in hex, hoofdletters)". | GET `/5e3f/` returns HTTP **301** with `Location: /5E3F/`; GET `/5g3f/` returns HTTP **404** (Dutch template); admin form submit with `5e3f` succeeds and the DB shows `id = '5E3F'`; submit with `5g3f` is rejected with the Dutch error and zero new greenhouse rows. | FR-GH-010, FR-GH-060 |

## 8. Authentication and session design (`TDS-AUTH`)

| ID | Decision | Acceptance / Verification | Serves |
|---|---|---|---|
| TDS-AUTH-010 | The user-identity cookie carries an **opaque random token** of at least 128 bits of entropy, generated from a cryptographically secure RNG (e.g. PHP `bin2hex(random_bytes(16))` — 32 hex chars). | The cookie set on registration matches `^[0-9a-f]{32,}$`; statistical spot-check (chi-squared over 1 000 issued tokens) confirms uniform hex distribution. | FR-IDU-020 |
| TDS-AUTH-020 | The server side maps the token to a user record via the `user.current_cookie_token` column (see TDS-DM). The cookie itself contains no user-identifying information. | Inherits [FDS FR-IDU-020]. | FR-IDU-020 |
| TDS-AUTH-030 | Cookie attributes: `HttpOnly=true`; `Secure=true` when the request arrives over HTTPS; `SameSite=Lax`; `Path=/`; `Max-Age` set to a far-future date (effectively unlimited per FR-IDU-030). | Set-Cookie header carries `HttpOnly; SameSite=Lax; Path=/; Max-Age=<≥315360000>`; when the request was HTTPS, the header also carries `Secure`. | FR-IDU-020, FR-IDU-030 |
| TDS-AUTH-040 | Admin "forget" (FR-USR-030) is implemented as a write to `user.current_cookie_token` (rotated to a new value or NULL) plus a `cookie_invalidated_at` timestamp. The user record and observations are preserved (FR-USR-040). | Inherits [FDS FR-USR-030] + [FDS FR-USR-040]. | FR-USR-030 |
| TDS-AUTH-050 | Admin session uses PHP's native session mechanism (`session_start()`), keyed by the `admin_name` from config. Idle timeout is enforced server-side using `admin_session_timeout`. On **successful admin login** the app calls `session_regenerate_id(true)` immediately before redirecting to the admin home, replacing the session identifier and deleting the old session record (prevents session-fixation attacks per FR-ADM-070). | Inherits [FDS FR-ADM-030] + [FDS FR-ADM-070]. The session-id value present in the response cookie after successful login differs from the value the client carried into the login request. | FR-ADM-030, FR-ADM-070 |
| TDS-AUTH-060 | Failed admin login returns the same generic error ("Invalid credentials") regardless of whether the failure was due to the name or the password. | Inherits [FDS FR-ADM-050]. | FR-ADM-050 |
| TDS-AUTH-070 | Admin login shall enforce a **per-IP rate limit**: after **5** failed attempts the IP is locked out for **60 seconds**; each further failure **doubles** the lockout (60 s → 120 s → 240 s → ...) up to a cap of **1 hour**. Counters reset for an IP after **24 hours** of no attempts. Failed-attempt state is persisted in a small SQLite table (e.g. `admin_login_attempts(ip, last_ts, count, locked_until)`). | After 5 failed attempts from one IP, the 6th attempt returns HTTP 429 with `Retry-After: 60`; the 7th (made > 60 s later) returns 429 with `Retry-After: 120`; after 24 h idle, the next attempt returns HTTP 200 / 401 (counter reset). | defence-in-depth |
| TDS-AUTH-080 | The administrator password is stored as a **PHP `password_hash()`** value using the default algorithm (`PASSWORD_DEFAULT`, currently bcrypt as of PHP 7.4). Verification uses `password_verify()`. The hash lives in an `admin` table with a single row: `admin (id INTEGER PRIMARY KEY CHECK(id = 1), password_hash TEXT NOT NULL, password_updated_at TEXT NOT NULL)`. The single-row CHECK prevents accidental multi-admin records. On password rotation (FR-SEC-030) the hash is replaced and `password_updated_at` is set to the current UTC timestamp. | Stored `password_hash` matches `^\$2y\$` (bcrypt) or `^\$argon` (Argon2). `INSERT INTO admin (id, …) VALUES (2, …)` fails on the CHECK constraint. Admin login with the plaintext supplied to the setup wizard succeeds; with any other string fails. The plaintext appears in zero log files and zero DB columns (grep audit). | FR-ADM-020, FR-SEC-020, FR-SEC-030 |
| TDS-AUTH-090 | **First-admin-visit setup wizard**: if the `admin` table is empty when any admin URL is requested, the app renders the setup form (password + confirm) instead of routing to the requested page. On submit it stores the hash via TDS-AUTH-080 and redirects to the admin login. The wizard is reachable **only** from the admin URL; the user app does not show it. The user-side app continues to function normally during the pre-setup period. If the admin needs to recover from a lost password, manually deleting the `admin` row (via SQLite CLI) re-arms the wizard. | Inherits [FDS FR-SEC-020]. After running `DELETE FROM admin` and reloading the admin URL, the wizard appears again. | FR-SEC-020 |
| TDS-AUTH-100 | **CSRF tokens** use the synchronizer-token pattern. <br>• **Admin side**: a 128-bit token is generated on PHP session start (`bin2hex(random_bytes(16))`), stored in `$_SESSION['csrf']`, embedded in every state-changing form as a hidden input named `_csrf`, and rotated on admin login and on password rotation. <br>• **User side**: a 128-bit token is generated when the user cookie is first issued (and on each cookie rotation per FR-USR-030 / FR-IDU-060, plus on handle change per FR-IDU-050) and stored in a new `user.csrf_token` column (see TDS-DM update); the same `_csrf` hidden-input pattern is used. <br>• Verification uses `hash_equals()` to avoid timing attacks. <br>• AJAX requests (Step-2 offline-queue replay per TDS-UI-070) carry the token in an `X-CSRF-Token` header instead of (or in addition to) the form field. <br>• Mismatch ⇒ HTTP 403 per FR-SEC-010, with no DB write. | Inherits [FDS FR-SEC-010]. | FR-SEC-010 |
| TDS-AUTH-110 | **Handle uniqueness** (FR-IDU-010, FR-IDU-050): the `user.handle` column carries the original-case value the operator typed; a sibling `user.handle_norm` column stores the same value lower-cased (using PHP `mb_strtolower($s, 'UTF-8')`), and a UNIQUE INDEX on `handle_norm` enforces case-insensitive uniqueness at the database layer. The application also performs a SELECT-by-`handle_norm` before INSERT/UPDATE to render a friendly Dutch error rather than a raw constraint-violation. Handle length validation (1..40 chars after `trim()`) is enforced at the PHP layer. | Inherits [FDS FR-IDU-010] + [FDS FR-IDU-050]. `PRAGMA index_list(user)` shows a UNIQUE index on `handle_norm`. | FR-IDU-010, FR-IDU-050 |
| TDS-AUTH-120 | **Admin logout endpoint** (FR-ADM-070): `POST /<admin_url_path>/logout` (CSRF-protected per TDS-AUTH-100). The handler calls `session_unset(); session_destroy();` and emits a `Set-Cookie` clearing the PHP session cookie (`Max-Age=0`), then issues HTTP 302 to the admin login URL. A visible "Uitloggen" link in the admin header issues the POST via a small CSRF-protected form (not a plain `<a href>`, since logout is a state change). | Inherits [FDS FR-ADM-070]. | FR-ADM-070 |

## 9. CSV export format (`TDS-CSV`)

| ID | Decision | Acceptance / Verification | Serves |
|---|---|---|---|
| TDS-CSV-010 | The admin CSV export supports **two dialects**, selectable from the export dialog (FR-EXP-010). Both encode the same rows (FR-EXP-030) and the same columns (FR-EXP-020, TDS-CSV-020); the byte encoding **and** the timestamp date-time separator differ between them. <br><br> **Dialect A — "CSV" (RFC 4180; default for Python tooling):** `,` (comma) field separator; `"` quoting for fields containing the separator, the quote, or a newline; UTF-8 encoding **without** BOM; **CRLF** (`\r\n`) line endings; `ts_iso8601` formatted `YYYY-MM-DDTHH:MM:SS±HH:MM` (full ISO-8601 with `T` separator — round-trips through `datetime.fromisoformat` in Python). <br><br> **Dialect B — "CSV voor Excel" (European Excel):** `;` (semicolon) field separator; `"` quoting (same rule as A); UTF-8 encoding **with** BOM (`EF BB BF`); **CRLF** line endings; `ts_iso8601` formatted `YYYY-MM-DD HH:MM:SS±HH:MM` (**space** instead of `T` between date and time — Excel auto-recognises this as a date-time cell rather than dropping it into text mode). Opens by double-click in Dutch / German Excel locales where comma is the decimal mark; the BOM makes Excel auto-detect UTF-8. | Inherits [FDS FR-EXP-010]. Byte probe: Dialect A starts with the header row (no BOM); Dialect B's first three bytes are `EF BB BF`. | FR-EXP-010, FR-EXP-020 |
| TDS-CSV-020 | Column order (header row): `greenhouse_id`, `observation_id`, `ts_iso8601`, `user_id`, `user_handle`, `category_key`, `category_display`, `tag_key`, `tag_display`, `severity`, `note`, `photo_filename`. Additional columns may be appended; these twelve are required. The `ts_iso8601` value carries the explicit `±HH:MM` offset for the configured timezone per TDS-CFG-070; the date-time separator is **`T`** in Dialect A (`2026-05-23T14:30:00+02:00`) or a **space** in Dialect B (`2026-05-23 14:30:00+02:00`) per TDS-CSV-010, so the same column accommodates both export choices without changing the schema. Header names stay English by convention for analyst-pipeline portability, even when the UI runs in Dutch (FR-UI-070). | Inherits [FDS FR-EXP-020]. The header row split by the dialect's separator equals the 12-column list above in exact order. | FR-EXP-020 |
| TDS-CSV-030 | Exported filename pattern: <br>• **Dialect A (CSV)**: `observations_<gh-id>_<from-yyyymmdd>_<to-yyyymmdd>.csv` <br>• **Dialect B (CSV voor Excel)**: `observations_<gh-id>_<from-yyyymmdd>_<to-yyyymmdd>_excel.csv` <br><br> The `_excel` suffix lets both variants coexist in the same folder without overwriting each other and makes the dialect obvious from the filename alone. When a filter is unbounded on one end the corresponding date segment is `all`. | The `Content-Disposition: attachment; filename="..."` header on each export matches the pattern for the selected dialect. An unbounded-from filter produces `..._all_<to>.csv`. | FR-EXP-010 |
| TDS-CSV-040 | Photo archive filename (when offered per FR-EXP-050): `observations_<gh-id>_<from>_<to>_photos.zip`. ZIP contents preserve the date-sharded layout from TDS-STO-030. | The `Content-Disposition` header on the photo archive download matches the pattern; `unzip -l <archive>` shows entries under `YYYY/MM/` paths. | FR-EXP-050 |
| TDS-CSV-050 | **User self-export** (FR-SEC-050) produces a single ZIP named `mijn_gegevens_<handle>_<yyyymmdd>.zip` containing: (a) `observations.csv` — written in **Dialect A (CSV / RFC 4180)** per TDS-CSV-010 unconditionally (no selector here, for maximum portability of the GDPR export), columns from TDS-CSV-020, restricted to rows where `user_id = <requesting user>`; (b) `account.txt` — UTF-8 text with handle, account creation timestamp, current greenhouse, last-seen timestamp, cookie issue timestamp; (c) `photos/` directory preserving the date-shard layout from TDS-STO-030 with every photo file the user owns. CSRF-protected per TDS-AUTH-100. | Inherits [FDS FR-SEC-050]. `unzip -l` shows exactly `observations.csv`, `account.txt`, and a `photos/` tree. | FR-SEC-050 |
| TDS-CSV-060 | **Empty-result behaviour**: a CSV export whose filter matches zero observations produces a file containing **only the header row** (TDS-CSV-020), with the BOM and CRLF line ending. The download still succeeds with HTTP 200; no error is raised. Analyst pipelines that read the file see a valid CSV with zero data rows. | Exporting with a filter that matches zero rows returns HTTP 200; the downloaded file's row count (per the chosen dialect's parser) is exactly 1 (the header). | FR-EXP-010, FR-EXP-030 |

## 10. Visual and UI implementation (`TDS-UI`)

| ID | Decision | Acceptance / Verification | Serves |
|---|---|---|---|
| TDS-UI-010 | The binding style source is the bundle at **`design/webguiExample/`** in this repository: `data/index.html`, `data/style.css`, `data/app.js`, plus `webGuiExample.md` for context. The implementer shall reuse CSS rules, layout patterns, and component idioms from that bundle; any divergence shall be documented. | Rendered pages link to the same CSS class names defined in `webguiExample/data/style.css` (spot-checked across M1, M2, M3, M4, M5, M8). Any deviation from the bundle is recorded in a project deviation log. | FR-UI-* (all), [BD §4] |
| TDS-UI-020 | The user GUI is **mobile-first**: viewport meta tag set, touch targets ≥ 44 px, single-column layout, readable at typical phone DPR. | Inherits [FDS FR-UI-010]. | FR-UI-010 |
| TDS-UI-030 | The admin GUI is **desktop-first**: multi-column tables and hover-style interactions are acceptable. It need not actively break on a phone, but mobile-optimisation is not required. | Inherits [FDS FR-ADM-040]. | FR-ADM-040 |
| TDS-UI-040 | The QR-sign output format is **HTML at minimum**. PDF output is optional polish; if PDF is generated, it shall be from the same HTML template (e.g. via headless browser print or a PHP HTML-to-PDF library). | The greenhouse detail page contains an `<img>` (or inline `<svg>`) carrying the QR PNG/SVG bytes; if a PDF download is implemented, the rendered PDF embeds the same image at the same on-page position. | FR-GH-070, FR-UI-060 |
| TDS-UI-050 | Photo upload uses the standard HTML pattern `<input type="file" accept="image/*" capture="environment">` so the phone camera or gallery picker is offered as appropriate. | The observation form's file `<input>` element carries both `accept="image/*"` and `capture="environment"` attributes (HTML inspection of the rendered form). | FR-REC-040, FR-REC-070 |
| TDS-UI-060 | QR codes for greenhouse signs are generated server-side using the **`endroid/qr-code`** Composer package and **rendered inline on every greenhouse detail page** in the admin GUI without any admin action (FR-GH-070). PNG output is used for HTML rendering; SVG may be used where the embedding target (e.g. PDF) prefers vectors. Error-correction level: **M** (medium) — balances density and reliability for poster-distance scanning. To avoid regenerating on every page render, the QR image may be **cached server-side** at `<photo_root>/_qr/<gh-id>.png` and invalidated when the greenhouse ID, the `public_base_url`, or the endroid/qr-code library version changes; a fresh per-render generation is also acceptable at this scale (≤ 100 greenhouses). | `composer.json` requires `endroid/qr-code`; opening any greenhouse detail page in the admin GUI renders the QR inline with no admin action. If caching is enabled, the file `<photo_root>/_qr/<gh-id>.png` exists after the first render. Inherits [FDS FR-GH-070] for the visible "no Generate button" property. | FR-GH-070, TDS-UI-040 |
| TDS-UI-070 | The Step-2 offline-capture requirement (FR-REC-080) is implemented as a **Service Worker + IndexedDB** pattern. A service worker registered from the user GUI intercepts the observation-submit POST when the network is unavailable, persists the payload to an IndexedDB object store (`pending_observations`), and replays the queue when the worker observes the network coming back. The user sees an "offline — queued" indicator that resolves to "synced" once replay completes. The service worker is scoped per TDS-UI-140. | Inherits [FDS FR-REC-080]. | FR-REC-080 |
| TDS-UI-080 | The **privacy notice** (FR-SEC-040) is a single static HTML template `privacy.php` reachable at `/privacy` (user-side, linked from mock M2 footer) and from the admin login page footer. Content is Dutch, with section headings: "Welke gegevens", "Grondslag", "Bewaartermijn", "Uw rechten", "Contact". The admin-contact field is filled from a new optional `config.php` key `admin_contact` (free-text string, typically an email address). | Inherits [FDS FR-SEC-040]. | FR-SEC-040 |
| TDS-UI-090 | The app shall ship Dutch **error-page templates** for HTTP `404`, `403`, `413` (payload too large — photo upload), `429` (rate-limited admin login), and `5xx` (generic server error). Each page renders the project header/footer style (TDS-UI-010) and a single short Dutch explanation. The `5xx` template shall **not** leak stack traces or file paths to the client; PHP `display_errors` is forced off at runtime regardless of the host's `php.ini` (`ini_set('display_errors', '0')` in the entry-point). PHP errors are logged via `error_log()` to the file path in a new optional `config.php` key `error_log_path` (defaults to PHP's `ini_get('error_log')`). | Triggering each of 404 / 403 / 413 / 429 / 500 returns the Dutch template rendered with header/footer; the 500 body contains zero file-path or stack-trace substrings; PHP's `display_errors` is `0` at runtime (introspected via `phpinfo()` or `ini_get`). | FR-INST-* (production hardening); polish |
| TDS-UI-100 | **Post/Redirect/Get** (PRG): every state-changing POST (observation submit, handle change, admin edit/delete, settings change, etc.) responds with HTTP **303 See Other** to a GET URL on success. Form pages never render in response to a POST. This prevents the "do you want to resubmit?" browser dialog on refresh and avoids duplicate observations. | Each documented state-changing POST returns HTTP **303** on success with a `Location` header pointing at a GET URL. Refreshing the resulting page issues a GET and does not re-execute the action (verified by row-count before/after the refresh). | FR-REC-020, FR-OBS-030, FR-OBS-040, FR-USR-030, FR-IDU-050, FR-IDU-060 |
| TDS-UI-110 | **Dutch form-validation messages**: every server-side validation failure (empty required field, length out of bounds, malformed value, uniqueness collision, format mismatch) returns the form template re-rendered with the user's entered values preserved and a Dutch error message above the offending field. No browser-default validation strings are surfaced. A shared `lang/nl.php` array holds the canonical strings (e.g. `'handle_too_long' => 'Naam mag maximaal 40 tekens zijn.'`) so the strings are reviewable in one place. | Submitting an invalid handle (e.g. 50 chars) re-renders the form with the user's entered value preserved and a Dutch error sourced from `lang/nl.php` above the input. `grep -E "validate|error" src/` shows all error strings come via the language file, none are hard-coded inline. | FR-UI-070 |
| TDS-UI-120 | **App-version footer**: the admin GUI renders the deployed app version (read from a `VERSION` file at the application root, or from `composer.json` `version`) in a small footer on every admin page. The user GUI does **not** show the version (operators don't care; reduces fingerprinting from the public side). The health endpoint (FR-INST-060) reports the same version string. | Every admin page's footer contains the version string read from `VERSION`/`composer.json`. The user GUI's rendered HTML contains zero occurrences of the version string. The `/health` JSON body's `version` field equals the footer value. | FR-INST-060; operational visibility |
| TDS-UI-130 | **Multi-tab `current_greenhouse_id` semantics**: when an operator opens the app in two tabs against two different greenhouses (e.g. `/AABB/` and `/CCDD/`), the server-side `user.current_greenhouse_id` records the **most recent visit** (last-writer-wins). Each tab's outbound observation submission carries the greenhouse-id from its own URL path, not the stored `current_greenhouse_id`, so observations from the older tab still attach to the correct greenhouse. The stored value is only used by FR-GH-080 (root-URL redirect) and FR-IDU-040 (greeting context). | After visiting `/AABB/` and then `/CCDD/` in two tabs of one device, `user.current_greenhouse_id = (id of CCDD)`. Submitting an observation from the `/AABB/` tab still produces a row with `greenhouse_id = (id of AABB)` (the tab's URL wins for the submission's gh-id). | FR-GH-020, FR-GH-040, FR-GH-080 |
| TDS-UI-140 | **Service Worker scope `/`** (TDS-UI-070): the SW registration uses `scope: '/'` rather than `/<gh-id>/` so it survives operator switches between greenhouses on the same device. The SW's offline-queue payload carries the greenhouse-id of the originating tab so replayed submissions land at the correct greenhouse-scoped endpoint. | Inspecting the registered service worker in DevTools shows `scope: '/'`. An offline submission from `/AABB/` followed by a switch to `/CCDD/` and going back online replays the submission against `/AABB/observation/new` (not against `/CCDD/...`). | FR-REC-080, FR-GH-040 |
| TDS-UI-150 | **HTTP 413 mapping**: photo uploads larger than `upload_max_filesize` are rejected by the web server **before** PHP runs, producing a server-default 413. The app's nginx/Apache config snippet (published in operator docs §12.2) sets a `proxy_intercept_errors`/`ErrorDocument` rule that routes 413 to the Dutch `/error/413` template (TDS-UI-090) so the operator sees a friendly message rather than a bare server error. The Dutch text names the 8 MB limit and suggests sharing a lower-resolution photo. | Uploading a 10 MB photo against the recommended nginx config returns HTTP 413 with the Dutch template body (not nginx's default error page); the body contains the literal "8 MB" string. | FR-REC-070, TDS-STO-060 |

## 11. Traceability matrix (FR → TDS)

Inverse of the "Serves" column above — for each FR that needed implementation choices, the TDS items that realise it.

| FR ID | TDS items |
|---|---|
| FR-INST-010 | TDS-CFG-010, TDS-CFG-020, TDS-CFG-060 |
| FR-INST-020 | TDS-CFG-030 |
| FR-INST-030 | TDS-CFG-040, TDS-CFG-050, TDS-CFG-070 |
| FR-INST-040 | TDS-STK-020, TDS-STO-010, TDS-STO-050 |
| FR-INST-050 | TDS-STO-020, TDS-STO-080 |
| FR-INST-070 | TDS-STO-130 (launch-taxonomy seed) |
| FR-INST-080 | TDS-CFG-080 (validate-config helper) |
| FR-REC-100 | TDS-STO-120 (synchronous photo unlink) |
| FR-ADM-070 | TDS-AUTH-050 (regenerate-id on login), TDS-AUTH-120 (logout endpoint) |
| FR-GH-010, FR-GH-060 | TDS-URL-050 (greenhouse-id format validation) |
| FR-EXP-010 | (also TDS-CSV-060 empty-result behaviour) |
| FR-IDU-020 | TDS-AUTH-010, TDS-AUTH-020, TDS-AUTH-030 |
| FR-IDU-030 | TDS-AUTH-030 |
| FR-USR-030 | TDS-AUTH-040 |
| FR-REC-040 | TDS-UI-050 |
| FR-REC-050 | TDS-CFG-070 |
| FR-REC-070 | TDS-STO-030, TDS-STO-040, TDS-STO-050, TDS-STO-060, TDS-STO-070, TDS-UI-050 |
| FR-REC-080 | TDS-UI-070 |
| FR-REV-010, FR-REV-020 | TDS-STO-070 (thumbnails feed the list views) |
| FR-GH-020 | TDS-URL-010 |
| FR-GH-070 | TDS-URL-040, TDS-UI-040, TDS-UI-060 |
| FR-ADM-010 | TDS-URL-020 |
| FR-ADM-020 | TDS-AUTH-070 (defence-in-depth), TDS-AUTH-080 (hash storage and verification) |
| FR-ADM-030 | TDS-AUTH-050 |
| FR-ADM-040 | TDS-UI-030 |
| FR-ADM-050 | TDS-AUTH-060 |
| FR-SEC-010 | TDS-AUTH-100 |
| FR-SEC-020 | TDS-AUTH-080, TDS-AUTH-090 |
| FR-SEC-030 | TDS-AUTH-080 |
| FR-SEC-040 | TDS-UI-080 |
| FR-SEC-050 | TDS-CSV-050 |
| FR-SEC-060 | TDS-STO-090 |
| FR-USR-060 | TDS-STO-090 (orphan-user cleanup in the same sweep) |
| FR-IDU-010, FR-IDU-050 | TDS-AUTH-110 (handle uniqueness) |
| FR-GH-060 | TDS-STO-100 (delete safeguard) |
| FR-REC-090 | TDS-STO-110 (WAL concurrency) |
| FR-EXP-010 | TDS-CSV-010, TDS-CSV-030 |
| FR-EXP-020 | TDS-CSV-010, TDS-CSV-020, TDS-CFG-070 |
| FR-EXP-050 | TDS-CSV-040 |
| FR-UI-010 | TDS-UI-020 |
| FR-UI-* (all) | TDS-UI-010, TDS-STK-080 |
| FR-UI-060 | TDS-UI-040 |
| (architectural — no single FR) | TDS-STK-010..080 |

## 12. Operator deployment notes (non-normative)

This section collects deployment guidance for the administrator. None of it is mandatory for FDS conformance — it documents the surrounding environment the FDS assumes (A1: transport security and host hardening are the administrator's responsibility).

### 12.1 Recommended HTTP response headers

The FDS deliberately leaves HTTP security headers to the web-server configuration. The administrator is **strongly** recommended to add these to every response served by the app, via Apache `Header` directives or nginx `add_header` directives:

| Header | Recommended value | Why |
|---|---|---|
| `Content-Security-Policy` | `default-src 'self'; img-src 'self' data:; style-src 'self' 'unsafe-inline'; script-src 'self'` | Restricts the browser's loading sources. Hardens against XSS. |
| `X-Content-Type-Options` | `nosniff` | Stops MIME sniffing. |
| `X-Frame-Options` | `DENY` | Blocks click-jacking the admin GUI. Or use the CSP `frame-ancestors 'none'` directive. |
| `Referrer-Policy` | `same-origin` | Prevents URL leaks to third parties. |
| `Strict-Transport-Security` | `max-age=31536000; includeSubDomains` (HTTPS deployments only) | After the first HTTPS visit, the browser enforces HTTPS for one year. |
| `Permissions-Policy` | `camera=(), geolocation=(), microphone=()` (relax `camera=()` to `camera=(self)` on the photo-upload page) | Disables sensor APIs where the app does not need them. |

### 12.2 PHP runtime recommendations

Set in `php.ini` or the host's PHP-FPM pool config:

- `expose_php = Off` (no `X-Powered-By` header)
- `session.cookie_httponly = 1`, `session.cookie_samesite = Lax`
- `upload_max_filesize = 8M`, `post_max_size = 12M` (matches the photo limits in TDS-STO-060)
- `default_charset = "UTF-8"`

The app itself forces `ini_set('display_errors', '0')` in its entry-point (TDS-UI-090) so a misconfigured host does not leak stack traces to operators.

### 12.3 Backup

See the closing note in §4. SQLite `.backup` plus `rsync` of `<photo_root>`, with a retention policy that fits the host.

---

*End of TDS. Any new implementation choice surfaced during build shall be added here under a fresh ID; FR-level changes go in the FDS, not here.*
