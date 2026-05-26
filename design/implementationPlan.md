# Implementation Plan — Greenhouse Observation App

| Field | Value |
|---|---|
| Document | Implementation Plan |
| Project | Greenhouse Observation App |
| Status | Draft — 2026-05-24 |
| Inputs | `functionalRequirements.md` (FDS), `technicalDesignSpecification.md` (TDS), `testPlan.md` (companion verification plan) |
| Scope | How the FDS + TDS gets built, deployed, tested, and rolled out across three environments. |

---

## 1. Purpose

Turn the operator-approved FDS and TDS into a working deployment by sequencing the work, running the verification, and managing the rollout from a development server to acceptance and finally to production.

The plan is **iteration-based**: every iteration delivers a vertical slice of working app + the corresponding test pass before the next iteration begins. The companion `testPlan.md` defines the verification work that gates each iteration.

## 2. References

| Ref | Document |
|---|---|
| [FDS] | `functionalRequirements.md` — what to build; every FR carries its own SMART acceptance criterion |
| [TDS] | `technicalDesignSpecification.md` — how to build it; every TDS item carries its own acceptance criterion (often `Inherits [FDS FR-X-NNN]`) |
| [TP]  | `testPlan.md` — the verification work that gates each iteration's "done" |

## 3. Environments

| # | Name | Host / URL | Role | Access |
|---|---|---|---|---|
| 1 | **Dev** | `192.168.20.232` (LAN) — Apache, `http://192.168.20.232/webapp/` | Build + integrate + HIL test during Phase 1 | SSH (Claude has key); LAN HTTP from operator and Claude |
| 2 | **Acceptance** | `pe1mew.nl` (public, HTTPS) — `https://pe1mew.nl/webapp/` (final path TBD) | Operator UAT after Phase 1 green-lights it | SSH (admin only); public HTTPS |
| 3 | **Production** | `rfsee.net` (public, HTTPS) — `https://rfsee.net/webapp/` (final path TBD) | The deployment the herenboeren community actually uses | SSH (admin only); public HTTPS |

Three identical deploys, three different lifecycles. Code that passes Phase 1 on Dev is the bit-for-bit identical archive promoted to Acceptance, and then promoted again to Production.

## 4. Phase 0 — Prerequisites

Done by a human before Phase 1 begins. Claude does not perform these.

| # | Prerequisite | Where | Verification |
|---|---|---|---|
| P0-1 | Apache ≥ 2.4 installed and serving from `/var/www/html/` (or equivalent) | Dev server | `curl -I http://192.168.20.232/` returns 200 or 403 (server reachable) |
| P0-2 | PHP **≥ 7.4** with extensions `pdo_sqlite`, `mbstring`, `fileinfo`, `gd` (or ImageMagick) — `libheif` recommended for HEIC | Dev server | `ssh ... "php -m"` lists all required extensions |
| P0-3 | Claude's SSH public key added to the deployment account's `~/.ssh/authorized_keys` | Dev server | Claude can `ssh deploy@192.168.20.232 "whoami"` |
| P0-4 | Two directories created with write permission for the deploy user: <br>• `<httproot>/webapp/` — the application root, web-accessible <br>• `<httproot>/../webapp_data/` — sibling to httproot, **outside** the document root, for SQLite + photos (per `FR-INST-040`, `TDS-STO-010, 050`) | Dev server | `ssh ... "ls -ld <paths>"` shows the right owner and `rwx` |
| P0-5 | Composer installed on the dev server (or vendored offline) | Dev server | `ssh ... "composer --version"` |
| P0-6 | `endroid/qr-code` Composer package and its peer deps available offline if the server has no outbound internet | Dev server | `ssh ... "ls vendor/endroid/qr-code"` after `composer install` |
| P0-7 | DNS for `pe1mew.nl` and `rfsee.net` resolves to the right hosts; HTTPS certificates valid | Public DNS | `curl -I https://pe1mew.nl/` and same for `rfsee.net` return 200 with valid TLS |
| P0-8 | Operational backup of the **production** SQLite + photos before any deploy to `rfsee.net` (manual, admin's responsibility per A1) | Production | SHA-256 of backup recorded |

Until P0-1 through P0-6 are confirmed, Phase 1 cannot start.

## 5. Phase 1 — Dev build (iterative)

Ten iterations. Each iteration builds a vertical slice that touches storage + URL + UI as needed for the FR set it covers, then runs the test pass for those FRs + TDS items. **No iteration starts until the previous one's test pass is green.**

| It. | Slice | FRs / TDS items delivered | Verification gate |
|---|---|---|---|
| **1** | **Bootstrap** — application skeleton, entry-point routing, plain-PHP templating, `template_config.php`, config-load with validation, setup-required page, health endpoint | `FR-INST-010, 020, 030, 060, 080`; `TDS-STK-010, 060, 070, 080`, `TDS-CFG-010..080` | [TP] §8.1 (INST cases) — green |
| **2** | **Storage** — SQLite open in WAL, schema migration framework, `0001_initial.sql` with all tables, taxonomy seed, retention-sweep skeleton (no-op until iteration 9) | `FR-INST-040, 050, 070`; `TDS-STK-020`, `TDS-STO-010, 020, 050, 080, 110, 130` | [TP] §8.1 (STO cases) — green |
| **3** | **Admin auth** — admin URL routing, setup wizard, hashed-password storage, rate limit, generic login error, session-regeneration on login, logout endpoint, CSRF tokens | `FR-ADM-010..050, 070`; `FR-SEC-010, 020, 030`; `TDS-URL-020`, `TDS-AUTH-050, 060, 070, 080, 090, 100, 120`, `TDS-AUTH-110` (schema only, used by operator side next) | [TP] §8.6 + §8.12 (ADM + SEC) — green |
| **4** | **Greenhouse administration** — Greenhouses CRUD, format validation (uppercase hex), delete safeguard, auto-render QR via `endroid/qr-code`, printable sign | `FR-GH-010, 050, 060, 070`; `FR-UI-060`; `TDS-URL-010, 030, 040, 050`, `TDS-STO-100`, `TDS-UI-040, 060` | [TP] §8.5 (GH admin slice) — green |
| **5** | **User identification + recording flow** — first-visit splash, handle registration with uniqueness, cookie issue, recent panel placeholder, two-tap flow M2 → M3 → M4 → M5, observation commit, greenhouse context bind, root-URL fallback | `FR-IDU-010..070`; `FR-REC-010, 020, 030, 090`; `FR-REV-010` (empty placeholder OK), `FR-GH-020, 030, 040, 080`; `FR-UI-010..050, 070`; `TDS-AUTH-010, 020, 030, 040, 110`, `TDS-UI-010, 020`, `TDS-UI-100` (PRG), `TDS-UI-110` (Dutch validation), `TDS-UI-130` (multi-tab) | [TP] §8.2 + §8.3 + §8.4 + §8.5 (user slice) + §8.11 (UI) — green |
| **6** | **Review (read-only)** — observation detail page, own-only enforcement, photo serving endpoint stub (returns 404 until iteration 10 enables photos) | `FR-REV-030, 060`; (`FR-REV-010` empty-state already done in iteration 5) | [TP] §8.4 — green |
| **7** | **Admin user / observation / taxonomy management** — list + view + modify + delete; archive + add tags/categories; greenhouse filter; observation filter | `FR-USR-010, 020, 030, 040`; `FR-OBS-010, 020, 030, 040`; `FR-TAX-010, 020, 030, 040, 050, 070`; `TDS-AUTH-040`, `TDS-STO-100` (already done) | [TP] §8.7 + §8.8 + §8.9 — green |
| **8** | **CSV export** — two-dialect selector, 12-column header, filters, filename pattern, empty-result behaviour | `FR-EXP-010, 020, 030`; `TDS-CSV-010, 020, 030, 060`, `TDS-CFG-070` (timestamp render) | [TP] §8.10 — green |
| **9** | **Operational hardening** — Dutch error pages (404/403/413/429/500), `display_errors=0`, error_log routing, app version footer, HTTP 413 mapping, audit log + retention sweep + orphan-user cleanup | `FR-ADM-060`, `FR-SEC-060`, `FR-USR-060`, `FR-OBS-050`; `TDS-UI-090, 120, 150`, `TDS-STO-090` | [TP] §8.6 + §8.7 + §8.12 — green |
| **10** | **Step 2 polish** — severity, note, photo upload + EXIF strip + thumbnail + cache headers, edit window (FR-REV-040, 050), full history M7, Service Worker offline queue, user GDPR self-export, tag count ≤ 6 enforcement | `FR-REC-010` (sev/note/photo), `FR-REC-040, 050, 060, 070, 080, 100`; `FR-REV-020, 040, 050`; `FR-TAX-060`; `FR-EXP-040, 050`; `FR-SEC-050`; `FR-USR-050`; `TDS-STO-030, 040, 060, 070, 120, 140`, `TDS-UI-050, 070, 080, 140`, `TDS-CSV-040, 050` | [TP] §8.3, §8.4, §8.5 (Step-2 cases), §8.10, §8.11, §8.12 — green |

After iteration 10 the dev deploy implements the full Step-1 + Step-2 FDS. The end-to-end scenarios from `[TP §9]` run last as the Phase-1 exit gate.

### 5.1 Iteration mechanics

Each iteration runs the same micro-cycle:

1. **Branch** — work happens on `iteration-<N>` branch off `main` (locally, on Claude's host).
2. **Build locally** — write PHP, SQL, JS, CSS, templates. Test syntactically.
3. **Deploy to dev** — `rsync` (or `scp`) the slice to `deploy@192.168.20.232:<httproot>/webapp/`.
4. **Run TP cases** — Claude executes the test cases for the iteration's FRs / TDS items per `[TP §8]`, using HIL probes (`curl`, `ssh ... sqlite3`, `ssh ... ls`, `ssh ... tail`).
5. **Capture results** — Pass / Fail / Blocked per TC, with the probe output, into `testResults/iteration-<N>.md`.
6. **Iterate on failures** — fix on Claude's host, redeploy, re-test the failed TCs only.
7. **Gate** — when every TC in the iteration's scope is Pass, merge `iteration-<N>` to `main` (manual, by the user). Then iteration N+1 starts.

The gating is strict: a failing TC blocks the iteration's merge until it's Pass.

### 5.2 Dev-server layout

```
/var/www/html/                    ← Apache DocumentRoot
└── webapp/                       ← the application root
    ├── public/                   ← the only files Apache serves directly
    │   ├── index.php             ← single entry point; all routes funnel through it
    │   ├── .htaccess             ← rewrites to index.php; denies *.php elsewhere
    │   ├── assets/               ← CSS/JS/images derived from webguiExample
    │   └── sw.js                 ← Service Worker (iteration 10)
    ├── src/                      ← PHP application code (autoloaded via composer)
    ├── views/                    ← plain-PHP templates
    ├── migrations/               ← 0001_initial.sql, …
    ├── lang/                     ← nl.php (Dutch strings)
    ├── vendor/                   ← Composer deps (endroid/qr-code, …)
    ├── composer.json
    ├── template_config.php       ← shipped
    └── config.php                ← NOT shipped; created on dev server by P0 or by hand

/var/www/webapp_data/             ← outside DocumentRoot (FR-INST-040)
    ├── observations.sqlite       ← the database
    └── photos/                   ← <YYYY>/<MM>/<obs-id>.<ext> (iteration 10)
```

The single-entry-point + denies-`.php`-elsewhere pattern in `.htaccess` is what keeps the app from accidentally exposing `migrations/`, `vendor/`, `views/`, or `src/` to direct HTTP requests.

## 6. Phase 2a — Acceptance to `pe1mew.nl`

Triggered once every Phase 1 iteration is green and the end-to-end scenarios from `[TP §9]` pass on Dev.

| # | Step | Who | How |
|---|---|---|---|
| 2a-1 | Build a release archive from `main`: `git archive --format=tar.gz --output=webapp-<version>.tar.gz main` | Claude / user | Tarball includes everything **except** `config.php`, `vendor/` is included pre-built |
| 2a-2 | SHA-256 the archive; record alongside the release tag | User | Manual |
| 2a-3 | `scp` archive to `pe1mew.nl`; extract into a fresh `webapp/` directory next to (but not overwriting) any existing deploy | Admin | Per [admin manual §"installatie"] |
| 2a-4 | Symlink-or-rename the new deploy into position | Admin | Atomic swap |
| 2a-5 | Run the FR-INST acceptance set (`TP §8.1` + smoke from `TP §9.1`) against `https://pe1mew.nl/webapp/` | Claude | Same HIL probes as Phase 1, just a different base URL |
| 2a-6 | Hand off to the operator for UAT — operator runs through `userManual.md` and the admin runs through `adminManual.md` end to end | Operators / admin | Sign-off when both manuals run cleanly |
| 2a-7 | Hold for **≥ 1 calendar week** of operator-side use before promoting to Production | Operators | Watch for issues; collect feedback |

If UAT reveals defects, fix in Phase 1, redeploy to Acceptance, restart the ≥ 1-week soak.

## 7. Phase 2b — Production to `rfsee.net`

Triggered after Acceptance soak completes without unresolved defects.

| # | Step | Who | How |
|---|---|---|---|
| 2b-1 | **Backup-first**: take a full SQLite `.backup` + `rsync` of `<photo_root>` to a separate volume on `rfsee.net`. Verify backup restores (test restore to a scratch path). | Admin | Mandatory; no deploy until backup verified |
| 2b-2 | Deploy the **same archive** (same SHA-256) that survived Acceptance — no rebuild | Admin | Avoids "works on Acceptance, broken in Prod" drift |
| 2b-3 | Smoke-test (`TP §9.1` minimal scenario) against `https://rfsee.net/webapp/` | Claude or admin | Aborts the deploy if it fails |
| 2b-4 | Announce to the community + circulate the updated user manual | Admin | Community comms |
| 2b-5 | Watch the audit log + error log for 48 h | Admin | Early-warning |

## 8. Rollback

For the dev server: redeploy the previous archive — nothing precious to preserve.

For Acceptance / Production: restore from the P0-8 / 2b-1 backup. The deploy archive is small; the data file is the asset. Rollback is "stop the new code from running, restore the backup, restart the previous code archive".

| Trigger | Action |
|---|---|
| Phase 1 iteration test fails irrecoverably | Discard the iteration branch; re-plan |
| Acceptance UAT discovers a structural defect | Fix in Phase 1, redeploy to Acceptance, restart UAT clock |
| Production deploy fails smoke | Restore data backup (2b-1) and previous code archive; restart |
| Production reveals a defect after promotion | Patch on Dev, re-run Acceptance, re-promote; or rollback to previous prod archive if urgent |

## 9. Open questions

Items the plan deliberately leaves to the user / admin to settle before they bite:

1. **The exact URL path on Acceptance and Production** — `https://pe1mew.nl/webapp/` is the assumed default; if you'd rather mount at the apex (`https://pe1mew.nl/`) or a subdomain (`https://obs.pe1mew.nl/`), the `public_base_url` config key changes and the QR contents change.
2. **Apache vs. nginx on Acceptance / Production** — the dev server is Apache (per brief). If Acceptance or Production runs nginx, the `.htaccess` becomes a `location` block in the server config — the implementer writes a one-page conversion note.
3. **Composer access on the deployment hosts** — if outbound HTTPS is blocked, `vendor/` ships in the release archive (decision recommended in this plan). If outbound is open, `composer install --no-dev` runs on the host instead.
4. **The Acceptance soak length** — the plan defaults to ≥ 1 calendar week. If the herenboeren UAT cycle is faster or slower, adjust.
5. **Who runs the HIL test cases on Acceptance / Production** — Phase 1 explicitly uses Claude. Phase 2 can use Claude too (if SSH access is granted) or the admin (running the same probe commands manually). The TP is written so a human can execute every case without Claude in the loop.

## 10. Out of scope for this plan

- Continuous integration / continuous deployment (CI/CD) — not warranted at this scale; manual gating is appropriate.
- Multi-region / load-balanced deployments — single-machine deploy per environment.
- Containerisation — the plan targets a stock LAMP server; containerising is the implementer's option but adds nothing required by the FDS / TDS.
- Performance / load testing beyond the small-scale assumption (A7) — the timing budgets in `§1.1` of the FDS are the binding ones; a separate scale-test campaign is not in this plan.

---

*End of implementation plan. Subject to revision as Phase 1 iterations surface concrete questions; revisions shall preserve the iteration boundaries so the test plan's iteration mapping stays valid.*
