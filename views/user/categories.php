<?php
$pageTitle   = lang('what_kind');
$showPrivacy = false;
require APP_ROOT . '/views/layout/header.php';
?>
<section>
  <h2><?= e(lang('what_kind')) ?></h2>
  <ul class="tap-list">
    <?php foreach ($categories as $cat): ?>
      <li>
        <a href="<?= e(app_url($ghId . '/observe/' . $cat['id'] . '/')) ?>">
          <?= e($cat['display_name']) ?>
        </a>
      </li>
    <?php endforeach; ?>
  </ul>
</section>
<p><a href="<?= e(app_url($ghId . '/')) ?>" class="btn btn-sm"><?= e(lang('back')) ?></a></p>
<?php require APP_ROOT . '/views/layout/footer.php'; ?>
