# Operator-Observation Strategy

| Field | Value |
|---|---|
| Document | Operator-Observation Strategy |
| Project | Greenhouse Ventilation Controller |
| Status | Approved — ready for Step 1 implementation (operator approval 2026-05-23) |
| Approved | Hosting: **generic PHP backend** (deployable to any conventional PHP/MySQL host); Multi-greenhouse: **from day zero, identified by greenhouse ID embedded in the QR-code URL** |
| Scope | A standalone observation-collection web application, fully decoupled from the physical controller. Joins controller data only at analysis time, through a manual merge. |
| Related | `model/thermalProfileCampaign.md` (analysis pipeline consumer), `model/logUpdatePlan.md` (controller-side log evolution, *unaffected* by this plan), `temp/plot_daily.py` (plotting tool, gains an observation lane) |
| Companion firmware change | **None.** This strategy explicitly excludes any firmware modification. |

---

## 1. Purpose

The controller captures what it can sense. Operators see everything else — pest pressure, weather events, manual interventions, sensor disagreements, crop-stage changes, oscillations the math wouldn't flag as anomalous, condensation on the glass, a branch resting against a vent. Today none of this is recorded anywhere a future analyst can correlate with sensor data.

This document describes a strategy to close that gap *without modifying the controller*. The vehicle is a small, easy-to-use web application that operators visit on a phone to record what they observe. The web app and the controller never communicate. The two streams of data — what the greenhouse did, and what the human noticed — are joined later, manually, in the analysis pipeline.

## 2. Two-path architecture

Two independent record streams that meet only when an analyst chooses to merge them.

### Path 1 — Controller / SD-log / post-analysis (existing, unchanged)

The greenhouse controller continues to operate as today:

- Senses temperature, humidity, wind speed and direction, window state.
- Decides per the climate-control logic and the wind/motor safety overrides.
- Acts on the relays.
- Records every event to the SD card with a local-time timestamp.
- Uploads the day's records to the status server once every 24 hours.

The analyst, on demand, pulls the day's records from the status server and runs the existing pipeline tools (plotting, plant-model fitting, thermal-profile building). Path 1 needs no human input to operate. It captures *what the greenhouse did*.

This strategy makes **zero changes to Path 1** — no firmware modification, no SD-log schema change, no T14 upload-protocol change. Path 1 is mentioned here only because it is one of the two streams the merge step consumes.

### Path 2 — Observation collection (new web app)

A small web application served from a static URL. Its only purpose is to let an operator, holding a phone, record what they see, do, or notice. Five capabilities, nothing more:

1. Opens via a QR code in the greenhouse or a bookmark on the operator's phone.
2. Asks who is recording — a self-asserted handle ("Remko", "Marja").
3. Presents a small set of pre-defined tags (≤ 6 per category, 5 categories — see §3).
4. Accepts an optional free-text note and an optional photo.
5. Stores the record with a timestamp the operator can adjust if they're recording after the fact.

The app does not communicate with the controller, does not display sensor data, does not show live greenhouse conditions, does not send notifications. It is a *recording tool*, not a dashboard. Two taps is the typical interaction.

Path 2 captures *what the greenhouse experienced from a human's point of view*.

### The merge — manual, on demand, offline

The two paths never meet during normal operation. They meet only when the analyst chooses to merge them, and only by aligning **greenhouse ID + timestamp**:

- Sensor records from Path 1 sit on the status server (today's `/hbwv/log/` archive), organised per-controller and therefore implicitly per-greenhouse.
- Observation records from Path 2 sit in the web app's own data store, each tagged with the greenhouse ID captured at QR-scan time (see §10.2).
- An analysis run pulls both as CSV files for the greenhouse of interest and joins them by time.

The merge is a downstream activity. It happens:

- When the analyst generates a daily plot — observation markers overlay the sensor traces.
- When the analyst fits the thermal model — observations annotate anomalies and exclude operator-disturbed intervals from the cooling-rate fit.
- When investigating an incident — observations explain sensor anomalies, or fail to and motivate further investigation.
- When summarising a week or a season — observations become the narrative that contextualises the sensor data.

It never happens automatically, never in real time, never as part of the controller's runtime. The analyst pays a small one-off effort per merge; the system pays zero ongoing coordination effort.

## 3. What gets recorded

Five things per observation, in order of importance to a future analyst:

| Field | Purpose |
|---|---|
| **When** | A timestamp. Operator can adjust slightly when recording after the fact ("this happened ~30 min ago"). |
| **Who** | The operator's self-asserted handle. Supports multi-person greenhouse handovers ("Marja was here this morning"). |
| **What kind** | One category and one tag from a fixed list (see below). |
| **How much / how bad** | Optional severity, 1..5. Absent when the tag is inherently binary. |
| **Why or what specifically** | Optional free-text note. Optional photo. |

### Tag taxonomy (frozen at launch, extensible afterwards)

Five categories with ≤ 6 tags each. The list is deliberately short so two taps always covers the routine case; the escape hatches (note, photo) handle the unusual.

| Category | Tags | Typical use |
|---|---|---|
| **Wellbeing check** | `all_good`, `concern` | The routine "I came in, I looked around, here's my impression" |
| **Environment** | `weather_storm`, `weather_overcast`, `obstacle_seen`, `external_noise` | Things that affect the greenhouse but aren't fully captured by the indoor sensors |
| **Crop** | `crop_stage_change`, `crop_pest`, `crop_disease`, `crop_other` | Anything the plants are doing or suffering |
| **Sensor / control feedback** | `sensor_drift_suspect`, `control_too_open`, `control_too_closed`, `oscillation_noticed`, `manual_override` | Operator's opinion of how the controller is performing |
| **Maintenance** | `maint_clean_sensors`, `maint_window_check`, `maint_other` | Maintenance actions the operator performed |

The taxonomy is **frozen at launch with explicit room to extend later**. Each tag has a stable identity that does not change when its display text is reworded or translated. Historical observations never re-interpret because the identity is stable. New tags can be added at any time; renaming or removing existing ones must be avoided to keep the history readable.

## 4. Operator experience

The minimum-friction interaction is the design centre:

- Operator picks up phone in greenhouse.
- Two taps to record a routine "all good", "saw aphids", or "cleaned sensors".
- Half a minute to add a note or a photo if something deserves it.
- Walks away.

There is no login screen, no waiting for the app to load live conditions, no decisions about which dashboard to use. The observation app is a one-button recorder. The controller dashboard remains a separate tool, used for different purposes (configuring setpoints, viewing live state); the two coexist on the operator's phone home screen, used at different moments for different intents.

When the operator wants to refine a previously recorded observation — add a missing photo, edit a wrong tag, append a note — the app lets them browse and edit their own recent records for a short window (e.g. the last 24 hours). After that window the record is read-only, preserving the integrity of the historical record.

### 4.1 UX mock-ups (illustrative)

The mock-ups below show what the operator sees at each step of the recording flow. They are illustrative, not binding: an implementer is free to choose typography, spacing, framework, and colour. What is binding is the *shape* of the interaction — the number of taps to complete a routine observation, the optionality of notes and photos, and the absence of any live-sensor surface.

**M1 — First-visit splash (identity).** Opens once when the operator first lands on the app from the QR code. Stored in a per-device cookie; never asked again unless the operator switches profile in settings.

```
┌─────────────────────────────────┐
│                                 │
│  Welcome to Willemshoeve        │
│  greenhouse observations        │
│                                 │
│  Who is recording today?        │
│                                 │
│  [ Remko                     ]  │
│  [ Marja                     ]  │
│  [ Other...                  ]  │
│                                 │
│  ──────────────────────────     │
│  Set once. Change in settings.  │
└─────────────────────────────────┘
```

**M2 — Home screen (the launching pad).** What the operator sees every subsequent visit. One big primary action plus a glance at the day so far. Two non-primary links live at the bottom (full history, settings) — out of the way, available if needed.

```
┌─────────────────────────────────┐
│  Willemshoeve · Hi Remko        │
│                                 │
│  ╔═══════════════════════════╗  │
│  ║                           ║  │
│  ║    +  Quick observation   ║  │
│  ║                           ║  │
│  ╚═══════════════════════════╝  │
│                                 │
│  Recent (last 24 h)             │
│  ─────────────────────          │
│  14:30  Environment             │
│         weather_storm           │
│  10:15  Maintenance             │
│         maint_clean_sensors     │
│  09:42  Wellbeing — all_good    │
│                                 │
│  ──────────────────────────     │
│  See all · Settings             │
└─────────────────────────────────┘
```

**M3 — Category picker (tap 1).** Five rows, one tap each.

```
┌─────────────────────────────────┐
│  ←  Back                        │
│                                 │
│  What kind of observation?      │
│                                 │
│  ┌───────────────────────────┐  │
│  │  Wellbeing check          │  │
│  ├───────────────────────────┤  │
│  │  Environment              │  │
│  ├───────────────────────────┤  │
│  │  Crop                     │  │
│  ├───────────────────────────┤  │
│  │  Sensor / control         │  │
│  ├───────────────────────────┤  │
│  │  Maintenance              │  │
│  └───────────────────────────┘  │
└─────────────────────────────────┘
```

**M4 — Tag picker (tap 2).** Up to six tags. Tapping a tag records the observation immediately — no separate "submit" step.

```
┌─────────────────────────────────┐
│  ←  Back                        │
│                                 │
│  Environment                    │
│                                 │
│  ┌───────────────────────────┐  │
│  │  weather_storm            │  │
│  ├───────────────────────────┤  │
│  │  weather_overcast         │  │
│  ├───────────────────────────┤  │
│  │  obstacle_seen            │  │
│  ├───────────────────────────┤  │
│  │  external_noise           │  │
│  └───────────────────────────┘  │
│                                 │
└─────────────────────────────────┘
```

**M5 — Recorded confirmation + optional note / photo.** Closes after 2 seconds if the operator does nothing. The "Add note" and "Add photo" actions remain reachable from the recent-list entry for the next 24 hours, so a hurried operator can come back later.

```
┌─────────────────────────────────┐
│                                 │
│           ✓  Recorded           │
│                                 │
│  Environment · weather_storm    │
│  Just now · 15:42               │
│                                 │
│  Anything to add? (optional)    │
│  ┌───────────────────────────┐  │
│  │  +  Add note              │  │
│  ├───────────────────────────┤  │
│  │  📷  Add photo            │  │
│  └───────────────────────────┘  │
│                                 │
│  ──────────────────────────     │
│  [ Done ]                       │
└─────────────────────────────────┘
```

**M6 — Add note (optional).** Free text and optional severity (1..5). No required fields; the operator can always cancel back to the bare observation.

```
┌─────────────────────────────────┐
│  ←  Back · weather_storm 15:42  │
│                                 │
│  Note (optional)                │
│  ┌───────────────────────────┐  │
│  │ Hail started ~15:40.      │  │
│  │ M3 was OPEN, closed at    │  │
│  │ 15:45 by safety logic.    │  │
│  │                           │  │
│  └───────────────────────────┘  │
│                                 │
│  Severity (optional)            │
│  [ 1 ][ 2 ][ 3 ][ 4 ][ 5 ]      │
│                                 │
│  ──────────────────────────     │
│  [ Save ]                       │
└─────────────────────────────────┘
```

**M7 — Recent observations (full list).** Reached from the home-screen "See all" link. Grouped by day, newest first. A camera icon flags entries with attached photos.

```
┌─────────────────────────────────┐
│  ←  Back · Recent observations  │
│                                 │
│  Today                          │
│  15:42  Environment             │
│         weather_storm  sev 3    │
│  14:30  Environment             │
│         weather_overcast        │
│  10:15  Maintenance             │
│         maint_clean_sensors     │
│  09:42  Wellbeing — all_good    │
│                                 │
│  Yesterday                      │
│  18:00  Wellbeing — day_good    │
│  14:55  Sensor / control        │
│         oscillation_noticed     │
│                                 │
│  2 days ago                     │
│  16:20  Crop                    │
│         crop_pest         📷    │
│  …                              │
└─────────────────────────────────┘
```

**M8 — Observation detail (editable for 24 h).** Tap any recent entry to see full content; while the entry is within the 24-hour edit window the [Edit] and [Delete] actions are live. Past the window the same screen renders the same content but without the action row, and a small footer reads "Read-only after 24 h".

```
┌─────────────────────────────────┐
│  ←  Back                        │
│                                 │
│  Environment · weather_storm    │
│  Today, 15:42 · Remko · Sev 3   │
│                                 │
│  Note                           │
│  ────                           │
│  Hail started ~15:40.           │
│  M3 was OPEN, closed at 15:45   │
│  by safety logic.               │
│                                 │
│  Photo                          │
│  ─────                          │
│  [  thumbnail  ]                │
│                                 │
│  ──────────────────────────     │
│  [ Edit ]      [ Delete ]       │
│  Editable for 23 h 56 min more. │
└─────────────────────────────────┘
```

**M9 — Physical-world entry point (in the greenhouse).** Not a screen — a laminated sign on the greenhouse wall. The QR encodes a URL that opens directly at the home screen (M2) with the greenhouse already selected; first-time visitors see M1 once and then jump straight to M2 thereafter.

```
        Greenhouse wall sign (A5 laminated):

  ╔══════════════════════════════════╗
  ║                                  ║
  ║      KAS WILLEMSHOEVE            ║
  ║      Observatie-app              ║
  ║                                  ║
  ║      ┌──────────────┐            ║
  ║      │              │            ║
  ║      │     [QR]     │  ← scan    ║
  ║      │              │            ║
  ║      └──────────────┘            ║
  ║                                  ║
  ║      Or: obs.pe1mew.nl/wsh       ║
  ║                                  ║
  ║      Two taps to record          ║
  ║      what you see.               ║
  ║                                  ║
  ╚══════════════════════════════════╝
```

**Interaction summary.** The full path from "wants to record" to "recorded" is four screens deep (Home → Category → Tag → Confirmation), of which the first is already on screen and the last auto-dismisses. Net operator effort for the routine case: two taps. The note and photo paths exist but are never blocking.

## 5. Where the value shows up

Three concrete payoffs, all in the analysis pipeline. None happen inside the observation app itself.

| Use case | What the observation merge unlocks |
|---|---|
| **Daily plot** | The plot grows an observation lane below the sensor panels. Operator-recorded events appear as markers at the right timestamps, colour-coded by category. The daily PNG becomes a logbook page rather than a sensor trace. |
| **Thermal-model calibration** | Observations marked `manual_override` exclude their interval from the cooling-rate fit. Observations marked `weather_storm` annotate sudden T drops the model would otherwise treat as noise. The model's residuals are explained rather than dismissed. |
| **Incident triage** | When an alarm fires or a panic occurs, the matching-time observations are the operator's testimony. "M2 took ages to open at 14:00" recorded by the operator is the missing context for an SD-log row showing an unusual travel-timer expiry. |
| **Setpoint vetting** | Operator notes about setpoint changes ("changed t_max_day to 26 because peppers wilted") preserve *intent*, not just value. Future review can decide if the rationale held up. |
| **Community handover** | At a multi-operator installation, the observation history is the shared memory: arriving operator sees "since your last visit" before walking in. |
| **Weekly digest** | "You recorded 3 oscillations this week, all between 14:00 and 16:00, while the controller's dwell_close_m2 was at 0 minutes" — turns the data into a conversation between the operator and the controller. |

## 6. Rollout

Two functional milestones, in order. No firmware coordination, no soak gate, no controller side-effects — the observation app is released independently of any controller release.

### Step 1 — the recorder

The minimum that ships a useful tool:

- The five categories with their tags, presented as the simple two-tap flow.
- An operator handle entered once and remembered.
- Records stored durably with a timestamp.
- An export endpoint so the analyst can pull all observations as CSV for the merge step.
- Discoverable via a QR code in the greenhouse.

This is the entire core. Everything that follows is enhancement.

### Step 2 — the polish

Quality-of-life additions that build on the same foundation, in priority order:

- Free-text notes and photo upload.
- Offline capture — observations recorded while the phone has no signal are queued and submitted when the connection returns. The greenhouse's metal-glass envelope makes WiFi flaky; this is not optional in practice but does not block the initial release.
- The editable "recent observations" list with the 24-hour edit window.
- Multi-greenhouse support — operators record against one of several installations from the same app.
- A weekly digest the analyst can email out or operators can view in the app.

The analysis-pipeline side gets one corresponding addition: the daily plot grows the observation lane (one merge with one CSV input). Plant-model and thermal-profile fits learn to read the same CSV.

## 7. Trade-offs

The two-path architecture is a deliberate design choice; the trade-offs are the cost of that choice.

| Aspect | Consequence |
|---|---|
| **Zero firmware risk** | No soak invalidation, no rollback path, no OTA risk. The controller is untouched. |
| **Independent release cadence** | Observation taxonomy, UX, and features can iterate weekly without touching the firmware's quarterly release lineage. |
| **Independent failure modes** | If the controller is down for maintenance, observation recording continues. If the observation app is down, the controller continues to operate. |
| **Lower technical complexity** | The web app is a recording tool, not a dashboard. No real-time data flow, no notification logic, no controller-availability checks. |
| **Operator can record from anywhere** | Recording is not gated on being on the controller's local network. An operator can record an observation from home if they remembered something after leaving the greenhouse. |
| **No live in-app context** | The observation app does not show current greenhouse conditions. An operator who wants to check current T or window state opens the controller dashboard in another browser tab. This is a deliberate separation of *recording* and *monitoring* concerns. |
| **Correlation is deferred** | Observations and sensor data live in different stores; the join is paid at analysis time, not at write time. This is acceptable because analysis is itself an on-demand activity. |
| **Discoverability requires effort** | The observation app is not co-located with the controller dashboard. A laminated QR code in the greenhouse, plus the phone's home-screen install, are the discovery mechanisms; both are operator-side, low-effort to set up. |

## 8. What this strategy explicitly does not include

- **Any contact between the web app and the controller.** Ever. No proxying, no caching of controller data on the observation server, no live data display.
- **Real-time notifications, alerts, or dashboards** in the observation app. The app is a recorder, not a monitor.
- **Any control surface** in the observation app. Operators continue to configure the controller from the controller's GUI. The observation app cannot change a setpoint, open a window, or affect controller behaviour in any way.
- **Audit-grade or compliance-grade framing.** Operator handles are self-asserted; the system is a community-knowledge tool, not a regulated record. Severity is operator opinion, not certified measurement.
- **Automated cross-correlation alarms.** If the analysis pipeline discovers that operator-recorded oscillations correlate with low `dwell_close_m2` values, that finding is surfaced to the analyst through the existing plot/report mechanisms — not as an automated alarm at observation time.
- **Backporting to or coordination with controller firmware releases.** The observation app is a fully independent project with its own version history.

## 9. Cultural framing — the herenboeren context

At a multi-operator community installation (Willemshoeve), the observation log is not "data for engineers" — it is *the community's shared memory of the greenhouse*. Marketing the tool correctly in the operator manual is as important as the tool itself:

- Observations are routine, not extraordinary. "All looks fine" is a perfectly good observation and the most common one.
- Other operators read them. The next person walking in benefits from knowing what the prior person noticed.
- The analyst reads them and the controller's behaviour responds (over time, via setpoint adjustments) to what the community sees.
- No observation is ever wrong. Even a tag that turns out to be a misdiagnosis becomes useful data: "operator thought there was a sensor drift, sensor was actually fine" is a worthwhile record.

The friction-low UX matters because the cultural framing only works if recording is genuinely easy. A two-tap flow + an optional note + an optional photo is the right scale.

## 10. Decisions (locked 2026-05-23)

Two strategy-level decisions, settled before Step 1 starts. Both are recorded here so future readers of the spec understand the chosen path and the reasoning behind it.

### 10.1 Hosting — generic PHP backend

The observation web app is delivered as a **generic PHP application**, deployable to any conventional PHP/MySQL host (shared hosting, managed LAMP/LEMP, or self-hosted). It does not bind to a specific platform vendor and does not entangle with the controller's status-upload pipeline at the infrastructure layer.

**Rationale.**
- PHP is universally hostable — no managed-platform commitment, no proprietary runtime, easy to relocate.
- The observation backend's complexity (a handful of endpoints, one SQL table, file storage for photos) sits well within PHP's strengths.
- The operator community already runs PHP infrastructure, so deployment and maintenance fit existing skill set and tooling.
- The decoupling from the controller's status-upload pipeline at the infrastructure layer is consistent with the strategy's two-path architecture (§2) at the data layer — observation hosting can change independently of the status-server hosting.

### 10.2 Multi-greenhouse from day zero — greenhouse ID in QR-code URL

The system is designed for multiple greenhouses from launch. Each greenhouse has its own laminated QR-code sign; the QR encodes a URL that includes the **greenhouse ID** as a path segment or query parameter (the choice is left to the implementer; both are functionally equivalent — see M9 mock-up for a sample URL form).

On first visit:
1. The app captures the greenhouse ID from the URL.
2. It stores the ID in the device profile alongside the operator handle.
3. All subsequent observations are recorded against that greenhouse.

An operator who tends multiple greenhouses simply scans the QR for the one they are currently at; the app switches context automatically. The home screen (M2 mock-up) displays the current greenhouse's friendly name so the operator can never be confused about which installation they are recording against.

**Rationale.**
- The data model already supports a `greenhouse_id` field at zero marginal cost.
- Adding the entry-point mechanism (one QR per greenhouse, ID in the URL) upfront avoids a retrofit later when the project gains a second installation.
- Single-greenhouse operators see no overhead — the URL just has one greenhouse ID and the app always lands in the right place.
- The greenhouse ID is also the join key that the analysis pipeline uses to align Path 1 (controller's SD-log archive, organised per-controller / per-greenhouse) with Path 2 (observations, tagged with greenhouse ID).

## 11. Out-of-band note on path-1 evolution

The companion document `model/logUpdatePlan.md` describes an upcoming firmware release (2.0.0-rc.1.4.0) that extends the SD log with richer sensor sub-rows (0.1 °C T precision, continuous wind, window-state bitmask) and adds a `LOG_SUN` row for sunrise/sunset persistence. **That release is independent of this observation strategy.** Its only interaction with this plan is that the analysis pipeline's daily plot — which the observation lane lands in — also reads the richer SD-log rows. The two projects can ship in either order without conflict.

---

*End of plan — all strategy-level decisions locked (operator approval 2026-05-23): hosting = generic PHP backend; multi-greenhouse = day-zero, identified via greenhouse ID in QR-code URL. Ready for Step 1 implementation.*
