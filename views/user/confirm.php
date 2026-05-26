<?php
$pageTitle   = lang('observation_saved');
$showPrivacy = false;
$homeUrl     = app_url($ghId . '/');
require APP_ROOT . '/views/layout/header.php';
?>
<section style="text-align:center;padding:2rem 1rem">
  <p style="font-size:3rem;line-height:1;margin-bottom:.5rem">✓</p>
  <p style="font-size:1.1rem;font-weight:600"><?= e(lang('observation_saved')) ?></p>
  <p class="hint" style="margin-top:.5rem">
    <?= e($obs['cat_name']) ?> — <?= e($obs['tag_name']) ?>
  </p>
</section>
<p style="text-align:center">
  <a href="<?= e($homeUrl) ?>" class="btn"><?= e(lang('done')) ?></a>
</p>
<script>
  setTimeout(function () {
    window.location.replace(<?= json_encode($homeUrl) ?>);
  }, 2000);
</script>
<?php require APP_ROOT . '/views/layout/footer.php'; ?>
