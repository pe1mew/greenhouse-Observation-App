-- Migration 0001: initial schema
-- Applied automatically on first run by the migration runner (TDS-STO-080).
-- All timestamps stored as UTC TEXT 'YYYY-MM-DDTHH:MM:SSZ' (TDS-CFG-070).

PRAGMA journal_mode = WAL;
PRAGMA busy_timeout = 5000;
PRAGMA foreign_keys = ON;

-- ── Schema version tracker ───────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS schema_meta (
    id      INTEGER PRIMARY KEY CHECK(id = 1),
    version INTEGER NOT NULL
);

-- ── Greenhouses ──────────────────────────────────────────────────────────
-- id is a 4-character uppercase hex string (FR-GH-010, TDS-DM note).
-- SQLite GLOB is used for the format check; the application layer
-- (TDS-URL-050) is the primary enforcement point.
CREATE TABLE IF NOT EXISTS greenhouse (
    id         TEXT    NOT NULL PRIMARY KEY
                       CHECK(id GLOB '[0-9A-F][0-9A-F][0-9A-F][0-9A-F]'),
    name       TEXT    NOT NULL,
    location   TEXT,
    notes      TEXT,
    created_at TEXT    NOT NULL
);

-- ── Users / operators ────────────────────────────────────────────────────
-- handle_norm stores mb_strtolower(handle) for case-insensitive uniqueness
-- (TDS-AUTH-110). The UNIQUE index on handle_norm enforces it at DB level.
CREATE TABLE IF NOT EXISTS user (
    id                    INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
    handle                TEXT    NOT NULL,
    handle_norm           TEXT    NOT NULL,
    current_cookie_token  TEXT,
    csrf_token            TEXT    NOT NULL DEFAULT '',
    current_greenhouse_id TEXT    REFERENCES greenhouse(id),
    created_at            TEXT    NOT NULL,
    last_seen_at          TEXT    NOT NULL,
    cookie_invalidated_at TEXT
);

CREATE UNIQUE INDEX IF NOT EXISTS idx_user_handle_norm
    ON user(handle_norm);

-- ── Taxonomy ─────────────────────────────────────────────────────────────
-- internal_key is UNIQUE per category (FR-TAX-030, TDS-DM note).
-- The same key string (e.g. 'all_good') may appear in different categories.
CREATE TABLE IF NOT EXISTS category (
    id           INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
    internal_key TEXT    NOT NULL UNIQUE,
    display_name TEXT    NOT NULL,
    active_flag  INTEGER NOT NULL DEFAULT 1,
    sort_order   INTEGER NOT NULL DEFAULT 0
);

CREATE TABLE IF NOT EXISTS tag (
    id           INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
    category_id  INTEGER NOT NULL REFERENCES category(id),
    internal_key TEXT    NOT NULL,
    display_name TEXT    NOT NULL,
    active_flag  INTEGER NOT NULL DEFAULT 1,
    sort_order   INTEGER NOT NULL DEFAULT 0,
    UNIQUE (category_id, internal_key)
);

-- ── Observations ─────────────────────────────────────────────────────────
-- photo_path is a relative path under <photo_root> (TDS-STO-030).
-- greenhouse_id / category_id / tag_id use NO ACTION on delete so the
-- application layer can enforce friendly errors before deleting (TDS-STO-100).
CREATE TABLE IF NOT EXISTS observation (
    id            INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
    greenhouse_id TEXT    NOT NULL REFERENCES greenhouse(id),
    user_id       INTEGER NOT NULL REFERENCES user(id),
    ts            TEXT    NOT NULL,
    category_id   INTEGER NOT NULL REFERENCES category(id),
    tag_id        INTEGER NOT NULL REFERENCES tag(id),
    severity      INTEGER,
    note          TEXT,
    photo_path    TEXT,
    created_at    TEXT    NOT NULL,
    updated_at    TEXT    NOT NULL
);

CREATE INDEX IF NOT EXISTS idx_observation_greenhouse
    ON observation(greenhouse_id);
CREATE INDEX IF NOT EXISTS idx_observation_user
    ON observation(user_id);
CREATE INDEX IF NOT EXISTS idx_observation_ts
    ON observation(ts);

-- ── Admin credentials ────────────────────────────────────────────────────
-- Single-row table enforced by CHECK(id=1). Password set via setup wizard,
-- never stored in config.php (FR-SEC-020, TDS-AUTH-080).
CREATE TABLE IF NOT EXISTS admin (
    id                  INTEGER NOT NULL PRIMARY KEY CHECK(id = 1),
    password_hash       TEXT    NOT NULL,
    password_updated_at TEXT    NOT NULL
);

-- ── Brute-force protection ───────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS admin_login_attempts (
    ip           TEXT    NOT NULL PRIMARY KEY,
    last_ts      TEXT    NOT NULL,
    count        INTEGER NOT NULL DEFAULT 0,
    locked_until TEXT
);

-- ── Audit log (Step 2) ───────────────────────────────────────────────────
-- Table created in the initial schema so FK references are valid from day
-- one; rows are only written from Step 2 onwards (FR-ADM-060).
CREATE TABLE IF NOT EXISTS admin_audit (
    id          INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
    ts          TEXT    NOT NULL,
    action      TEXT    NOT NULL,
    target_kind TEXT    NOT NULL,
    target_id   TEXT    NOT NULL,
    details     TEXT
);

-- ── Seed schema version ──────────────────────────────────────────────────
INSERT OR REPLACE INTO schema_meta (id, version) VALUES (1, 1);
