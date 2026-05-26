<?php require APP_ROOT . '/views/admin/layout/header.php'; ?>
<section>
  <h2>Eerste installatie</h2>
  <p class="hint">Stel een beheerderswachtwoord in om de applicatie te gebruiken.</p>
  <?php if ($error): ?>
    <p class="error-msg"><?= e($error) ?></p>
  <?php endif; ?>
  <form method="post" action="">
    <div class="row">
      <label>Wachtwoord</label>
      <input type="password" name="password" autofocus>
    </div>
    <div class="row">
      <label>Bevestigen</label>
      <input type="password" name="password2">
    </div>
    <button type="submit" class="btn" style="margin-top:.5rem">Instellen</button>
  </form>
</section>
<?php require APP_ROOT . '/views/admin/layout/footer.php'; ?>
