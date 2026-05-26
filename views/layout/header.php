<!DOCTYPE html>
<html lang="nl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($pageTitle ?? 'Greenhouse Observation App') ?></title>
  <link rel="stylesheet" href="<?= e(app_url('assets/css/app.css')) ?>">
</head>
<body class="<?= e($bodyClass ?? 'user-gui') ?>">
<header>
  <h1><?= e($ghName ?? 'Greenhouse Observations') ?></h1>
  <?php if (!empty($handle)): ?>
    <span class="badge online"><?= e($handle) ?></span>
  <?php endif; ?>
</header>
