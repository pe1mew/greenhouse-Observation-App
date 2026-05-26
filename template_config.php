<?php
/**
 * Greenhouse Observation App — configuration template
 *
 * Copy this file to config.php and fill in your values.
 * Do NOT rename this file — config.php is loaded at runtime;
 * template_config.php is the distributable starter.
 *
 * This file must return a plain associative array (TDS-CFG-060).
 * Do not use define() or a Config class.
 *
 * The admin password is NOT set here. It is configured via the
 * first-run setup wizard at the admin URL (FR-SEC-020, TDS-AUTH-090).
 */
return [

    // ── Identity ────────────────────────────────────────────────────
    // Display name shown in the admin GUI header. Must not be empty.
    'admin_name' => '',

    // ── Session ─────────────────────────────────────────────────────
    // Admin session inactivity timeout in seconds. Minimum 60.
    'admin_session_timeout' => 3600,

    // ── Storage ─────────────────────────────────────────────────────
    // Absolute path to the SQLite database file.
    // MUST resolve to a location OUTSIDE the web document root.
    'db_path' => '/var/www/greenhouse-data/greenhouse.db',

    // Absolute path to the photo storage directory.
    // MUST resolve to a location OUTSIDE the web document root.
    'photo_root' => '/var/www/greenhouse-data/photos',

    // ── Admin URL ───────────────────────────────────────────────────
    // URL path segment for the admin GUI (no leading/trailing slash).
    // Keep this non-obvious — operators must not discover it by guessing.
    // Only letters, digits, hyphens, and underscores are accepted.
    // Default: management
    'admin_url_path' => 'management',

    // ── Observation rules ───────────────────────────────────────────
    // How long (in hours) operators may edit or delete their own
    // observations after recording. Configurable here only — there is
    // no admin-GUI control for this value (TDS-CFG-050).
    // Default: 24
    'edit_window_hours' => 24,

    // ── Localisation ────────────────────────────────────────────────
    // IANA timezone name used for all user-facing timestamps and CSV
    // export offsets. All internal storage is UTC (TDS-CFG-070).
    // Default: Europe/Amsterdam
    'timezone' => 'Europe/Amsterdam',

    // ── Data retention ──────────────────────────────────────────────
    // Automatic deletion of observations older than this many days.
    // Set to 0 to disable automatic deletion entirely.
    // Default: 365 (one year)
    'retention_days' => 365,

    // ── Public URL (strongly recommended) ───────────────────────────
    // Canonical public base URL of this deployment, no trailing slash.
    // Used to construct QR-code URLs (TDS-URL-040).
    // Set this explicitly on reverse-proxy deployments so the QR code
    // encodes the operator-facing URL, not the internal hostname.
    // Example: 'https://obs.example.com'
    // Leave empty to derive from $_SERVER at request time.
    'public_base_url' => '',

    // ── GDPR contact ────────────────────────────────────────────────
    // Administrator contact details shown on the privacy notice
    // (FR-SEC-040, TDS-UI-080). Typically an email address.
    'admin_contact' => '',

    // ── Logging ─────────────────────────────────────────────────────
    // Path to the PHP error log file. Leave empty to use PHP's default
    // error_log ini setting (TDS-UI-090).
    'error_log_path' => '',

];
