<?php
declare(strict_types=1);
namespace GreenhouseObs;

class Config
{
    private static array $defaults = [
        'admin_session_timeout' => 3600,
        'admin_url_path'        => 'management',
        'edit_window_hours'     => 24,
        'timezone'              => 'Europe/Amsterdam',
        'retention_days'        => 365,
        'public_base_url'       => '',
        'admin_contact'         => '',
        'error_log_path'        => '',
    ];

    /** Load config.php and merge with defaults. Returns null if the file is absent or invalid. */
    public static function load(string $path): ?array
    {
        if (!file_exists($path)) {
            return null;
        }
        $cfg = @(require $path);
        if (!is_array($cfg)) {
            return null;
        }
        return array_replace(self::$defaults, $cfg);
    }

    /**
     * Validate all required config keys.
     * @return string[] Lang keys for each failed rule (empty = valid).
     */
    public static function validate(array $cfg, string $docRoot): array
    {
        $errors = [];

        if (trim((string)($cfg['admin_name'] ?? '')) === '') {
            $errors[] = 'config_admin_name_empty';
        }

        if (!is_numeric($cfg['admin_session_timeout'] ?? null)
            || (int)$cfg['admin_session_timeout'] < 60) {
            $errors[] = 'config_session_timeout';
        }

        if (!self::isSafePath((string)($cfg['db_path'] ?? ''), $docRoot, true)) {
            $errors[] = 'config_db_path_invalid';
        }

        if (!self::isSafePath((string)($cfg['photo_root'] ?? ''), $docRoot, false)) {
            $errors[] = 'config_photo_root_invalid';
        }

        if (!preg_match('/^[A-Za-z0-9_-]+$/', (string)($cfg['admin_url_path'] ?? ''))) {
            $errors[] = 'config_admin_url_path_invalid';
        }

        if (!is_numeric($cfg['edit_window_hours'] ?? null)
            || (int)$cfg['edit_window_hours'] < 1) {
            $errors[] = 'config_edit_window_invalid';
        }

        try {
            new \DateTimeZone((string)($cfg['timezone'] ?? ''));
        } catch (\Exception $e) {
            $errors[] = 'config_timezone_invalid';
        }

        if (!is_numeric($cfg['retention_days'] ?? null)
            || (int)$cfg['retention_days'] < 0) {
            $errors[] = 'config_retention_invalid';
        }

        return $errors;
    }

    /**
     * Check that $path is (a) resolvable with a writable parent and
     * (b) not inside the document root (FR-INST-040 / TDS-STO-010).
     * For files ($isFile=true), the parent dir must be writable.
     * For directories ($isFile=false), the dir or its parent must be writable.
     */
    private static function isSafePath(string $path, string $docRoot, bool $isFile): bool
    {
        if ($path === '') {
            return false;
        }

        // Resolve as much of the path as possible
        $real = realpath($path);
        if ($real === false) {
            $real = realpath(dirname($path));
            if ($real === false) {
                return false;
            }
        }

        // Must not be inside (or equal to) the document root
        $realDoc = realpath($docRoot);
        if ($realDoc !== false) {
            $docSlash  = rtrim($realDoc, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
            $pathSlash = rtrim($real,    DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
            if ($real === $realDoc || strncmp($pathSlash, $docSlash, strlen($docSlash)) === 0) {
                return false;
            }
        }

        // Must have a writable parent (or be writable itself for directories)
        $checkDir = $isFile
            ? (is_file($real) ? dirname($real) : $real)
            : (is_dir($real)  ? $real          : dirname($real));

        return is_writable($checkDir);
    }
}
