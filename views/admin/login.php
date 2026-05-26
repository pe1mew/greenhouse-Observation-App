<?php require APP_ROOT . '/views/admin/layout/header.php'; ?>
<section>
  <h2>Inloggen</h2>
  <?php if ($error): ?>
    <p class="error-msg"><?= e($error) ?></p>
  <?php endif; ?>
  <form method="post" action="">
    <div class="row">
      <label>Wachtwoord</label>
      <input type="password" name="password" autofocus>
    </div>
    <button type="submit" class="btn" style="margin-top:.5rem">Inloggen</button>
  </form>
</section>
<?php require APP_ROOT . '/views/admin/layout/footer.php'; ?>
