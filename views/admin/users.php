<?php require APP_ROOT . '/views/admin/layout/header.php'; ?>
<section>
  <h2>Gebruikers</h2>
  <?php if (empty($users)): ?>
    <p class="hint">Nog geen gebruikers geregistreerd.</p>
  <?php else: ?>
    <table class="data-table">
      <thead>
        <tr>
          <th>Naam</th><th>Aangemaakt</th><th>Laatste bezoek</th>
          <th>Kas</th><th>Waarnemingen</th><th>Status</th><th></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($users as $u): ?>
          <tr>
            <td><?= e($u['handle']) ?></td>
            <td><?= e(substr($u['created_at'], 0, 10)) ?></td>
            <td><?= e(substr($u['last_seen_at'], 0, 10)) ?></td>
            <td><?= e($u['greenhouse_name'] ?? '—') ?></td>
            <td><?= (int)$u['obs_count'] ?></td>
            <td><?= empty($u['cookie_invalidated_at'])
                  ? '<span class="badge online">Actief</span>'
                  : '<span class="badge offline">Vergeten</span>' ?></td>
            <td>
              <?php if (empty($u['cookie_invalidated_at'])): ?>
                <form method="post"
                      action="<?= e($adminBase) ?>/users/<?= (int)$u['id'] ?>/forget"
                      style="margin:0">
                  <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">
                  <button type="submit" class="btn btn-sm btn-danger"
                          onclick="return confirm('Cookie van <?= e($u['handle']) ?> ongeldig maken?')">
                    <?= e(lang('forget_me')) ?>
                  </button>
                </form>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</section>
<?php require APP_ROOT . '/views/admin/layout/footer.php'; ?>
