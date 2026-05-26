<!DOCTYPE html>
<html lang="nl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($pageTitle ?? 'Beheer') ?> — Greenhouse Observation App</title>
  <link rel="stylesheet" href="<?= e(app_url('assets/css/app.css')) ?>">
</head>
<body class="admin-gui">
<header>
  <h1>Greenhouse Observation App</h1>
  <?php if (!empty($showNav)): ?>
    <form method="post" action="<?= e($adminBase) ?>/logout" style="margin:0">
      <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">
      <button type="submit" class="btn btn-sm"><?= e(lang('logout')) ?></button>
    </form>
  <?php endif; ?>
</header>
<?php if (!empty($showNav)): ?>
<nav style="display:flex;gap:.4rem;flex-wrap:wrap;margin-bottom:1rem">
  <a href="<?= e($adminBase) ?>/"            class="btn btn-sm">Dashboard</a>
  <a href="<?= e($adminBase) ?>/greenhouses" class="btn btn-sm">Kassen</a>
  <a href="<?= e($adminBase) ?>/taxonomy"    class="btn btn-sm">Taxonomie</a>
  <a href="<?= e($adminBase) ?>/users"       class="btn btn-sm">Gebruikers</a>
  <a href="<?= e($adminBase) ?>/export"      class="btn btn-sm">Export</a>
  <a href="<?= e($adminBase) ?>/password"   class="btn btn-sm">Wachtwoord</a>
</nav>
<?php endif; ?>
