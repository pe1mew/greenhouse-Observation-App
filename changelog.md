# Changelog

All notable changes to this project are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/), and this project adheres to [Semantic Versioning](https://semver.org/).

---

## [1.0.0-rc.1] — 2026-05-26  First release candidate, deployed to production

Step 1 implementation complete and live; most of Step 2 has also landed. Deployed at `rfsee.net/webapp` behind nginx reverse-proxying to a PHP 8.3-FPM Docker container. Offline capture (Service Worker + IndexedDB) remains as the last major Step 2 feature.

### Added — runtime (Step 1)

- Greenhouse administration with QR-code generation (`endroid/qr-code` 4.x) and printable QR signs.
- Cookie-based user identification — opaque 128-bit random token; one-time handle registration per browser; per-greenhouse user list.
- Two-tap observation recording flow over the active taxonomy (per [FR-REC]/[FR-UI]).
- Admin GUI for greenhouses, users, observations, and taxonomy categories/tags (Dutch UI per FR-UI-070).
- European-Excel CSV export (`;` separator, UTF-8 BOM, CRLF) — both per-greenhouse and full dataset.
- Privacy notice page reachable from the footer of every screen; CSRF synchronizer-token protection on all state-changing routes.
- Admin password setup wizard on first login; bcrypt-hashed password stored in the database (never in `config.php`).

### Added — runtime (Step 2, mostly delivered)

- Per-observation free-text note and one photo (JPEG/PNG/WebP, max 8 MB), with server-side MIME validation via PhotoHandler.
- Automatic EXIF stripping on photo upload (privacy).
- **Photo auto-upload on file selection** — dedicated `POST /<gh-id>/observation/<id>/photo` endpoint (CSRF via form field or `X-CSRF-Token` header). Photos commit the moment they are picked, so they survive a closed browser tab or a forgotten "Opslaan" tap. Documented as FR-REC-040 / TDS-UI-160.
- Editable recent-observations list with a 24-hour edit window (severity, note, photo add/replace/delete); observations become read-only afterwards.
- Multi-greenhouse user-list filter in the admin GUI.
- Audit log for admin actions.
- GDPR self-export: per-user ZIP containing CSV + photos, downloadable from the user's Instellingen page.
- Configurable retention sweep — default 365 days; older observations and their photos are removed automatically.

### Added — taxonomy

- 8 active categories seeded: 5 strategic (`welzijnscheck`, `omgeving`, `gewas`, `sensor`, `onderhoud`) and 3 climate-focused (`temperatuur`, `luchtvochtigheid_hoog`, `luchtvochtigheid_laag`). Climate categories added via `migrations/0002_climate_categories.sql`.
- `Database::seedTaxonomy()` made idempotent — uses `INSERT OR IGNORE` per row instead of bailing on any existing category.

### Added — deployment

- `Dockerfile` — `php:8.3-fpm` base; installs `libsqlite3-dev`, `pdo_sqlite`, `gd`, `zip`, `opcache`; Composer 2 from the official image; PHP overrides for `upload_max_filesize` / `post_max_size`.
- `docker-compose.yml` — single PHP-FPM service running as `www-data` (UID 33); listens on `127.0.0.1:9083`; bind-mounts code at `/var/www/html` and persistent data at `/var/www/data`.
- `nginx.conf.example` — apex deployment example **plus** sub-path (`/webapp/`) reverse-proxy example with FastCGI param overrides (`SCRIPT_FILENAME`, `SCRIPT_NAME`, `DOCUMENT_ROOT`, `PATH_INFO`), `client_max_body_size 12M`, and `error_page 413` direction.
- Sub-path PHP routing: `Router` is sub-path aware via `dirname($_SERVER['SCRIPT_NAME'])`; nginx overrides `SCRIPT_NAME` to `/webapp$fastcgi_script_name`.
- Production deploy chain: Ubuntu host (PHP 7.4 stays for sibling sites) → nginx reverse-proxy → PHP 8.3-FPM container → SQLite, all wired through bind-mounts (`www-data` UID 33 aligns host and container).
- `/error/<code>` route added to `Router` for nginx `error_page` mapping.

### Added — manuals

- `manual/gebruikersHandleiding.md` — Dutch user manual in "je"-vorm (informal, herenboeren-community register), with 12 numbered figures, a 9-question FAQ, a Privacy section, a Contact-with-admin section, and a "Tot slot — over verwachting" cultural-framing section. Also available as `gebruikersHandleiding.pdf`.
- `manual/administratorManual.md` — English administrator manual covering installation, first-time setup, the admin GUI (dashboard, greenhouses, taxonomy, observations, users, exports, audit log), routine operations, and troubleshooting. Available as `administratorManual.pdf`.
- `manual/md2pdf.py` — Markdown → branded PDF converter. Uses Microsoft Edge headless via the Chrome DevTools Protocol. Auto-numbers `Figuur #:` placeholders, extracts the version from `**Versie:**` / `**Version:**` headers (semver with hyphens supported), and auto-localises the footer page label (`pagina` for Dutch documents, `page` for English).

### Added — design documents

- `design/implementationPlan.md` — step-by-step rollout plan that drove the Step 1 implementation.
- `design/categorien.md` — canonical taxonomy reference (8 categories with their tag sets and intended usage).
- `design/testPlan.md` — per-FR acceptance test specification.

### Notable design decisions during implementation

- **Docker over Ubuntu native PHP 8** — the Ondrej PPA for `focal` is no longer maintained, so a PHP 8.3-FPM container was chosen instead of upgrading the host. The host stays on PHP 7.4 for other rfsee.net sites.
- **Auto-upload as a separate REST endpoint** — `POST /<gh-id>/observation/<id>/photo` is decoupled from the form-driven observation update so the user can compose freely before committing notes and severity, while the photo is no longer at risk of being lost.
- **Sub-path mounting via nginx alias + SCRIPT_NAME override** — keeps the PHP router unchanged across apex and sub-path deployments by deriving the base path from `$_SERVER['SCRIPT_NAME']`.
- **`/manual/raw/` excluded from version control** — operator-supplied raw screenshots (timestamped filenames) live there for cropping; only the final `manual/images/*.png` versions are committed.

### Fixed

- `Database::seedTaxonomy()` was bailing on any existing category, blocking taxonomy migrations on existing databases. Replaced with per-row `INSERT OR IGNORE` + `SELECT BY KEY` for `catId`.
- Nested `<form>` elements in `views/admin/observation_edit.php` (delete-photo form inside the main edit form — invalid HTML) — extracted to a sibling form.

### Documentation

- Repository root documents (`README.md`, `changelog.md`, `contributing.md`) updated to reflect the release-candidate state, the new directory layout (`manual/`, `src/`, `views/`, `lang/`, `migrations/`, Docker files), and to link the manuals and PDFs.
- `.gitignore` extended with `adminPassWd.txt` and `manual/*.html` (generated by `md2pdf.py`).

---

## [Unreleased] — 2026-05-23  Initial design specification

First operator-approved revision of the design documents. No implementation had started at this point.

### Added — design (`design/`)

- `operatorObservationStrategy.md` — strategy: two-path architecture (observation app fully decoupled from the greenhouse controller; joined offline by the analyst), five-category tag taxonomy, UX mock-ups M1–M9, Step 1 / Step 2 rollout, hosting and multi-greenhouse decisions.
- `behaviouralDescription.md` — binding behavioural rules: PHP + SQLite outside the document root, Apache / nginx, configuration file rules, unlimited-cookie user identification, separate admin URL, admin password setup wizard, taxonomy editable from admin GUI.
- `functionalRequirements.md` — Functional Design Specification (FDS):
  - 12 functional-requirement areas (`FR-INST`, `FR-IDU`, `FR-REC`, `FR-REV`, `FR-GH`, `FR-ADM`, `FR-USR`, `FR-OBS`, `FR-TAX`, `FR-EXP`, `FR-UI`, `FR-SEC`).
  - Every requirement carries an ID, a "shall" statement, notes, a SMART acceptance criterion, a Step 1 / Step 2 rollout band, and a MoSCoW priority.
  - 4 assumptions (A1–A4) — transport security as admin responsibility, cookie as sole credential, frozen-yet-extensible taxonomy, no controller communication — plus A5 (single timezone) and A6 (browser scope, modern evergreen).
  - Section 7 maps every FR to both views: rollout band and MoSCoW priority.
  - Section 8 lists what is explicitly out of scope.
- `technicalDesignSpecification.md` — Technical Design Specification (TDS):
  - 11 implementation-decision areas (`TDS-STK`, `TDS-STO`, `TDS-DM`, `TDS-CFG`, `TDS-URL`, `TDS-AUTH`, `TDS-CSV`, `TDS-UI`) plus a traceability matrix (`§11`) and non-normative operator deployment notes (`§12`).
  - Locked choices: PHP 7.4+, SQLite via PDO_SQLite, plain-PHP templating, opaque 128-bit cookie token, path-segment greenhouse ID `^[0-9A-F]{4}$` (uppercase hex, by convention the last 2 bytes of the paired controller MAC), European Excel CSV dialect (`;`, UTF-8 BOM, CRLF), Service Worker + IndexedDB offline queue, `endroid/qr-code` for QR generation, hashed admin password in DB, CSRF synchronizer-token pattern, SQLite WAL mode.
  - Every TDS item back-references the FR IDs it serves.
- `webguiExample/` — binding visual style source bundle (`index.html`, `style.css`, `app.js` plus a short README). Referenced from the TDS as the source for CSS rules and component idioms.

### Added — repository

- `README.md` — project overview, status, architecture summary, repository structure, design-doc reading order, rollout summary, license summary.
- `LICENSE` — dual-licence: source-available non-commercial for software (when implemented), CC BY-NC-ND 4.0 for design documents.
- `license.md` — human-readable explanation of the dual licence.
- `contributing.md` — contribution guidelines adapted to the design-driven workflow.
- `code_of_conduct.md` — community standards.
