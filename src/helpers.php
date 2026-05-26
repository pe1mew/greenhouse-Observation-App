<?php
declare(strict_types=1);

/** Look up a Dutch UI string; replace :placeholder tokens. */
function lang(string $key, array $vars = []): string
{
    static $strings = null;
    if ($strings === null) {
        $strings = require APP_ROOT . '/lang/nl.php';
    }
    $s = $strings[$key] ?? $key;
    foreach ($vars as $k => $v) {
        $s = str_replace(':' . $k, (string)$v, $s);
    }
    return $s;
}

/** HTML-escape a value for safe output in views (TDS-STK-080). */
function e(string $s): string
{
    return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Current UTC timestamp in the internal canonical format. */
function utc_now(): string
{
    return gmdate('Y-m-d\TH:i:s\Z');
}

/**
 * Convert a stored UTC timestamp to a display string in the configured
 * timezone, with explicit ±HH:MM offset (TDS-CFG-070).
 */
function tz_display(string $utcTs, string $tz): string
{
    $dt = new DateTimeImmutable($utcTs, new DateTimeZone('UTC'));
    return $dt->setTimezone(new DateTimeZone($tz))->format('Y-m-d\TH:i:sP');
}

/** Convert a stored UTC timestamp to local date+time without timezone indication. */
function tz_local(string $utcTs, string $tz): string
{
    $dt = new DateTimeImmutable($utcTs, new DateTimeZone('UTC'));
    return $dt->setTimezone(new DateTimeZone($tz))->format('Y-m-d H:i');
}

/** Send an HTTP redirect and stop execution. */
function redirect(string $url, int $code = 302): void
{
    http_response_code($code);
    header('Location: ' . $url);
    exit;
}

/**
 * Extract $data into the local scope and require a view file.
 * EXTR_SKIP prevents data keys from overwriting $view or $data itself.
 */
function render(string $view, array $data = []): void
{
    extract($data, EXTR_SKIP);
    require APP_ROOT . '/views/' . $view . '.php';
}

/**
 * Build an absolute URL to an app-relative path.
 * Uses public_base_url from config when set; derives from $_SERVER otherwise.
 */
function app_url(string $path = ''): string
{
    global $cfg;
    if (!empty($cfg['public_base_url'])) {
        return rtrim($cfg['public_base_url'], '/') . '/' . ltrim($path, '/');
    }
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $dir    = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? '/'), '/');
    return $scheme . '://' . $host . $dir . '/' . ltrim($path, '/');
}
