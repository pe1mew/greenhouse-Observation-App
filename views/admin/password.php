<?php require APP_ROOT . '/views/admin/layout/header.php'; ?>
<section>
  <h2>Wachtwoord wijzigen</h2>
  <?php if ($error): ?>
    <p class="error-msg"><?= e($error) ?></p>
  <?php endif; ?>
  <?php if ($success): ?>
    <p class="success-msg"><?= e($success) ?></p>
  <?php endif; ?>
  <form method="post" action="">
    <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">
    <div class="row">
      <label>Huidig wachtwoord</label>
      <input type="password" name="current_password" autofocus>
    </div>
    <div class="row">
      <label>Nieuw wachtwoord</label>
      <input type="password" name="new_password">
    </div>
    <div class="row">
      <label>Bevestigen</label>
      <input type="password" name="new_password2">
    </div>
    <button type="submit" class="btn" style="margin-top:.5rem">Wijzigen</button>
  </form>
</section>
<?php require APP_ROOT . '/views/admin/layout/footer.php'; ?>
