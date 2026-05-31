# Greenhouse Observation App

A mobile-first PHP web application for greenhouse operators to record observations on a phone: pick a category, pick a tag, done. Optional severity, note, and photo. Multi-greenhouse from day zero via QR code. Administrator backend for taxonomy, users, observations, and CSV export.

Designed for the **herenboeren** (community-supported agriculture) context: low-friction recording during a greenhouse walk, no app install, runs on conventional PHP hosting. Dutch UI, GDPR-aware.

## Status

**v1.0.0-rc.1 — release candidate, in production since 2026-05-26.**

Step 1 (core recorder, admin GUI, taxonomy) and most of Step 2 are live. Offline capture (Service Worker + IndexedDB) is the remaining major Step 2 feature.

- Functional design: operator-approved 2026-05-23
- First production deploy: 2026-05-26 (`rfsee.net/webapp`, Docker + nginx reverse proxy)

See [`changelog.md`](changelog.md) for the release notes and [`manual/`](manual/) for the end-user and administrator handbooks.

## What it does

- Operator scans a per-greenhouse QR code, registers a self-asserted handle once, then records observations in two taps from the home screen.
- **Eight categories** — 5 strategic (*Welzijnscheck, Omgeving, Gewas, Sensor/regeling, Onderhoud*) plus 3 climate-focused (*Temperatuur, Luchtvochtigheid — te hoog, Luchtvochtigheid — te laag*) — each with its own set of tags. The taxonomy is editable from the admin GUI but the internal keys never change, so analyst scripts keep working across renames. See [`design/categorien.md`](design/categorien.md) for the full reference.
- Optional severity (1..5), free-text note, and one photo per observation. Photos **auto-upload on file selection** so they survive a closed browser tab.
- Administrator manages greenhouses (incl. printable QR-code signs), users, observations, taxonomy, and exports the dataset as a European-Excel CSV.
- Privacy-by-design: no live-data display, no controller comms, no third-party trackers, automatic EXIF stripping on uploaded photos, configurable retention (default 365 days), GDPR self-export.

## Companion to the greenhouse controller

The observation app is fully decoupled from the [Greenhouse Controller](https://github.com/pe1mew/greenhouse-Controller) — they never communicate at runtime. Sensor data from the controller and human observations from this app are joined offline by an analyst.

By convention, the greenhouse ID in the observation app is the **last 2 bytes of the paired controller's MAC address in uppercase hex** (e.g. `5E3F`), so the same identifier makes sense in both worlds. Deployments without a paired controller may assign any unique 4-hex value.

## Architecture (one paragraph)

PHP 8.1+ backend (runs in a PHP 8.3-FPM Docker container in production — the application code itself stays 7.4-compatible except for the QR-code dependency), SQLite single-file database located outside the web root, no external services. Front end is plain PHP templating with a small vanilla-JS layer for photo auto-upload. Deployable on any conventional PHP host (shared LAMP/LEMP, managed PHP host) or — as in production — behind nginx as a reverse proxy to PHP-FPM. Mobile-first user GUI, desktop-first admin GUI at a configurable URL. Service Worker + IndexedDB for offline recording is the remaining Step 2 feature.

## Repository structure

```
greenhouse-Observation-App/
│
├── design/                                ← Design documents (operator-approved)
│   ├── operatorObservationStrategy.md     ← Two-path architecture, UX mock-ups M1–M9, rollout
│   ├── behaviouralDescription.md          ← Binding behavioural rules
│   ├── functionalRequirements.md          ← FDS — what to build
│   ├── technicalDesignSpecification.md    ← TDS — how to build it
│   ├── implementationPlan.md              ← Step-by-step rollout plan
│   ├── categorien.md                      ← Canonical taxonomy reference (8 categories + tags)
│   ├── testPlan.md                        ← Per-FR acceptance test specification
│   └── webguiExample/                     ← Binding visual style source (HTML/CSS/JS)
│
├── manual/                                ← End-user and operator handbooks
│   ├── gebruikersHandleiding.md           ← User manual (Dutch, "je"-vorm)
│   ├── gebruikersHandleiding.pdf          ← Printable PDF (auto-generated)
│   ├── administratorManual.md             ← Administrator manual (English)
│   ├── administratorManual.pdf            ← Printable PDF
│   ├── md2pdf.py                          ← Branded markdown → PDF converter
│   └── images/                            ← Screenshots used in the manuals
│
├── src/                                   ← PHP application code (controllers, models, helpers)
├── views/                                 ← PHP templates (user + admin GUI)
├── lang/                                  ← Translation files (nl.php is canonical)
├── migrations/                            ← SQLite schema migrations (applied on bootstrap)
├── assets/                                ← Static CSS / icons
│
├── index.php                              ← Front controller
├── template_config.php                    ← Configuration template (copy to config.php)
├── composer.json / composer.lock          ← PHP dependency manifest
├── Dockerfile                             ← Production PHP 8.3-FPM image
├── docker-compose.yml                     ← One-service compose for production
├── nginx.conf.example                     ← Reverse-proxy example (apex + subpath /webapp/)
├── .htaccess                              ← Optional Apache fallback
│
├── README.md                              ← This file
├── LICENSE                                ← Canonical licence text
├── license.md                             ← Human-readable licence explanation
├── changelog.md                           ← Release notes
├── contributing.md                        ← How to contribute
├── code_of_conduct.md                     ← Community standards
├── testplan.md                            ← Most recent smoke-test results
└── VERSION                                ← Current version, single line
```

## Getting started

### As an end user (operator)

Read the user manual: [`manual/gebruikersHandleiding.md`](manual/gebruikersHandleiding.md) (Dutch, with 12 screenshots and an FAQ). For a printable copy, see [`manual/gebruikersHandleiding.pdf`](manual/gebruikersHandleiding.pdf).

### As an administrator

Read the administrator manual: [`manual/administratorManual.md`](manual/administratorManual.md) (English) — or the PDF version, [`manual/administratorManual.pdf`](manual/administratorManual.pdf).

### As a developer or reviewer

Read the design documents in this order:

1. [`design/operatorObservationStrategy.md`](design/operatorObservationStrategy.md) — *why* the app exists, the two-path data model, the operator experience (mock-ups M1–M9), and rollout phasing.
2. [`design/behaviouralDescription.md`](design/behaviouralDescription.md) — the binding behavioural rules that drove the FDS.
3. [`design/functionalRequirements.md`](design/functionalRequirements.md) — the FDS. Every FR carries an ID, a "shall" statement, a SMART acceptance criterion, a rollout band (Step 1 / Step 2), and a MoSCoW priority.
4. [`design/technicalDesignSpecification.md`](design/technicalDesignSpecification.md) — the TDS. Every implementation choice with back-references to the FR IDs it serves.
5. [`design/implementationPlan.md`](design/implementationPlan.md) — the step-by-step rollout plan that drove Step 1.
6. [`design/categorien.md`](design/categorien.md) — the canonical taxonomy reference (8 categories + tags).
7. [`design/testPlan.md`](design/testPlan.md) — per-FR acceptance tests; [`testplan.md`](testplan.md) at the repository root records the most recent smoke-test run.

For visual style, see [`design/webguiExample/`](design/webguiExample/) — the bundle of `index.html`, `style.css`, and `app.js` that the implementation reuses for CSS rules and component idioms.

### Generating PDFs from the manuals

The `manual/md2pdf.py` script converts any of the markdown manuals to a branded PDF (Kas Controller header, RFSee footer, page numbers) using a headless Microsoft Edge instance.

```sh
cd manual
python md2pdf.py gebruikersHandleiding.md
python md2pdf.py administratorManual.md
```

The script auto-detects the document language from the `**Versie:**` / `**Version:**` header and localises the footer label accordingly (`pagina` / `page`). Dependencies: `markdown`, `requests`, `simple-websocket`, plus Microsoft Edge installed locally.

## Rollout

Per [OS §6]:

- **Step 1 — delivered.** Greenhouse administration with QR generation, user identification by cookie, two-tap recording flow, admin user/observation/taxonomy management, CSV export, privacy notice, CSRF protection, admin password setup wizard.
- **Step 2 — partially delivered.** Notes, photos with EXIF stripping (✓), photo auto-upload on file selection (✓), editable recent-observations list with 24-hour edit window (✓), multi-greenhouse user-list filter (✓), audit log (✓), GDPR self-export (✓), configurable retention sweep (✓). **Offline capture** (Service Worker + IndexedDB) is the remaining major Step 2 feature.

Each FR is tagged with its rollout band in the FDS; §7.1 of the FDS contains the full mapping.

## License

See [license.md](license.md) for the human-readable summary and [`LICENSE`](LICENSE) for the canonical text.

- **Software**: source-available, non-commercial. Free to use and modify for personal and non-commercial purposes; redistribution and commercial use are not permitted.
- **Documentation and design files**: [Creative Commons Attribution-NonCommercial-NoDerivatives 4.0 International License (CC BY-NC-ND 4.0)](https://creativecommons.org/licenses/by-nc-nd/4.0/).

<a rel="license" href="https://creativecommons.org/licenses/by-nc-nd/4.0/"><img alt="Creative Commons License" style="border-width:0" src="https://i.creativecommons.org/l/by-nc-nd/4.0/88x31.png" /></a>

## Disclaimer

This project is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
