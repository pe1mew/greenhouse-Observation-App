<?php
$pageTitle   = lang('settings');
$showPrivacy = true;
require APP_ROOT . '/views/layout/header.php';
?>
<section>
  <h2><?= e(lang('change_name')) ?></h2>
  <?php if ($error): ?>
    <p class="error-msg"><?= e($error) ?></p>
  <?php endif; ?>
  <?php if ($success): ?>
    <p class="success-msg"><?= e($success) ?></p>
  <?php endif; ?>
  <form method="post" action="<?= e(app_url($ghId . '/settings')) ?>">
    <input type="hidden" name="_csrf" value="<?= e($user['csrf_token']) ?>">
    <div class="row">
      <input type="text" name="handle" value="<?= e($user['handle']) ?>"
             maxlength="40" autocomplete="nickname">
    </div>
    <button type="submit" class="btn" style="margin-top:.5rem"><?= e(lang('save')) ?></button>
  </form>
</section>

<section>
  <h2><?= e(lang('forget_me')) ?></h2>
  <p class="hint"><?= e(lang('forget_me_done')) ?></p>
  <form method="post" action="<?= e(app_url($ghId . '/forget')) ?>" style="margin-top:.75rem">
    <input type="hidden" name="_csrf" value="<?= e($user['csrf_token']) ?>">
    <button type="submit" class="btn btn-danger"><?= e(lang('forget_me')) ?></button>
  </form>
</section>

<p><a href="<?= e(app_url($ghId . '/')) ?>" class="btn btn-sm"><?= e(lang('back')) ?></a></p>
<?php require APP_ROOT . '/views/layout/footer.php'; ?>
