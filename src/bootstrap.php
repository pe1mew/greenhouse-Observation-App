<?php
declare(strict_types=1);

use GreenhouseObs\Config;
use GreenhouseObs\Database;
use GreenhouseObs\Router;

require APP_ROOT . '/src/helpers.php';

// ── Exception handler ────────────────────────────────────────────────────
// Catches anything that escapes normal error handling and renders a safe
// Dutch 500 page without leaking paths or traces (TDS-UI-090).
set_exception_handler(function (Throwable $e) {
    error_log('[greenhouse-obs] ' . get_class($e) . ': ' . $e->getMessage()
        . ' in ' . $e->getFile() . ':' . $e->getLine());
    if (!headers_sent()) {
        http_response_code(500);
    }
    render('error', [
        'statusCode' => 500,
        'heading'    => lang('error_500_title'),
        'body'       => lang('error_500_body'),
    ]);
    exit;
});

// ── Configuration ────────────────────────────────────────────────────────
$cfg = Config::load(APP_ROOT . '/config.php');

if ($cfg === null) {
    http_response_code(503);
    render('setup_required', ['configErrors' => ['config_missing']]);
    exit;
}

$configErrors = Config::validate($cfg, rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', '/'));

if (!empty($configErrors)) {
    http_response_code(503);
    render('setup_required', ['configErrors' => $configErrors]);
    exit;
}

// Override error log path if configured (TDS-UI-090)
if (!empty($cfg['error_log_path'])) {
    ini_set('error_log', $cfg['error_log_path']);
}

// ── Storage ──────────────────────────────────────────────────────────────
Database::ensurePhotoRoot($cfg['photo_root']);

// ── Database ─────────────────────────────────────────────────────────────
$db = Database::connect($cfg['db_path']);
Database::migrate($db, APP_ROOT . '/migrations');
Database::seedTaxonomy($db);

// ── Data retention ───────────────────────────────────────────────────────
if ((int)$cfg['retention_days'] > 0 && random_int(0, 49) === 0) {
    $db->prepare(
        "DELETE FROM observation WHERE ts < datetime('now', ? || ' days')"
    )->execute(['-' . (int)$cfg['retention_days']]);
}

// ── Dispatch ─────────────────────────────────────────────────────────────
(new Router($cfg, $db))->dispatch(
    $_SERVER['REQUEST_METHOD'] ?? 'GET',
    $_SERVER['REQUEST_URI']    ?? '/'
);
