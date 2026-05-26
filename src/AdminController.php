<?php
declare(strict_types=1);
namespace GreenhouseObs;

use Endroid\QrCode\Builder\Builder;
use GreenhouseObs\PhotoHandler;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelMedium;
use Endroid\QrCode\Writer\PngWriter;

class AdminController
{
    private array  $cfg;
    private \PDO   $db;
    private string $basePath;
    private string $adminUrl; // full path prefix, e.g. /greenhouse/management

    public function __construct(array $cfg, \PDO $db, string $basePath)
    {
        $this->cfg      = $cfg;
        $this->db       = $db;
        $this->basePath = $basePath;
        $this->adminUrl = $basePath . '/' . $cfg['admin_url_path'];
    }

    public function dispatch(string $method, string $path, string $adminBase): void
    {
        AdminAuth::start($this->cfg);

        $sub = substr($path, strlen($adminBase));
        if ($sub === '' || $sub === '/') {
            $sub = '/';
        }

        // Setup wizard — always reachable
        if ($sub === '/setup' || $sub === '/setup/') {
            $this->handleSetup($method);
            return;
        }

        // Redirect to setup if no admin password is set
        if (AdminAuth::isSetupNeeded($this->db)) {
            redirect($this->adminUrl . '/setup');
            return;
        }

        // Login / logout — no session required
        if ($sub === '/login' || $sub === '/login/') {
            $this->handleLogin($method);
            return;
        }
        if ($sub === '/logout') {
            $this->handleLogout($method);
            return;
        }

        // All other routes require a logged-in session
        if (!AdminAuth::isLoggedIn()) {
            redirect($this->adminUrl . '/login');
            return;
        }

        $csrf = AdminAuth::getCsrf();

        if ($sub === '/') {
            $this->handleHome($csrf);
            return;
        }

        // ── Greenhouses ──────────────────────────────────────────────────
        if ($sub === '/greenhouses' || $sub === '/greenhouses/') {
            $this->handleGreenhouses($method, $csrf);
            return;
        }
        if ($sub === '/greenhouses/new') {
            $this->handleGreenhouseNew($method, $csrf);
            return;
        }
        if (preg_match('#^/greenhouses/([0-9A-F]{4})/qr$#', $sub, $m)) {
            $this->handleGreenhouseQr($m[1]);
            return;
        }
        if (preg_match('#^/greenhouses/([0-9A-F]{4})/delete$#', $sub, $m)) {
            $this->handleGreenhouseDelete($method, $m[1], $csrf);
            return;
        }
        if (preg_match('#^/greenhouses/([0-9A-F]{4})/?$#', $sub, $m)) {
            $this->handleGreenhouseEdit($method, $m[1], $csrf);
            return;
        }

        // ── Taxonomy ─────────────────────────────────────────────────────
        if ($sub === '/taxonomy' || $sub === '/taxonomy/') {
            $this->handleTaxonomy($method, $csrf);
            return;
        }
        if ($sub === '/taxonomy/new') {
            $this->handleCategoryNew($method, $csrf);
            return;
        }
        if (preg_match('#^/taxonomy/(\d+)/archive$#', $sub, $m)) {
            $this->handleCategoryArchive($method, (int)$m[1], $csrf);
            return;
        }
        if (preg_match('#^/taxonomy/(\d+)/move$#', $sub, $m)) {
            $this->handleCategoryMove($method, (int)$m[1], $csrf);
            return;
        }
        if (preg_match('#^/taxonomy/(\d+)/edit$#', $sub, $m)) {
            $this->handleCategoryEdit($method, (int)$m[1], $csrf);
            return;
        }
        if (preg_match('#^/taxonomy/(\d+)/tags/new$#', $sub, $m)) {
            $this->handleTagNew($method, (int)$m[1], $csrf);
            return;
        }
        if (preg_match('#^/taxonomy/(\d+)/tags/(\d+)/archive$#', $sub, $m)) {
            $this->handleTagArchive($method, (int)$m[1], (int)$m[2], $csrf);
            return;
        }
        if (preg_match('#^/taxonomy/(\d+)/tags/(\d+)/move$#', $sub, $m)) {
            $this->handleTagMove($method, (int)$m[1], (int)$m[2], $csrf);
            return;
        }
        if (preg_match('#^/taxonomy/(\d+)/tags/(\d+)/edit$#', $sub, $m)) {
            $this->handleTagEdit($method, (int)$m[1], (int)$m[2], $csrf);
            return;
        }
        if (preg_match('#^/taxonomy/(\d+)/?$#', $sub, $m)) {
            $this->handleTaxonomyTags($method, (int)$m[1], $csrf);
            return;
        }

        // ── Observations ─────────────────────────────────────────────────
        if ($sub === '/observations' || $sub === '/observations/') {
            $this->handleObservations($csrf);
            return;
        }
        if (preg_match('#^/observations/(\d+)/delete$#', $sub, $m)) {
            $this->handleObservationDelete($method, (int)$m[1], $csrf);
            return;
        }
        if (preg_match('#^/observations/(\d+)/photo/delete$#', $sub, $m)) {
            $this->handleObservationPhotoDelete($method, (int)$m[1], $csrf);
            return;
        }
        if (preg_match('#^/observations/(\d+)/photo$#', $sub, $m)) {
            $this->handleObservationPhoto((int)$m[1]);
            return;
        }
        if (preg_match('#^/observations/(\d+)/edit$#', $sub, $m)) {
            $this->handleObservationEdit($method, (int)$m[1], $csrf);
            return;
        }
        if (preg_match('#^/observations/(\d+)/?$#', $sub, $m)) {
            $this->handleObservationDetail((int)$m[1], $csrf);
            return;
        }

        // ── Users ────────────────────────────────────────────────────────
        if ($sub === '/users' || $sub === '/users/') {
            $this->handleUsers($csrf);
            return;
        }
        if (preg_match('#^/users/(\d+)/forget$#', $sub, $m)) {
            $this->handleUserForget($method, (int)$m[1], $csrf);
            return;
        }

        // ── Password change ──────────────────────────────────────────────
        if ($sub === '/password' || $sub === '/password/') {
            $this->handlePassword($method, $csrf);
            return;
        }

        // ── Export ───────────────────────────────────────────────────────
        if ($sub === '/export' || $sub === '/export/') {
            $this->handleExport($csrf);
            return;
        }

        http_response_code(404);
        render('error', [
            'statusCode' => 404,
            'heading'    => lang('error_404_title'),
            'body'       => lang('error_404_body'),
        ]);
    }

    // ── Setup wizard ──────────────────────────────────────────────────────

    private function handleSetup(string $method): void
    {
        if (!AdminAuth::isSetupNeeded($this->db)) {
            redirect($this->adminUrl . '/');
            return;
        }

        $error = null;
        if ($method === 'POST') {
            $pw1 = $_POST['password']  ?? '';
            $pw2 = $_POST['password2'] ?? '';
            if ($pw1 === '') {
                $error = lang('setup_password_required');
            } elseif ($pw1 !== $pw2) {
                $error = lang('setup_password_mismatch');
            } else {
                AdminAuth::setup($this->db, $pw1);
                redirect($this->adminUrl . '/login');
                return;
            }
        }

        http_response_code(200);
        $this->adminRender('setup', ['error' => $error, 'pageTitle' => 'Eerste installatie'], false);
    }

    // ── Login / logout ────────────────────────────────────────────────────

    private function handleLogin(string $method): void
    {
        if (AdminAuth::isLoggedIn()) {
            redirect($this->adminUrl . '/');
            return;
        }

        $error = null;
        if ($method === 'POST') {
            $pw    = $_POST['password'] ?? '';
            $result = AdminAuth::login($this->db, $pw);
            if ($result === null) {
                redirect($this->adminUrl . '/');
                return;
            }
            $ip   = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
            $wait = AdminAuth::lockoutSeconds($this->db, $ip);
            $error = $wait !== null
                ? lang('admin_rate_limited', ['seconds' => (string)$wait])
                : lang($result);
        }

        http_response_code(200);
        $this->adminRender('login', ['error' => $error, 'pageTitle' => 'Inloggen'], false);
    }

    private function handleLogout(string $method): void
    {
        if ($method === 'POST' && AdminAuth::isLoggedIn()
            && AdminAuth::verifyCsrf($_POST['_csrf'] ?? '')) {
            AdminAuth::logout();
        }
        redirect($this->adminUrl . '/login');
    }

    // ── Dashboard ─────────────────────────────────────────────────────────

    private function handleHome(string $csrf): void
    {
        $ghCount   = (int)$this->db->query('SELECT COUNT(*) FROM greenhouse')->fetchColumn();
        $userCount = (int)$this->db->query('SELECT COUNT(*) FROM user')->fetchColumn();
        $obsTotal  = (int)$this->db->query('SELECT COUNT(*) FROM observation')->fetchColumn();
        $obsToday  = (int)$this->db->query(
            "SELECT COUNT(*) FROM observation WHERE ts >= datetime('now','-24 hours')"
        )->fetchColumn();

        $this->adminRender('home', [
            'pageTitle' => 'Dashboard',
            'csrfToken' => $csrf,
            'ghCount'   => $ghCount,
            'userCount' => $userCount,
            'obsTotal'  => $obsTotal,
            'obsToday'  => $obsToday,
        ]);
    }

    // ── Greenhouses ───────────────────────────────────────────────────────

    private function handleGreenhouses(string $method, string $csrf): void
    {
        $greenhouses = $this->db->query(
            'SELECT g.id, g.name, g.location,
                    (SELECT COUNT(*) FROM observation WHERE greenhouse_id = g.id) AS obs_count
             FROM greenhouse g ORDER BY g.name'
        )->fetchAll();

        $this->adminRender('greenhouses', [
            'pageTitle'   => 'Kassen',
            'csrfToken'   => $csrf,
            'greenhouses' => $greenhouses,
        ]);
    }

    private function handleGreenhouseNew(string $method, string $csrf): void
    {
        $error  = null;
        $values = ['id' => '', 'name' => '', 'location' => '', 'notes' => ''];

        if ($method === 'POST') {
            if (!AdminAuth::verifyCsrf($_POST['_csrf'] ?? '')) {
                http_response_code(403);
                render('error', ['statusCode' => 403, 'heading' => lang('error_403_title'), 'body' => lang('csrf_invalid')]);
                return;
            }
            $id       = strtoupper(trim($_POST['id']       ?? ''));
            $name     = trim($_POST['name']     ?? '');
            $location = trim($_POST['location'] ?? '');
            $notes    = trim($_POST['notes']    ?? '');
            $values   = compact('id', 'name', 'location', 'notes');

            if (!preg_match('/^[0-9A-F]{4}$/', $id)) {
                $error = lang('greenhouse_id_format');
            } elseif ($name === '') {
                $error = lang('greenhouse_name_required');
            } else {
                $exists = $this->db->prepare('SELECT id FROM greenhouse WHERE id = ?');
                $exists->execute([$id]);
                if ($exists->fetch()) {
                    $error = lang('greenhouse_id_format'); // reuse — "already exists" implied
                } else {
                    $this->db->prepare(
                        'INSERT INTO greenhouse (id, name, location, notes, created_at) VALUES (?, ?, ?, ?, ?)'
                    )->execute([$id, $name, $location ?: null, $notes ?: null, utc_now()]);
                    redirect($this->adminUrl . '/greenhouses/' . $id);
                    return;
                }
            }
        }

        $this->adminRender('greenhouse_form', [
            'pageTitle' => 'Kas toevoegen',
            'csrfToken' => $csrf,
            'error'     => $error,
            'values'    => $values,
            'isNew'     => true,
        ]);
    }

    private function handleGreenhouseEdit(string $method, string $ghId, string $csrf): void
    {
        $gh = $this->db->prepare('SELECT * FROM greenhouse WHERE id = ?');
        $gh->execute([$ghId]);
        $gh = $gh->fetch();
        if (!$gh) {
            $this->send404();
            return;
        }

        $error  = null;
        $values = ['id' => $gh['id'], 'name' => $gh['name'], 'location' => $gh['location'] ?? '', 'notes' => $gh['notes'] ?? ''];

        if ($method === 'POST') {
            if (!AdminAuth::verifyCsrf($_POST['_csrf'] ?? '')) {
                http_response_code(403);
                render('error', ['statusCode' => 403, 'heading' => lang('error_403_title'), 'body' => lang('csrf_invalid')]);
                return;
            }
            $name     = trim($_POST['name']     ?? '');
            $location = trim($_POST['location'] ?? '');
            $notes    = trim($_POST['notes']    ?? '');
            $values   = array_merge($values, compact('name', 'location', 'notes'));

            if ($name === '') {
                $error = lang('greenhouse_name_required');
            } else {
                $this->db->prepare(
                    'UPDATE greenhouse SET name = ?, location = ?, notes = ? WHERE id = ?'
                )->execute([$name, $location ?: null, $notes ?: null, $ghId]);
                redirect($this->adminUrl . '/greenhouses');
                return;
            }
        }

        $stmt = $this->db->prepare('SELECT COUNT(*) FROM observation WHERE greenhouse_id = ?');
        $stmt->execute([$ghId]);
        $obsCount = (int)$stmt->fetchColumn();

        $qrUrl = app_url($ghId . '/');

        $this->adminRender('greenhouse_form', [
            'pageTitle' => 'Kas bewerken',
            'csrfToken' => $csrf,
            'error'     => $error,
            'values'    => $values,
            'isNew'     => false,
            'ghId'      => $ghId,
            'obsCount'  => $obsCount,
            'qrUrl'     => $qrUrl,
            'adminUrl'  => $this->adminUrl,
        ]);
    }

    private function handleGreenhouseDelete(string $method, string $ghId, string $csrf): void
    {
        if ($method !== 'POST' || !AdminAuth::verifyCsrf($_POST['_csrf'] ?? '')) {
            redirect($this->adminUrl . '/greenhouses/' . $ghId);
            return;
        }
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM observation WHERE greenhouse_id = ?');
        $stmt->execute([$ghId]);
        $count = (int)$stmt->fetchColumn();
        if ($count > 0) {
            // Re-render edit page with error
            $this->handleGreenhouseEdit('GET', $ghId, $csrf);
            return;
        }
        $this->db->prepare('DELETE FROM greenhouse WHERE id = ?')->execute([$ghId]);
        redirect($this->adminUrl . '/greenhouses');
    }

    private function handleGreenhouseQr(string $ghId): void
    {
        $url    = app_url($ghId . '/');
        $result = Builder::create()
            ->writer(new PngWriter())
            ->data($url)
            ->encoding(new Encoding('UTF-8'))
            ->errorCorrectionLevel(new ErrorCorrectionLevelMedium())
            ->size(300)
            ->margin(10)
            ->build();

        header('Content-Type: ' . $result->getMimeType());
        header('Cache-Control: public, max-age=86400');
        echo $result->getString();
        exit;
    }

    // ── Taxonomy ──────────────────────────────────────────────────────────

    private function handleTaxonomy(string $method, string $csrf): void
    {
        $categories = $this->db->query(
            'SELECT c.id, c.internal_key, c.display_name, c.active_flag,
                    (SELECT COUNT(*) FROM tag WHERE category_id = c.id AND active_flag = 1) AS tag_count
             FROM category c ORDER BY c.sort_order, c.id'
        )->fetchAll();

        $this->adminRender('taxonomy', [
            'pageTitle'  => 'Taxonomie',
            'csrfToken'  => $csrf,
            'categories' => $categories,
        ]);
    }

    private function handleCategoryNew(string $method, string $csrf): void
    {
        $error  = null;
        $values = ['internal_key' => '', 'display_name' => ''];

        if ($method === 'POST') {
            if (!AdminAuth::verifyCsrf($_POST['_csrf'] ?? '')) {
                http_response_code(403); render('error', ['statusCode' => 403, 'heading' => lang('error_403_title'), 'body' => lang('csrf_invalid')]); return;
            }
            $key  = trim($_POST['internal_key']  ?? '');
            $name = trim($_POST['display_name'] ?? '');
            $values = compact('key', 'name');

            if ($key === '') {
                $error = lang('tax_key_required');
            } elseif (!preg_match('/^[a-z0-9_]+$/', $key)) {
                $error = lang('tax_key_required');
            } elseif ($name === '') {
                $error = lang('tax_display_name_required');
            } else {
                $ord  = (int)$this->db->query('SELECT COALESCE(MAX(sort_order)+1,0) FROM category')->fetchColumn();
                try {
                    $this->db->prepare(
                        'INSERT INTO category (internal_key, display_name, active_flag, sort_order) VALUES (?, ?, 1, ?)'
                    )->execute([$key, $name, $ord]);
                    redirect($this->adminUrl . '/taxonomy');
                    return;
                } catch (\PDOException $e) {
                    $error = lang('tax_key_taken');
                }
            }
        }

        $this->adminRender('category_form', [
            'pageTitle' => 'Categorie toevoegen',
            'csrfToken' => $csrf,
            'error'     => $error,
            'values'    => $values,
        ]);
    }

    private function handleCategoryArchive(string $method, int $catId, string $csrf): void
    {
        if ($method !== 'POST' || !AdminAuth::verifyCsrf($_POST['_csrf'] ?? '')) {
            redirect($this->adminUrl . '/taxonomy');
            return;
        }
        $row = $this->db->prepare('SELECT active_flag FROM category WHERE id = ?');
        $row->execute([$catId]);
        $row = $row->fetch();
        if ($row) {
            $this->db->prepare('UPDATE category SET active_flag = ? WHERE id = ?')
                     ->execute([$row['active_flag'] ? 0 : 1, $catId]);
        }
        redirect($this->adminUrl . '/taxonomy');
    }

    private function handleCategoryMove(string $method, int $catId, string $csrf): void
    {
        if ($method !== 'POST' || !AdminAuth::verifyCsrf($_POST['_csrf'] ?? '')) {
            redirect($this->adminUrl . '/taxonomy');
            return;
        }
        $direction = $_POST['direction'] ?? '';
        $row = $this->db->prepare('SELECT sort_order FROM category WHERE id = ?');
        $row->execute([$catId]);
        $current = $row->fetch();
        if (!$current) {
            redirect($this->adminUrl . '/taxonomy');
            return;
        }
        $currentOrd = (int)$current['sort_order'];

        if ($direction === 'up') {
            $neighbor = $this->db->prepare(
                'SELECT id, sort_order FROM category WHERE sort_order < ? ORDER BY sort_order DESC LIMIT 1'
            );
        } else {
            $neighbor = $this->db->prepare(
                'SELECT id, sort_order FROM category WHERE sort_order > ? ORDER BY sort_order ASC LIMIT 1'
            );
        }
        $neighbor->execute([$currentOrd]);
        $other = $neighbor->fetch();
        if ($other) {
            $this->db->prepare('UPDATE category SET sort_order = ? WHERE id = ?')->execute([(int)$other['sort_order'], $catId]);
            $this->db->prepare('UPDATE category SET sort_order = ? WHERE id = ?')->execute([$currentOrd, (int)$other['id']]);
        }
        redirect($this->adminUrl . '/taxonomy');
    }

    private function handleTagMove(string $method, int $catId, int $tagId, string $csrf): void
    {
        if ($method !== 'POST' || !AdminAuth::verifyCsrf($_POST['_csrf'] ?? '')) {
            redirect($this->adminUrl . '/taxonomy/' . $catId);
            return;
        }
        $direction = $_POST['direction'] ?? '';
        $row = $this->db->prepare('SELECT sort_order FROM tag WHERE id = ? AND category_id = ?');
        $row->execute([$tagId, $catId]);
        $current = $row->fetch();
        if (!$current) {
            redirect($this->adminUrl . '/taxonomy/' . $catId);
            return;
        }
        $currentOrd = (int)$current['sort_order'];

        if ($direction === 'up') {
            $neighbor = $this->db->prepare(
                'SELECT id, sort_order FROM tag WHERE category_id = ? AND sort_order < ? ORDER BY sort_order DESC LIMIT 1'
            );
        } else {
            $neighbor = $this->db->prepare(
                'SELECT id, sort_order FROM tag WHERE category_id = ? AND sort_order > ? ORDER BY sort_order ASC LIMIT 1'
            );
        }
        $neighbor->execute([$catId, $currentOrd]);
        $other = $neighbor->fetch();
        if ($other) {
            $this->db->prepare('UPDATE tag SET sort_order = ? WHERE id = ?')->execute([(int)$other['sort_order'], $tagId]);
            $this->db->prepare('UPDATE tag SET sort_order = ? WHERE id = ?')->execute([$currentOrd, (int)$other['id']]);
        }
        redirect($this->adminUrl . '/taxonomy/' . $catId);
    }

    private function handleCategoryEdit(string $method, int $catId, string $csrf): void
    {
        $row = $this->db->prepare('SELECT * FROM category WHERE id = ?');
        $row->execute([$catId]);
        $cat = $row->fetch();
        if (!$cat) {
            $this->send404();
            return;
        }

        $error  = null;
        $values = ['internal_key' => $cat['internal_key'], 'display_name' => $cat['display_name']];

        if ($method === 'POST') {
            if (!AdminAuth::verifyCsrf($_POST['_csrf'] ?? '')) {
                http_response_code(403); render('error', ['statusCode' => 403, 'heading' => lang('error_403_title'), 'body' => lang('csrf_invalid')]); return;
            }
            $name = trim($_POST['display_name'] ?? '');
            if ($name === '') {
                $error = lang('tax_display_name_required');
            } else {
                $this->db->prepare('UPDATE category SET display_name = ? WHERE id = ?')
                         ->execute([$name, $catId]);
                redirect($this->adminUrl . '/taxonomy');
                return;
            }
            $values['display_name'] = $name;
        }

        $this->adminRender('category_form', [
            'pageTitle' => 'Categorie bewerken',
            'csrfToken' => $csrf,
            'error'     => $error,
            'values'    => $values,
            'isEdit'    => true,
            'editAction' => $this->adminUrl . '/taxonomy/' . $catId . '/edit',
        ]);
    }

    private function handleTagEdit(string $method, int $catId, int $tagId, string $csrf): void
    {
        $catRow = $this->db->prepare('SELECT * FROM category WHERE id = ?');
        $catRow->execute([$catId]);
        $cat = $catRow->fetch();
        if (!$cat) {
            $this->send404();
            return;
        }

        $tagRow = $this->db->prepare('SELECT * FROM tag WHERE id = ? AND category_id = ?');
        $tagRow->execute([$tagId, $catId]);
        $tag = $tagRow->fetch();
        if (!$tag) {
            $this->send404();
            return;
        }

        $error  = null;
        $values = ['internal_key' => $tag['internal_key'], 'display_name' => $tag['display_name']];

        if ($method === 'POST') {
            if (!AdminAuth::verifyCsrf($_POST['_csrf'] ?? '')) {
                http_response_code(403); render('error', ['statusCode' => 403, 'heading' => lang('error_403_title'), 'body' => lang('csrf_invalid')]); return;
            }
            $name = trim($_POST['display_name'] ?? '');
            if ($name === '') {
                $error = lang('tax_display_name_required');
            } else {
                $this->db->prepare('UPDATE tag SET display_name = ? WHERE id = ?')
                         ->execute([$name, $tagId]);
                redirect($this->adminUrl . '/taxonomy/' . $catId);
                return;
            }
            $values['display_name'] = $name;
        }

        $this->adminRender('tag_form', [
            'pageTitle'  => $cat['display_name'] . ' — Tag bewerken',
            'csrfToken'  => $csrf,
            'error'      => $error,
            'values'     => $values,
            'cat'        => $cat,
            'isEdit'     => true,
            'editAction' => $this->adminUrl . '/taxonomy/' . $catId . '/tags/' . $tagId . '/edit',
        ]);
    }

    private function handleTaxonomyTags(string $method, int $catId, string $csrf): void
    {
        $cat = $this->db->prepare('SELECT * FROM category WHERE id = ?');
        $cat->execute([$catId]);
        $cat = $cat->fetch();
        if (!$cat) {
            $this->send404();
            return;
        }

        $tags = $this->db->prepare(
            'SELECT t.id, t.internal_key, t.display_name, t.active_flag,
                    (SELECT COUNT(*) FROM observation WHERE tag_id = t.id) AS obs_count
             FROM tag t WHERE t.category_id = ? ORDER BY t.sort_order, t.id'
        );
        $tags->execute([$catId]);
        $tags = $tags->fetchAll();

        $this->adminRender('taxonomy_tags', [
            'pageTitle' => $cat['display_name'] . ' — Tags',
            'csrfToken' => $csrf,
            'cat'       => $cat,
            'tags'      => $tags,
        ]);
    }

    private function handleTagNew(string $method, int $catId, string $csrf): void
    {
        $cat = $this->db->prepare('SELECT * FROM category WHERE id = ?');
        $cat->execute([$catId]);
        $cat = $cat->fetch();
        if (!$cat) {
            $this->send404();
            return;
        }

        $error  = null;
        $values = ['internal_key' => '', 'display_name' => ''];

        if ($method === 'POST') {
            if (!AdminAuth::verifyCsrf($_POST['_csrf'] ?? '')) {
                http_response_code(403); render('error', ['statusCode' => 403, 'heading' => lang('error_403_title'), 'body' => lang('csrf_invalid')]); return;
            }
            $key  = trim($_POST['internal_key']  ?? '');
            $name = trim($_POST['display_name'] ?? '');
            $values = compact('key', 'name');

            if ($key === '') {
                $error = lang('tax_key_required');
            } elseif (!preg_match('/^[a-z0-9_]+$/', $key)) {
                $error = lang('tax_key_required');
            } elseif ($name === '') {
                $error = lang('tax_display_name_required');
            } else {
                $stmt2 = $this->db->prepare('SELECT COALESCE(MAX(sort_order)+1,0) FROM tag WHERE category_id = ?');
                $stmt2->execute([$catId]);
                $ord = (int)$stmt2->fetchColumn();
                try {
                    $this->db->prepare(
                        'INSERT INTO tag (category_id, internal_key, display_name, active_flag, sort_order) VALUES (?, ?, ?, 1, ?)'
                    )->execute([$catId, $key, $name, $ord]);
                    redirect($this->adminUrl . '/taxonomy/' . $catId);
                    return;
                } catch (\PDOException $e) {
                    $error = lang('tax_key_taken');
                }
            }
        }

        $this->adminRender('tag_form', [
            'pageTitle' => $cat['display_name'] . ' — Tag toevoegen',
            'csrfToken' => $csrf,
            'error'     => $error,
            'values'    => $values,
            'cat'       => $cat,
        ]);
    }

    private function handleTagArchive(string $method, int $catId, int $tagId, string $csrf): void
    {
        if ($method !== 'POST' || !AdminAuth::verifyCsrf($_POST['_csrf'] ?? '')) {
            redirect($this->adminUrl . '/taxonomy/' . $catId);
            return;
        }
        $row = $this->db->prepare('SELECT active_flag FROM tag WHERE id = ? AND category_id = ?');
        $row->execute([$tagId, $catId]);
        $row = $row->fetch();
        if ($row) {
            $this->db->prepare('UPDATE tag SET active_flag = ? WHERE id = ?')
                     ->execute([$row['active_flag'] ? 0 : 1, $tagId]);
        }
        redirect($this->adminUrl . '/taxonomy/' . $catId);
    }

    // ── Observations ─────────────────────────────────────────────────────

    private function handleObservations(string $csrf): void
    {
        $ghFilter   = trim($_GET['gh_id'] ?? '');
        $fromFilter = trim($_GET['from']  ?? '');
        $toFilter   = trim($_GET['to']    ?? '');

        $sql    = "SELECT o.id, o.ts, o.severity, o.note,
                          g.id AS gh_id, g.name AS gh_name,
                          u.handle,
                          c.display_name AS cat_name, t.display_name AS tag_name
                   FROM observation o
                   JOIN greenhouse g ON g.id = o.greenhouse_id
                   JOIN user u ON u.id = o.user_id
                   JOIN category c ON c.id = o.category_id
                   JOIN tag t ON t.id = o.tag_id
                   WHERE 1=1";
        $params = [];

        if (preg_match('/^[0-9A-F]{4}$/', $ghFilter)) {
            $sql .= ' AND o.greenhouse_id = ?';
            $params[] = $ghFilter;
        }
        if ($fromFilter !== '') {
            $sql .= ' AND o.ts >= ?';
            $params[] = $fromFilter . 'T00:00:00Z';
        }
        if ($toFilter !== '') {
            $sql .= ' AND o.ts <= ?';
            $params[] = $toFilter . 'T23:59:59Z';
        }
        $sql .= ' ORDER BY o.ts DESC LIMIT 200';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $observations = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $greenhouses = $this->db->query('SELECT id, name FROM greenhouse ORDER BY name')->fetchAll();

        $this->adminRender('observations', [
            'pageTitle'    => lang('observations'),
            'csrfToken'    => $csrf,
            'observations' => $observations,
            'greenhouses'  => $greenhouses,
            'selGhId'      => $ghFilter,
            'selFrom'      => $fromFilter,
            'selTo'        => $toFilter,
            'cfg'          => $this->cfg,
        ]);
    }

    private function handleObservationDetail(int $obsId, string $csrf): void
    {
        $stmt = $this->db->prepare(
            'SELECT o.id, o.ts, o.severity, o.note, o.photo_path, o.created_at, o.updated_at,
                    g.id AS gh_id, g.name AS gh_name,
                    u.id AS user_id, u.handle,
                    c.display_name AS cat_name, t.display_name AS tag_name
             FROM observation o
             JOIN greenhouse g ON g.id = o.greenhouse_id
             JOIN user u ON u.id = o.user_id
             JOIN category c ON c.id = o.category_id
             JOIN tag t ON t.id = o.tag_id
             WHERE o.id = ?'
        );
        $stmt->execute([$obsId]);
        $obs = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$obs) {
            $this->send404();
            return;
        }

        $this->adminRender('observation_detail', [
            'pageTitle' => lang('obs_detail') . ' #' . $obsId,
            'csrfToken' => $csrf,
            'obs'       => $obs,
            'cfg'       => $this->cfg,
            'updated'   => isset($_GET['updated']),
        ]);
    }

    private function handleObservationPhoto(int $obsId): void
    {
        $stmt = $this->db->prepare('SELECT photo_path FROM observation WHERE id = ?');
        $stmt->execute([$obsId]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$row || empty($row['photo_path'])) {
            http_response_code(404);
            exit;
        }

        PhotoHandler::serve($this->cfg['photo_root'], (string)$row['photo_path']);
    }

    private function handleObservationEdit(string $method, int $obsId, string $csrf): void
    {
        $stmt = $this->db->prepare(
            'SELECT o.*, c.display_name AS cat_name, t.display_name AS tag_name,
                    g.name AS gh_name
             FROM observation o
             JOIN category c ON c.id = o.category_id
             JOIN tag t ON t.id = o.tag_id
             JOIN greenhouse g ON g.id = o.greenhouse_id
             WHERE o.id = ?'
        );
        $stmt->execute([$obsId]);
        $obs = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$obs) {
            redirect($this->adminUrl . '/observations');
            return;
        }

        $categories = $this->db->query(
            'SELECT id, display_name FROM category ORDER BY sort_order, id'
        )->fetchAll(\PDO::FETCH_ASSOC);

        $tags = $this->db->query(
            'SELECT id, category_id, display_name FROM tag ORDER BY category_id, sort_order, id'
        )->fetchAll(\PDO::FETCH_ASSOC);

        $error   = null;
        $success = null;

        if ($method === 'POST') {
            if (!AdminAuth::verifyCsrf($_POST['_csrf'] ?? '')) {
                http_response_code(403);
                render('error', ['statusCode' => 403, 'heading' => lang('error_403_title'), 'body' => lang('csrf_invalid')]);
                return;
            }

            $catId  = (int)($_POST['category_id'] ?? 0);
            $tagId  = (int)($_POST['tag_id']      ?? 0);
            $sevRaw = trim($_POST['severity'] ?? '');
            $sev    = $sevRaw !== '' ? (int)$sevRaw : null;
            $note   = trim($_POST['note']   ?? '');
            $tsRaw  = trim($_POST['ts']     ?? '');

            // Validate category + tag relationship
            $tagRow = null;
            foreach ($tags as $t) {
                if ((int)$t['id'] === $tagId && (int)$t['category_id'] === $catId) {
                    $tagRow = $t;
                    break;
                }
            }

            if (!$tagRow) {
                $error = 'Ongeldige combinatie van categorie en tag.';
            } elseif ($sev !== null && ($sev < 1 || $sev > 5)) {
                $error = lang('severity_invalid');
            } else {
                // Parse timestamp
                $newTs = $obs['ts'];
                if ($tsRaw !== '') {
                    $tz = new \DateTimeZone($this->cfg['timezone']);
                    $dt = \DateTimeImmutable::createFromFormat('Y-m-d\TH:i', $tsRaw, $tz);
                    if ($dt !== false) {
                        $newTs = $dt->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d\TH:i:s\Z');
                    }
                }

                // Handle photo
                $photoPath = $obs['photo_path'];
                $upload    = $_FILES['photo'] ?? null;
                if ($upload && $upload['error'] === UPLOAD_ERR_OK) {
                    $photoError = PhotoHandler::validate($upload);
                    if ($photoError) {
                        $error = lang($photoError);
                    } else {
                        if ($photoPath) {
                            PhotoHandler::delete($this->cfg['photo_root'], $photoPath);
                        }
                        $photoPath = PhotoHandler::store($upload, $this->cfg['photo_root'], $obsId);
                    }
                }

                if (!$error) {
                    $this->db->prepare(
                        'UPDATE observation
                         SET ts = ?, category_id = ?, tag_id = ?, severity = ?,
                             note = ?, photo_path = ?, updated_at = ?
                         WHERE id = ?'
                    )->execute([$newTs, $catId, $tagId, $sev, $note ?: null, $photoPath, utc_now(), $obsId]);

                    $this->db->prepare(
                        'INSERT INTO admin_audit (ts, action, target_kind, target_id, details) VALUES (?, ?, ?, ?, ?)'
                    )->execute([utc_now(), 'edit', 'observation', (string)$obsId, null]);

                    redirect($this->adminUrl . '/observations/' . $obsId . '?updated=1');
                    return;
                }
            }

            // Re-fetch obs for re-render after error
            $stmt->execute([$obsId]);
            $obs = $stmt->fetch(\PDO::FETCH_ASSOC);
        }

        // Build ts default for datetime-local input
        $tz        = new \DateTimeZone($this->cfg['timezone']);
        $tsLocal   = (new \DateTimeImmutable((string)$obs['ts'], new \DateTimeZone('UTC')))
                         ->setTimezone($tz)->format('Y-m-d\TH:i');
        $photoUrl  = !empty($obs['photo_path'])
                       ? $this->adminUrl . '/observations/' . $obsId . '/photo'
                       : null;

        $this->adminRender('observation_edit', [
            'pageTitle'  => lang('obs_detail') . ' #' . $obsId . ' — ' . lang('edit'),
            'csrfToken'  => $csrf,
            'obs'        => $obs,
            'categories' => $categories,
            'tags'       => $tags,
            'tsLocal'    => $tsLocal,
            'photoUrl'   => $photoUrl,
            'error'      => $error,
            'success'    => $success,
        ]);
    }

    private function handleObservationPhotoDelete(string $method, int $obsId, string $csrf): void
    {
        if ($method !== 'POST' || !AdminAuth::verifyCsrf($_POST['_csrf'] ?? '')) {
            redirect($this->adminUrl . '/observations/' . $obsId . '/edit');
            return;
        }

        $stmt = $this->db->prepare('SELECT photo_path FROM observation WHERE id = ?');
        $stmt->execute([$obsId]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        if ($row && !empty($row['photo_path'])) {
            PhotoHandler::delete($this->cfg['photo_root'], (string)$row['photo_path']);
            $this->db->prepare(
                'UPDATE observation SET photo_path = NULL, updated_at = ? WHERE id = ?'
            )->execute([utc_now(), $obsId]);
        }

        redirect($this->adminUrl . '/observations/' . $obsId . '/edit');
    }

    private function handleObservationDelete(string $method, int $obsId, string $csrf): void
    {
        if ($method !== 'POST' || !AdminAuth::verifyCsrf($_POST['_csrf'] ?? '')) {
            redirect($this->adminUrl . '/observations/' . $obsId);
            return;
        }

        $stmt = $this->db->prepare('SELECT id FROM observation WHERE id = ?');
        $stmt->execute([$obsId]);
        if (!$stmt->fetch()) {
            redirect($this->adminUrl . '/observations');
            return;
        }

        $this->db->prepare('DELETE FROM observation WHERE id = ?')->execute([$obsId]);

        $this->db->prepare(
            'INSERT INTO admin_audit (ts, action, target_kind, target_id, details) VALUES (?, ?, ?, ?, ?)'
        )->execute([utc_now(), 'delete', 'observation', (string)$obsId, null]);

        redirect($this->adminUrl . '/observations?deleted=1');
    }

    // ── Users ─────────────────────────────────────────────────────────────

    private function handleUsers(string $csrf): void
    {
        $users = $this->db->query(
            'SELECT u.id, u.handle, u.created_at, u.last_seen_at,
                    u.cookie_invalidated_at,
                    (SELECT COUNT(*) FROM observation WHERE user_id = u.id) AS obs_count,
                    g.name AS greenhouse_name
             FROM user u
             LEFT JOIN greenhouse g ON g.id = u.current_greenhouse_id
             ORDER BY u.last_seen_at DESC'
        )->fetchAll();

        $this->adminRender('users', [
            'pageTitle' => 'Gebruikers',
            'csrfToken' => $csrf,
            'users'     => $users,
        ]);
    }

    private function handleUserForget(string $method, int $userId, string $csrf): void
    {
        if ($method !== 'POST' || !AdminAuth::verifyCsrf($_POST['_csrf'] ?? '')) {
            redirect($this->adminUrl . '/users');
            return;
        }
        $stmt = $this->db->prepare('SELECT handle FROM user WHERE id = ?');
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        if ($user) {
            $this->db->prepare(
                'UPDATE user SET current_cookie_token = ?, csrf_token = ?, cookie_invalidated_at = ? WHERE id = ?'
            )->execute([
                bin2hex(random_bytes(16)),
                bin2hex(random_bytes(16)),
                utc_now(),
                $userId,
            ]);
        }
        redirect($this->adminUrl . '/users');
    }

    // ── Password change ───────────────────────────────────────────────────

    private function handlePassword(string $method, string $csrf): void
    {
        $error   = null;
        $success = null;

        if ($method === 'POST') {
            if (!AdminAuth::verifyCsrf($_POST['_csrf'] ?? '')) {
                http_response_code(403);
                render('error', ['statusCode' => 403, 'heading' => lang('error_403_title'), 'body' => lang('csrf_invalid')]);
                return;
            }
            $current = $_POST['current_password'] ?? '';
            $new1    = $_POST['new_password']     ?? '';
            $new2    = $_POST['new_password2']    ?? '';

            $row = $this->db->query('SELECT password_hash FROM admin WHERE id = 1')->fetch();
            if (!$row || !password_verify($current, (string)$row['password_hash'])) {
                $error = lang('admin_invalid_credentials');
            } elseif ($new1 === '') {
                $error = lang('setup_password_required');
            } elseif ($new1 !== $new2) {
                $error = lang('setup_password_mismatch');
            } else {
                $this->db->prepare(
                    'UPDATE admin SET password_hash = ?, password_updated_at = ? WHERE id = 1'
                )->execute([password_hash($new1, PASSWORD_DEFAULT), utc_now()]);
                $success = 'Wachtwoord gewijzigd.';
            }
        }

        $this->adminRender('password', [
            'pageTitle' => 'Wachtwoord wijzigen',
            'csrfToken' => $csrf,
            'error'     => $error,
            'success'   => $success,
        ]);
    }

    // ── Export ────────────────────────────────────────────────────────────

    private function handleExport(string $csrf): void
    {
        $ghId    = trim($_GET['gh_id']   ?? '');
        $from    = trim($_GET['from']    ?? '');
        $to      = trim($_GET['to']      ?? '');
        $dialect = ($_GET['dialect'] ?? 'A') === 'B' ? 'B' : 'A';

        $greenhouses = $this->db->query('SELECT id, name FROM greenhouse ORDER BY name')->fetchAll();

        // If greenhouse selected and valid, stream CSV
        if ($ghId !== '' && preg_match('/^[0-9A-F]{4}$/', $ghId)) {
            $this->streamCsv($ghId, $from, $to, $dialect);
            return;
        }

        $this->adminRender('export', [
            'pageTitle'   => 'Exporteren',
            'csrfToken'   => $csrf,
            'greenhouses' => $greenhouses,
            'selGhId'     => $ghId,
            'selFrom'     => $from,
            'selTo'       => $to,
            'selDialect'  => $dialect,
        ]);
    }

    private function streamCsv(string $ghId, string $from, string $to, string $dialect): void
    {
        $sep = $dialect === 'B' ? ';' : ',';

        $sql    = "SELECT o.greenhouse_id, o.id AS observation_id, o.ts,
                          u.id AS user_id, u.handle AS user_handle,
                          c.internal_key AS category_key, c.display_name AS category_display,
                          t.internal_key AS tag_key, t.display_name AS tag_display,
                          o.severity, o.note, o.photo_path
                   FROM observation o
                   JOIN user u ON u.id = o.user_id
                   JOIN category c ON c.id = o.category_id
                   JOIN tag t ON t.id = o.tag_id
                   WHERE o.greenhouse_id = ?";
        $params = [$ghId];
        if ($from !== '') {
            $sql .= ' AND o.ts >= ?';
            $params[] = $from . 'T00:00:00Z';
        }
        if ($to !== '') {
            $sql .= ' AND o.ts <= ?';
            $params[] = $to . 'T23:59:59Z';
        }
        $sql .= ' ORDER BY o.ts';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        $fromPart = $from !== '' ? str_replace('-', '', $from) : 'all';
        $toPart   = $to   !== '' ? str_replace('-', '', $to)   : 'all';
        $suffix   = $dialect === 'B' ? '_excel' : '';
        $filename = "observations_{$ghId}_{$fromPart}_{$toPart}{$suffix}.csv";

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-store');

        $tz = new \DateTimeZone($this->cfg['timezone']);

        if ($dialect === 'B') {
            echo "\xEF\xBB\xBF"; // UTF-8 BOM
        }
        echo $this->csvLine([
            'greenhouse_id', 'observation_id', 'ts_iso8601', 'user_id', 'user_handle',
            'category_key', 'category_display', 'tag_key', 'tag_display',
            'severity', 'note', 'photo_filename',
        ], $sep);

        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $dt    = new \DateTimeImmutable((string)$row['ts'], new \DateTimeZone('UTC'));
            $local = $dt->setTimezone($tz);
            $ts    = $dialect === 'B' ? $local->format('Y-m-d H:i:sP') : $local->format('Y-m-d\TH:i:sP');
            echo $this->csvLine([
                $row['greenhouse_id'],
                $row['observation_id'],
                $ts,
                $row['user_id'],
                $row['user_handle'],
                $row['category_key'],
                $row['category_display'],
                $row['tag_key'],
                $row['tag_display'],
                $row['severity'] ?? '',
                $row['note'] ?? '',
                $row['photo_path'] ? basename((string)$row['photo_path']) : '',
            ], $sep);
        }
        exit;
    }

    // ── Helpers ───────────────────────────────────────────────────────────

    private function adminRender(string $view, array $data = [], bool $showNav = true): void
    {
        render('admin/' . $view, array_merge([
            'adminBase' => app_url($this->cfg['admin_url_path']),
            'adminUrl'  => $this->adminUrl,
            'csrfToken' => AdminAuth::isLoggedIn() ? AdminAuth::getCsrf() : '',
            'showNav'   => $showNav,
        ], $data));
    }

    private function send404(): void
    {
        http_response_code(404);
        render('error', [
            'statusCode' => 404,
            'heading'    => lang('error_404_title'),
            'body'       => lang('error_404_body'),
        ]);
    }

    private function csvLine(array $fields, string $sep): string
    {
        $parts = [];
        foreach ($fields as $f) {
            $f = (string)$f;
            if (strpbrk($f, $sep . '"\n\r') !== false) {
                $f = '"' . str_replace('"', '""', $f) . '"';
            }
            $parts[] = $f;
        }
        return implode($sep, $parts) . "\r\n";
    }
}
