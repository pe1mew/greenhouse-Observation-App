<?php
declare(strict_types=1);
namespace GreenhouseObs;

class Router
{
    private array $cfg;
    private \PDO  $db;
    private string $basePath;

    public function __construct(array $cfg, \PDO $db)
    {
        $this->cfg      = $cfg;
        $this->db       = $db;
        // /greenhouse/index.php → base = /greenhouse
        $this->basePath = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? '/'), '/');
    }

    public function dispatch(string $method, string $requestUri): void
    {
        $path = parse_url($requestUri, PHP_URL_PATH) ?? '/';

        // Strip the app's base path prefix
        if ($this->basePath !== '' && strncmp($path, $this->basePath, strlen($this->basePath)) === 0) {
            $path = substr($path, strlen($this->basePath));
        }
        $path = '/' . ltrim($path, '/');

        // ── Health check (FR-INST-060) ────────────────────────────────
        if ($path === '/health') {
            $this->handleHealth();
            return;
        }

        // ── Admin tree ────────────────────────────────────────────────
        $adminBase = '/' . $this->cfg['admin_url_path'];
        if ($path === $adminBase
            || strncmp($path, $adminBase . '/', strlen($adminBase) + 1) === 0) {
            $this->dispatchAdmin($method, $path, $adminBase);
            return;
        }

        // ── Root (no greenhouse selected) ─────────────────────────────
        if ($path === '/' || $path === '') {
            $this->handleRoot();
            return;
        }

        // ── Greenhouse-scoped: /<gh-id>/... ───────────────────────────
        // Normalise lowercase hex to uppercase (TDS-URL-050)
        if (preg_match('#^/([0-9A-Fa-f]{4})(/.*)?$#', $path, $m)) {
            $ghId = strtoupper($m[1]);
            $sub  = $m[2] ?? '/';
            if ($sub === '') {
                $sub = '/';
            }
            if ($ghId !== $m[1]) {
                redirect($this->basePath . '/' . $ghId . $sub, 301);
                return;
            }
            // Ensure trailing slash on bare /<gh-id>
            if ($sub === '') {
                redirect($this->basePath . '/' . $ghId . '/', 301);
                return;
            }
            $this->dispatchGreenhouse($method, $ghId, $sub);
            return;
        }

        $this->send404();
    }

    // ── Health ────────────────────────────────────────────────────────────

    private function handleHealth(): void
    {
        $dbOk = Database::isReachable($this->db);
        http_response_code($dbOk ? 200 : 503);
        header('Content-Type: application/json; charset=UTF-8');
        header('Cache-Control: no-store');
        echo json_encode([
            'status'  => $dbOk ? 'ok' : 'degraded',
            'version' => APP_VERSION,
            'db'      => $dbOk ? 'reachable' : 'unreachable',
            'ts'      => utc_now(),
        ], JSON_UNESCAPED_SLASHES);
    }

    // ── Greenhouse dispatcher ─────────────────────────────────────────────

    private function dispatchGreenhouse(string $method, string $ghId, string $sub): void
    {
        // Verify this greenhouse exists before routing deeper
        $stmt = $this->db->prepare('SELECT id, name FROM greenhouse WHERE id = ?');
        $stmt->execute([$ghId]);
        $gh = $stmt->fetch();

        if (!$gh) {
            // Unknown greenhouse ID — not in DB yet (FR-GH-080 shape)
            $this->send404();
            return;
        }

        // TODO: implement user-side handlers in next step
        http_response_code(200);
        render('placeholder', [
            'message' => "Kas {$gh['name']} ({$ghId}) — gebruikersinterface in uitvoering.",
        ]);
    }

    // ── Root dispatcher ───────────────────────────────────────────────────

    private function handleRoot(): void
    {
        // TODO: implement FR-GH-080 (scan-QR page / redirect) in next step
        http_response_code(200);
        render('placeholder', [
            'message' => lang('greenhouse_scan_qr'),
        ]);
    }

    // ── Admin dispatcher ──────────────────────────────────────────────────

    private function dispatchAdmin(string $method, string $path, string $adminBase): void
    {
        // TODO: implement admin handlers in next step
        http_response_code(200);
        render('placeholder', [
            'message' => 'Beheerdersinterface — in uitvoering.',
        ]);
    }

    // ── Helpers ───────────────────────────────────────────────────────────

    private function send404(): void
    {
        http_response_code(404);
        render('error', [
            'statusCode' => 404,
            'heading'    => lang('error_404_title'),
            'body'       => lang('error_404_body'),
        ]);
    }
}
