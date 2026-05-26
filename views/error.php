<?php
$pageTitle   = e($heading ?? 'Fout');
$showPrivacy = false;
require APP_ROOT . '/views/layout/header.php';
?>
<section>
  <h2><?= e($heading ?? 'Fout') ?></h2>
  <p><?= e($body ?? '') ?></p>
  <?php if (($statusCode ?? 0) === 404): ?>
    <p class="hint"><a href="<?= e(app_url()) ?>">← Terug naar de startpagina</a></p>
  <?php endif; ?>
</section>
<?php require APP_ROOT . '/views/layout/footer.php'; ?>
