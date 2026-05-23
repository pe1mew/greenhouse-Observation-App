# Changelog

All notable changes to this project are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/), and this project adheres to [Semantic Versioning](https://semver.org/).

---

## [Unreleased] — 2026-05-23  Initial design specification

First operator-approved revision of the design documents. No implementation has started.

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

---

*Future implementation releases will follow Semantic Versioning. Step 1 will land as `0.1.0`; Step 2 features arrive as further `0.x.y` releases until the spec set is complete, at which point a `1.0.0` release is cut.*
