<?php
$pageTitle   = lang('who_is_recording');
$showPrivacy = true;
require APP_ROOT . '/views/layout/header.php';
?>
<section>
  <h2><?= e(lang('who_is_recording')) ?></h2>
  <p class="hint"><?= e(lang('set_once')) ?></p>
  <?php if ($error): ?>
    <p class="error-msg"><?= e($error) ?></p>
  <?php endif; ?>
  <form method="post" action="<?= e(app_url($ghId . '/register')) ?>" style="margin-top:.75rem">
    <div class="row">
      <input type="text" name="handle" value="<?= e($enteredHandle) ?>"
             placeholder="Uw naam" autofocus maxlength="40" autocomplete="nickname">
    </div>
    <button type="submit" class="primary-cta" style="margin-top:.75rem">
      <?= e(lang('save')) ?>
    </button>
  </form>
</section>
<?php require APP_ROOT . '/views/layout/footer.php'; ?>
