<?php
$pageTitle   = 'Configuratie vereist';
$bodyClass   = 'admin-gui';
$showPrivacy = false;
require APP_ROOT . '/views/layout/header.php';
?>
<section>
  <h2>Configuratie vereist</h2>
  <?php foreach ($configErrors as $errKey): ?>
    <p class="error-msg"><?= e(lang($errKey)) ?></p>
  <?php endforeach; ?>
  <p class="hint">
    Maak een kopie van <code>template_config.php</code> met de naam
    <code>config.php</code> en vul de vereiste waarden in.
  </p>
</section>
<?php require APP_ROOT . '/views/layout/footer.php'; ?>
