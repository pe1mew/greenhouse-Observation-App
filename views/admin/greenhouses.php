<?php require APP_ROOT . '/views/admin/layout/header.php'; ?>
<section>
  <h2>Kassen</h2>
  <?php if (empty($greenhouses)): ?>
    <p class="hint">Nog geen kassen. Voeg een kas toe om te beginnen.</p>
  <?php else: ?>
    <table class="data-table">
      <thead>
        <tr><th>ID</th><th>Naam</th><th>Locatie</th><th>Waarnemingen</th><th></th></tr>
      </thead>
      <tbody>
        <?php foreach ($greenhouses as $gh): ?>
          <tr>
            <td><code><?= e($gh['id']) ?></code></td>
            <td><?= e($gh['name']) ?></td>
            <td><?= e($gh['location'] ?? '—') ?></td>
            <td><?= (int)$gh['obs_count'] ?></td>
            <td>
              <a href="<?= e($adminBase) ?>/greenhouses/<?= e($gh['id']) ?>" class="btn btn-sm">Bewerken</a>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
  <p style="margin-top:.75rem">
    <a href="<?= e($adminBase) ?>/greenhouses/new" class="btn">+ Kas toevoegen</a>
  </p>
</section>
<?php require APP_ROOT . '/views/admin/layout/footer.php'; ?>
