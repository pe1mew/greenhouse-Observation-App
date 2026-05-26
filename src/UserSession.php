<?php
declare(strict_types=1);
namespace GreenhouseObs;

class UserSession
{
    private const COOKIE_NAME    = 'gh_user';
    private const TOKEN_BYTES    = 16;           // 128-bit
    private const COOKIE_MAX_AGE = 315360000;    // 10 years

    public static function resolve(\PDO $db): ?array
    {
        $token = $_COOKIE[self::COOKIE_NAME] ?? '';
        if ($token === '') {
            return null;
        }
        $stmt = $db->prepare('SELECT * FROM user WHERE current_cookie_token = ?');
        $stmt->execute([$token]);
        $user = $stmt->fetch(\PDO::FETCH_ASSOC);
        if (!$user) {
            return null;
        }
        $db->prepare('UPDATE user SET last_seen_at = ? WHERE id = ?')
           ->execute([utc_now(), $user['id']]);
        return $user;
    }

    public static function register(\PDO $db, string $ghId, string $handle): void
    {
        $handle = trim($handle);
        $norm   = mb_strtolower($handle, 'UTF-8');
        $token  = bin2hex(random_bytes(self::TOKEN_BYTES));
        $csrf   = bin2hex(random_bytes(self::TOKEN_BYTES));
        $now    = utc_now();
        $db->prepare(
            'INSERT INTO user (handle, handle_norm, current_cookie_token, csrf_token,
                               current_greenhouse_id, created_at, last_seen_at)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        )->execute([$handle, $norm, $token, $csrf, $ghId, $now, $now]);
        self::setCookie($token);
    }

    public static function updateCurrentGreenhouse(\PDO $db, int $userId, string $ghId): void
    {
        $db->prepare('UPDATE user SET current_greenhouse_id = ?, last_seen_at = ? WHERE id = ?')
           ->execute([$ghId, utc_now(), $userId]);
    }

    public static function updateHandle(\PDO $db, int $userId, string $handle): void
    {
        $handle = trim($handle);
        $norm   = mb_strtolower($handle, 'UTF-8');
        $token  = bin2hex(random_bytes(self::TOKEN_BYTES));
        $csrf   = bin2hex(random_bytes(self::TOKEN_BYTES));
        $db->prepare(
            'UPDATE user SET handle = ?, handle_norm = ?, current_cookie_token = ?, csrf_token = ? WHERE id = ?'
        )->execute([$handle, $norm, $token, $csrf, $userId]);
        self::setCookie($token);
    }

    public static function forget(\PDO $db, int $userId): void
    {
        $db->prepare(
            'UPDATE user SET current_cookie_token = ?, csrf_token = ?, cookie_invalidated_at = ? WHERE id = ?'
        )->execute([
            bin2hex(random_bytes(self::TOKEN_BYTES)),
            bin2hex(random_bytes(self::TOKEN_BYTES)),
            utc_now(),
            $userId,
        ]);
        self::clearCookie();
    }

    private static function setCookie(string $token): void
    {
        $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
        setcookie(self::COOKIE_NAME, $token, [
            'expires'  => time() + self::COOKIE_MAX_AGE,
            'path'     => '/',
            'secure'   => $secure,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }

    private static function clearCookie(): void
    {
        setcookie(self::COOKIE_NAME, '', [
            'expires'  => time() - 3600,
            'path'     => '/',
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }
}
