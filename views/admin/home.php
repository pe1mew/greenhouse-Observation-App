<?php require APP_ROOT . '/views/admin/layout/header.php'; ?>
<section>
  <h2>Dashboard</h2>
  <table class="data-table" style="max-width:360px">
    <tr><th>Kassen</th><td><?= (int)$ghCount ?></td></tr>
    <tr><th>Gebruikers</th><td><?= (int)$userCount ?></td></tr>
    <tr><th>Waarnemingen (totaal)</th><td><?= (int)$obsTotal ?></td></tr>
    <tr><th>Waarnemingen (laatste 24 u)</th><td><?= (int)$obsToday ?></td></tr>
  </table>
</section>
<?php require APP_ROOT . '/views/admin/layout/footer.php'; ?>
