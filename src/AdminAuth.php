<?php
declare(strict_types=1);
namespace GreenhouseObs;

class AdminAuth
{
    private const SK = 'admin_session'; // $_SESSION key

    public static function start(array $cfg): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!empty($_SESSION[self::SK]['last_active'])) {
            if (time() - (int)$_SESSION[self::SK]['last_active'] > (int)$cfg['admin_session_timeout']) {
                self::logout();
                return;
            }
            $_SESSION[self::SK]['last_active'] = time();
        }
    }

    public static function isLoggedIn(): bool
    {
        return !empty($_SESSION[self::SK]['logged_in']);
    }

    /** Returns null on success or a lang key string on failure. */
    public static function login(\PDO $db, string $password): ?string
    {
        $ip   = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $wait = self::lockoutSeconds($db, $ip);
        if ($wait !== null) {
            return 'admin_rate_limited';
        }
        $row = $db->query('SELECT password_hash FROM admin WHERE id = 1')->fetch();
        if (!$row || !password_verify($password, (string)$row['password_hash'])) {
            self::recordFail($db, $ip);
            return 'admin_invalid_credentials';
        }
        self::clearAttempts($db, $ip);
        session_regenerate_id(true);
        $_SESSION[self::SK] = [
            'logged_in'   => true,
            'last_active' => time(),
            'csrf'        => bin2hex(random_bytes(16)),
        ];
        return null;
    }

    public static function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 3600,
                $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
    }

    public static function getCsrf(): string
    {
        if (empty($_SESSION[self::SK]['csrf'])) {
            $_SESSION[self::SK]['csrf'] = bin2hex(random_bytes(16));
        }
        return (string)$_SESSION[self::SK]['csrf'];
    }

    public static function verifyCsrf(string $token): bool
    {
        return hash_equals($_SESSION[self::SK]['csrf'] ?? '', $token);
    }

    public static function isSetupNeeded(\PDO $db): bool
    {
        return (int)$db->query('SELECT COUNT(*) FROM admin')->fetchColumn() === 0;
    }

    public static function setup(\PDO $db, string $password): void
    {
        $db->prepare(
            'INSERT OR REPLACE INTO admin (id, password_hash, password_updated_at) VALUES (1, ?, ?)'
        )->execute([password_hash($password, PASSWORD_DEFAULT), utc_now()]);
    }

    /** Returns seconds remaining in lockout, or null if the IP is clear. */
    public static function lockoutSeconds(\PDO $db, string $ip): ?int
    {
        $stmt = $db->prepare('SELECT last_ts, count, locked_until FROM admin_login_attempts WHERE ip = ?');
        $stmt->execute([$ip]);
        $row = $stmt->fetch();
        if (!$row) {
            return null;
        }
        if ((time() - (int)strtotime((string)$row['last_ts'])) > 86400) {
            $db->prepare('DELETE FROM admin_login_attempts WHERE ip = ?')->execute([$ip]);
            return null;
        }
        if (!empty($row['locked_until'])) {
            $remaining = (int)strtotime((string)$row['locked_until']) - time();
            if ($remaining > 0) {
                return $remaining;
            }
        }
        return null;
    }

    private static function recordFail(\PDO $db, string $ip): void
    {
        $stmt = $db->prepare('SELECT count FROM admin_login_attempts WHERE ip = ?');
        $stmt->execute([$ip]);
        $count       = ((int)($stmt->fetchColumn() ?: 0)) + 1;
        $lockedUntil = null;
        if ($count >= 5) {
            $secs        = min(60 * (2 ** ($count - 5)), 3600);
            $lockedUntil = gmdate('Y-m-d\TH:i:s\Z', time() + $secs);
        }
        $db->prepare(
            'INSERT INTO admin_login_attempts (ip, last_ts, count, locked_until)
             VALUES (?, ?, ?, ?)
             ON CONFLICT(ip) DO UPDATE
               SET last_ts      = excluded.last_ts,
                   count        = excluded.count,
                   locked_until = excluded.locked_until'
        )->execute([$ip, utc_now(), $count, $lockedUntil]);
    }

    private static function clearAttempts(\PDO $db, string $ip): void
    {
        $db->prepare('DELETE FROM admin_login_attempts WHERE ip = ?')->execute([$ip]);
    }
}
