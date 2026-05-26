# Greenhouse Observation App

A mobile-first PHP web application for greenhouse operators to record observations on a phone: pick a category, pick a tag, done. Optional severity, note, and photo. Multi-greenhouse from day zero via QR code. Administrator backend for taxonomy, users, observations, and CSV export.

Designed for the **herenboeren** (community-supported agriculture) context: low-friction recording during a greenhouse walk, no app install, runs on conventional PHP hosting. Dutch UI, GDPR-aware.

## Status

**Step 1 implementation in progress — 2026-05-26.**

Design phase complete and operator-approved (2026-05-23). Repository scaffold in place; bootstrap and application code in progress.

## What it does

- Operator scans a per-greenhouse QR code, registers a self-asserted handle once, then records observations in two taps from the home screen.
- Five categories (Wellbeing, Environment, Crop, Sensor/control, Maintenance) with up to six tags each — the taxonomy is editable from the admin GUI but stable across renames.
- Optional severity (1..5), free-text note, and one photo per observation (Step 2).
- Administrator manages greenhouses (incl. printable QR-code signs), users, observations, taxonomy, and exports the dataset as CSV.
- Privacy-by-design: no live-data display, no controller comms, no third-party trackers. GDPR self-export and configurable data retention.

## Companion to the greenhouse controller

The observation app is fully decoupled from the [Greenhouse Controller](https://github.com/pe1mew/greenhouse-Controller) — they never communicate at runtime. Sensor data from the controller and human observations from this app are joined offline by an analyst.

By convention, the greenhouse ID in the observation app is the **last 2 bytes of the paired controller's MAC address in uppercase hex** (e.g. `5E3F`), so the same identifier makes sense in both worlds. Deployments without a paired controller may assign any unique 4-hex value.

## Architecture (one paragraph)

PHP 7.4+ backend, SQLite single-file database (located outside the web root), no external services. Deployable on any conventional PHP host (shared LAMP/LEMP, managed PHP host, or self-hosted VPS). Runs identically behind Apache (≥ 2.4) and nginx (≥ 1.18). Mobile-first user GUI, desktop-first admin GUI at a configurable URL. Service Worker + IndexedDB for offline recording (Step 2).

## Repository structure

```
greenhouse-Observation-App/
│
├── design/
│   ├── operatorObservationStrategy.md   ← Two-path architecture, UX mock-ups M1–M9, rollout
│   ├── behaviouralDescription.md        ← Binding behavioural rules
│   ├── functionalRequirements.md        ← FDS — what to build (operator-approved)
│   ├── technicalDesignSpecification.md  ← TDS — how to build it (operator-approved)
│   └── webguiExample/                   ← Binding visual style source (HTML/CSS/JS)
│
├── README.md
├── LICENSE
├── license.md
├── changelog.md
├── contributing.md
└── code_of_conduct.md
```

No firmware/, no PHP application code yet — the implementation starts from these four design documents.

## Getting started

Read the design documents in this order:

1. [`design/operatorObservationStrategy.md`](design/operatorObservationStrategy.md) — *why* the app exists, the two-path data model, the operator experience (mock-ups M1–M9), and rollout phasing.
2. [`design/behaviouralDescription.md`](design/behaviouralDescription.md) — the binding behavioural rules that drove the FDS.
3. [`design/functionalRequirements.md`](design/functionalRequirements.md) — the FDS. Every functional requirement carries an ID, a SMART acceptance criterion, a rollout band (Step 1 / Step 2), and a MoSCoW priority.
4. [`design/technicalDesignSpecification.md`](design/technicalDesignSpecification.md) — the TDS. Every implementation choice (PHP 7.4, SQLite, PDO_SQLite, opaque cookie token, European Excel CSV, Service Worker offline queue, …) with back-references to the FRs it serves.

For visual style, see [`design/webguiExample/`](design/webguiExample/) — the bundle of `index.html`, `style.css`, and `app.js` that the implementation reuses for CSS rules and component idioms.

## Rollout

Per [OS §6]:

- **Step 1** — the core recorder: greenhouse administration with QR generation, user identification by cookie, two-tap recording flow, admin user/observation/taxonomy management, CSV export, privacy notice, CSRF protection, admin password setup wizard.
- **Step 2** — polish: notes, photos with EXIF stripping, offline capture (Service Worker + IndexedDB), the editable recent-observations list with 24 h edit window, multi-greenhouse user-list filter, audit log, GDPR self-export, retention sweep.

Each FR is tagged with its rollout band in the FDS; §7.1 of the FDS contains the full mapping.

## License

See [license.md](license.md) for full details.

- **Software** (when implemented): source-available, non-commercial. Free to use and modify for personal and non-commercial purposes; redistribution and commercial use are not permitted.
- **Documentation and design files**: [Creative Commons Attribution-NonCommercial-NoDerivatives 4.0 International License (CC BY-NC-ND 4.0)](https://creativecommons.org/licenses/by-nc-nd/4.0/).

<a rel="license" href="https://creativecommons.org/licenses/by-nc-nd/4.0/"><img alt="Creative Commons License" style="border-width:0" src="https://i.creativecommons.org/l/by-nc-nd/4.0/88x31.png" /></a>

## Disclaimer

This project is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
