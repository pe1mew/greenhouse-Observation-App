<?php
$pageTitle   = 'In uitvoering';
$showPrivacy = false;
require APP_ROOT . '/views/layout/header.php';
?>
<section>
  <p class="hint"><?= e($message ?? 'In uitvoering.') ?></p>
</section>
<?php require APP_ROOT . '/views/layout/footer.php'; ?>
