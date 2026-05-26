<?php
declare(strict_types=1);
namespace GreenhouseObs;

class UserController
{
    private array  $cfg;
    private \PDO   $db;
    private string $basePath;

    public function __construct(array $cfg, \PDO $db, string $basePath)
    {
        $this->cfg      = $cfg;
        $this->db       = $db;
        $this->basePath = $basePath;
    }

    public function dispatch(string $method, string $ghId, string $sub, array $gh): void
    {
        $user = UserSession::resolve($this->db);
        if ($user) {
            UserSession::updateCurrentGreenhouse($this->db, (int)$user['id'], $ghId);
        }

        // /<gh-id>/
        if ($sub === '/' || $sub === '') {
            if (!$user) {
                redirect($this->basePath . '/' . $ghId . '/register');
                return;
            }
            $this->home($method, $ghId, $gh, $user);
            return;
        }

        // /<gh-id>/register
        if ($sub === '/register') {
            if ($user) {
                redirect($this->basePath . '/' . $ghId . '/');
                return;
            }
            $this->register($method, $ghId, $gh);
            return;
        }

        // /<gh-id>/observe/  → category picker (M3)
        if ($sub === '/observe' || $sub === '/observe/') {
            if (!$user) {
                redirect($this->basePath . '/' . $ghId . '/register');
                return;
            }
            $this->categories($method, $ghId, $gh, $user);
            return;
        }

        // /<gh-id>/observe/<cat-id>  → tag picker (M4)
        if (preg_match('#^/observe/(\d+)/?$#', $sub, $m)) {
            if (!$user) {
                redirect($this->basePath . '/' . $ghId . '/register');
                return;
            }
            $this->tags($method, $ghId, $gh, $user, (int)$m[1]);
            return;
        }

        // /<gh-id>/confirm/<obs-id>  → confirmation (M5)
        if (preg_match('#^/confirm/(\d+)/?$#', $sub, $m)) {
            if (!$user) {
                redirect($this->basePath . '/' . $ghId . '/register');
                return;
            }
            $this->confirm($ghId, $gh, $user, (int)$m[1]);
            return;
        }

        // /<gh-id>/observation/<obs-id>/delete  (POST only)
        if (preg_match('#^/observation/(\d+)/delete$#', $sub, $m)) {
            if (!$user) {
                redirect($this->basePath . '/' . $ghId . '/register');
                return;
            }
            $this->deleteObservation($method, $ghId, $gh, $user, (int)$m[1]);
            return;
        }

        // /<gh-id>/observation/<obs-id>  → detail / edit
        if (preg_match('#^/observation/(\d+)/?$#', $sub, $m)) {
            if (!$user) {
                redirect($this->basePath . '/' . $ghId . '/register');
                return;
            }
            $this->observation($method, $ghId, $gh, $user, (int)$m[1]);
            return;
        }

        // /<gh-id>/settings
        if ($sub === '/settings' || $sub === '/settings/') {
            if (!$user) {
                redirect($this->basePath . '/' . $ghId . '/register');
                return;
            }
            $this->settings($method, $ghId, $gh, $user);
            return;
        }

        // /<gh-id>/forget  (POST only)
        if ($sub === '/forget' || $sub === '/forget/') {
            if (!$user) {
                redirect($this->basePath . '/' . $ghId . '/register');
                return;
            }
            $this->forget($method, $ghId, $gh, $user);
            return;
        }

        http_response_code(404);
        render('error', [
            'statusCode' => 404,
            'heading'    => lang('error_404_title'),
            'body'       => lang('error_404_body'),
        ]);
    }

    // ── M2: Home ──────────────────────────────────────────────────────────

    private function home(string $method, string $ghId, array $gh, array $user): void
    {
        $stmt = $this->db->prepare(
            "SELECT o.id, o.ts, c.display_name AS cat_name, t.display_name AS tag_name,
                    o.severity, o.note
             FROM observation o
             JOIN category c ON c.id = o.category_id
             JOIN tag t ON t.id = o.tag_id
             WHERE o.greenhouse_id = ?
               AND o.user_id = ?
               AND o.ts >= datetime('now', '-24 hours')
             ORDER BY o.ts DESC
             LIMIT 20"
        );
        $stmt->execute([$ghId, $user['id']]);
        $recent = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        http_response_code(200);
        render('user/home', [
            'ghId'    => $ghId,
            'gh'      => $gh,
            'ghName'  => $gh['name'],
            'handle'  => $user['handle'],
            'user'    => $user,
            'recent'  => $recent,
            'cfg'     => $this->cfg,
        ]);
    }

    // ── M1: Register ─────────────────────────────────────────────────────

    private function register(string $method, string $ghId, array $gh): void
    {
        $error         = null;
        $enteredHandle = '';

        if ($method === 'POST') {
            $handle        = trim($_POST['handle'] ?? '');
            $enteredHandle = $handle;

            if ($handle === '') {
                $error = lang('handle_required');
            } elseif (mb_strlen($handle, 'UTF-8') > 40) {
                $error = lang('handle_too_long');
            } else {
                $norm = mb_strtolower($handle, 'UTF-8');
                $stmt = $this->db->prepare('SELECT id FROM user WHERE handle_norm = ?');
                $stmt->execute([$norm]);
                if ($stmt->fetch()) {
                    $error = lang('handle_taken');
                } else {
                    UserSession::register($this->db, $ghId, $handle);
                    redirect($this->basePath . '/' . $ghId . '/');
                    return;
                }
            }
        }

        http_response_code(200);
        render('user/register', [
            'ghId'          => $ghId,
            'gh'            => $gh,
            'ghName'        => $gh['name'],
            'error'         => $error,
            'enteredHandle' => $enteredHandle,
        ]);
    }

    // ── M3: Category picker ───────────────────────────────────────────────

    private function categories(string $method, string $ghId, array $gh, array $user): void
    {
        $stmt = $this->db->prepare(
            'SELECT id, display_name FROM category WHERE active_flag = 1 ORDER BY sort_order, id'
        );
        $stmt->execute();
        $categories = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        http_response_code(200);
        render('user/categories', [
            'ghId'       => $ghId,
            'gh'         => $gh,
            'ghName'     => $gh['name'],
            'handle'     => $user['handle'],
            'categories' => $categories,
        ]);
    }

    // ── M4: Tag picker ────────────────────────────────────────────────────

    private function tags(string $method, string $ghId, array $gh, array $user, int $catId): void
    {
        $stmt = $this->db->prepare(
            'SELECT id, display_name FROM category WHERE id = ? AND active_flag = 1'
        );
        $stmt->execute([$catId]);
        $cat = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$cat) {
            redirect($this->basePath . '/' . $ghId . '/observe/');
            return;
        }

        if ($method === 'POST') {
            if (!hash_equals($user['csrf_token'], $_POST['_csrf'] ?? '')) {
                http_response_code(403);
                render('error', [
                    'statusCode' => 403,
                    'heading'    => lang('error_403_title'),
                    'body'       => lang('csrf_invalid'),
                ]);
                return;
            }

            $tagId = (int)($_POST['tag_id'] ?? 0);
            $stmt  = $this->db->prepare(
                'SELECT id FROM tag WHERE id = ? AND category_id = ? AND active_flag = 1'
            );
            $stmt->execute([$tagId, $catId]);
            if (!$stmt->fetch()) {
                redirect($this->basePath . '/' . $ghId . '/observe/' . $catId . '/');
                return;
            }

            $now = utc_now();
            $this->db->prepare(
                'INSERT INTO observation
                   (greenhouse_id, user_id, ts, category_id, tag_id, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?)'
            )->execute([$ghId, $user['id'], $now, $catId, $tagId, $now, $now]);
            $obsId = (int)$this->db->lastInsertId();

            redirect($this->basePath . '/' . $ghId . '/confirm/' . $obsId);
            return;
        }

        $stmt = $this->db->prepare(
            'SELECT id, display_name FROM tag WHERE category_id = ? AND active_flag = 1 ORDER BY sort_order, id'
        );
        $stmt->execute([$catId]);
        $tags = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        http_response_code(200);
        render('user/tags', [
            'ghId'   => $ghId,
            'gh'     => $gh,
            'ghName' => $gh['name'],
            'handle' => $user['handle'],
            'user'   => $user,
            'cat'    => $cat,
            'tags'   => $tags,
        ]);
    }

    // ── M5: Confirmation ─────────────────────────────────────────────────

    private function confirm(string $ghId, array $gh, array $user, int $obsId): void
    {
        $stmt = $this->db->prepare(
            'SELECT o.id, o.ts, c.display_name AS cat_name, t.display_name AS tag_name
             FROM observation o
             JOIN category c ON c.id = o.category_id
             JOIN tag t ON t.id = o.tag_id
             WHERE o.id = ? AND o.greenhouse_id = ? AND o.user_id = ?'
        );
        $stmt->execute([$obsId, $ghId, $user['id']]);
        $obs = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$obs) {
            redirect($this->basePath . '/' . $ghId . '/');
            return;
        }

        http_response_code(200);
        render('user/confirm', [
            'ghId'   => $ghId,
            'gh'     => $gh,
            'ghName' => $gh['name'],
            'handle' => $user['handle'],
            'obs'    => $obs,
        ]);
    }

    // ── Settings ─────────────────────────────────────────────────────────

    private function settings(string $method, string $ghId, array $gh, array $user): void
    {
        $error   = null;
        $success = null;

        if ($method === 'POST') {
            if (!hash_equals($user['csrf_token'], $_POST['_csrf'] ?? '')) {
                http_response_code(403);
                render('error', [
                    'statusCode' => 403,
                    'heading'    => lang('error_403_title'),
                    'body'       => lang('csrf_invalid'),
                ]);
                return;
            }

            $handle = trim($_POST['handle'] ?? '');
            if ($handle === '') {
                $error = lang('handle_required');
            } elseif (mb_strlen($handle, 'UTF-8') > 40) {
                $error = lang('handle_too_long');
            } else {
                $norm = mb_strtolower($handle, 'UTF-8');
                if ($norm !== ($user['handle_norm'] ?? '')) {
                    $stmt = $this->db->prepare(
                        'SELECT id FROM user WHERE handle_norm = ? AND id != ?'
                    );
                    $stmt->execute([$norm, $user['id']]);
                    if ($stmt->fetch()) {
                        $error = lang('handle_taken');
                    } else {
                        UserSession::updateHandle($this->db, (int)$user['id'], $handle);
                        redirect($this->basePath . '/' . $ghId . '/settings?saved=1');
                        return;
                    }
                } else {
                    UserSession::updateHandle($this->db, (int)$user['id'], $handle);
                    redirect($this->basePath . '/' . $ghId . '/settings?saved=1');
                    return;
                }
            }
        }

        if (isset($_GET['saved'])) {
            $success = lang('handle_changed');
        }

        http_response_code(200);
        render('user/settings', [
            'ghId'    => $ghId,
            'gh'      => $gh,
            'ghName'  => $gh['name'],
            'handle'  => $user['handle'],
            'user'    => $user,
            'error'   => $error,
            'success' => $success,
        ]);
    }

    // ── Observation detail / edit ─────────────────────────────────────────

    private function observation(string $method, string $ghId, array $gh, array $user, int $obsId): void
    {
        $stmt = $this->db->prepare(
            'SELECT o.id, o.ts, o.severity, o.note, o.user_id, o.created_at, o.updated_at,
                    c.display_name AS cat_name, t.display_name AS tag_name
             FROM observation o
             JOIN category c ON c.id = o.category_id
             JOIN tag t ON t.id = o.tag_id
             WHERE o.id = ? AND o.greenhouse_id = ?'
        );
        $stmt->execute([$obsId, $ghId]);
        $obs = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$obs) {
            http_response_code(404);
            render('error', [
                'statusCode' => 404,
                'heading'    => lang('observation_not_found'),
                'body'       => '',
            ]);
            return;
        }

        if ((int)$obs['user_id'] !== (int)$user['id']) {
            http_response_code(403);
            render('error', [
                'statusCode' => 403,
                'heading'    => lang('error_403_title'),
                'body'       => lang('observation_not_owner'),
            ]);
            return;
        }

        $editWindow = (int)$this->cfg['edit_window_hours'];
        $editable   = $this->isEditable((string)$obs['ts'], $editWindow);
        $error      = null;
        $success    = null;

        if ($method === 'POST' && $editable) {
            if (!hash_equals($user['csrf_token'], $_POST['_csrf'] ?? '')) {
                http_response_code(403);
                render('error', [
                    'statusCode' => 403,
                    'heading'    => lang('error_403_title'),
                    'body'       => lang('csrf_invalid'),
                ]);
                return;
            }
            $note   = trim($_POST['note'] ?? '');
            $sevRaw = trim($_POST['severity'] ?? '');
            $sev    = $sevRaw !== '' ? (int)$sevRaw : null;

            if ($sev !== null && ($sev < 1 || $sev > 5)) {
                $error = lang('severity_invalid');
            } else {
                $this->db->prepare(
                    'UPDATE observation SET note = ?, severity = ?, updated_at = ? WHERE id = ?'
                )->execute([$note ?: null, $sev, utc_now(), $obsId]);
                redirect($this->basePath . '/' . $ghId . '/observation/' . $obsId . '?saved=1');
                return;
            }
        }

        if (isset($_GET['saved'])) {
            $success = lang('observation_saved');
        }

        http_response_code(200);
        render('user/observation', [
            'ghId'       => $ghId,
            'gh'         => $gh,
            'ghName'     => $gh['name'],
            'handle'     => $user['handle'],
            'user'       => $user,
            'obs'        => $obs,
            'editable'   => $editable,
            'editWindow' => $editWindow,
            'error'      => $error,
            'success'    => $success,
            'cfg'        => $this->cfg,
        ]);
    }

    private function deleteObservation(string $method, string $ghId, array $gh, array $user, int $obsId): void
    {
        if ($method !== 'POST') {
            redirect($this->basePath . '/' . $ghId . '/observation/' . $obsId);
            return;
        }

        if (!hash_equals($user['csrf_token'], $_POST['_csrf'] ?? '')) {
            http_response_code(403);
            render('error', [
                'statusCode' => 403,
                'heading'    => lang('error_403_title'),
                'body'       => lang('csrf_invalid'),
            ]);
            return;
        }

        $stmt = $this->db->prepare(
            'SELECT ts, user_id FROM observation WHERE id = ? AND greenhouse_id = ?'
        );
        $stmt->execute([$obsId, $ghId]);
        $obs = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$obs || (int)$obs['user_id'] !== (int)$user['id']) {
            redirect($this->basePath . '/' . $ghId . '/');
            return;
        }

        if (!$this->isEditable((string)$obs['ts'], (int)$this->cfg['edit_window_hours'])) {
            redirect($this->basePath . '/' . $ghId . '/observation/' . $obsId);
            return;
        }

        $this->db->prepare('DELETE FROM observation WHERE id = ?')->execute([$obsId]);
        redirect($this->basePath . '/' . $ghId . '/?deleted=1');
    }

    private function isEditable(string $ts, int $windowHours): bool
    {
        $obsTime = new \DateTimeImmutable($ts, new \DateTimeZone('UTC'));
        $now     = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        return ($now->getTimestamp() - $obsTime->getTimestamp()) < ($windowHours * 3600);
    }

    // ── Forget me ─────────────────────────────────────────────────────────

    private function forget(string $method, string $ghId, array $gh, array $user): void
    {
        if ($method !== 'POST') {
            redirect($this->basePath . '/' . $ghId . '/settings');
            return;
        }

        if (!hash_equals($user['csrf_token'], $_POST['_csrf'] ?? '')) {
            http_response_code(403);
            render('error', [
                'statusCode' => 403,
                'heading'    => lang('error_403_title'),
                'body'       => lang('csrf_invalid'),
            ]);
            return;
        }

        UserSession::forget($this->db, (int)$user['id']);
        redirect($this->basePath . '/' . $ghId . '/register');
    }
}
