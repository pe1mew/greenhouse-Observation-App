<?php require APP_ROOT . '/views/admin/layout/header.php'; ?>
<section>
  <h2><?= e($cat['display_name']) ?> — Tags</h2>
  <p class="hint" style="margin-bottom:.75rem">Categorie: <code><?= e($cat['internal_key']) ?></code></p>
  <table class="data-table">
    <thead>
      <tr><th>Volgorde</th><th>Interne sleutel</th><th>Naam</th><th>Waarnemingen</th><th>Status</th><th></th></tr>
    </thead>
    <tbody>
      <?php foreach ($tags as $i => $tag): ?>
        <tr>
          <td style="white-space:nowrap">
            <form method="post" action="<?= e($adminBase) ?>/taxonomy/<?= (int)$cat['id'] ?>/tags/<?= (int)$tag['id'] ?>/move" style="display:inline;margin:0">
              <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">
              <input type="hidden" name="direction" value="up">
              <button type="submit" class="btn btn-sm" <?= $i === 0 ? 'disabled' : '' ?> title="Omhoog">↑</button>
            </form>
            <form method="post" action="<?= e($adminBase) ?>/taxonomy/<?= (int)$cat['id'] ?>/tags/<?= (int)$tag['id'] ?>/move" style="display:inline;margin:0">
              <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">
              <input type="hidden" name="direction" value="down">
              <button type="submit" class="btn btn-sm" <?= $i === count($tags) - 1 ? 'disabled' : '' ?> title="Omlaag">↓</button>
            </form>
          </td>
          <td><code><?= e($tag['internal_key']) ?></code></td>
          <td><?= e($tag['display_name']) ?></td>
          <td><?= (int)$tag['obs_count'] ?></td>
          <td><?= $tag['active_flag'] ? '<span class="badge online">Actief</span>' : '<span class="badge offline">Gearchiveerd</span>' ?></td>
          <td style="display:flex;gap:.25rem;flex-wrap:wrap">
            <a href="<?= e($adminBase) ?>/taxonomy/<?= (int)$cat['id'] ?>/tags/<?= (int)$tag['id'] ?>/edit" class="btn btn-sm"><?= e(lang('edit')) ?></a>
            <form method="post"
                  action="<?= e($adminBase) ?>/taxonomy/<?= (int)$cat['id'] ?>/tags/<?= (int)$tag['id'] ?>/archive"
                  style="margin:0">
              <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">
              <button type="submit" class="btn btn-sm">
                <?= $tag['active_flag'] ? e(lang('archive')) : e(lang('restore')) ?>
              </button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <p style="margin-top:.75rem;display:flex;gap:.5rem">
    <a href="<?= e($adminBase) ?>/taxonomy/<?= (int)$cat['id'] ?>/tags/new" class="btn">+ Tag toevoegen</a>
    <a href="<?= e($adminBase) ?>/taxonomy" class="btn btn-sm"><?= e(lang('back')) ?></a>
  </p>
</section>
<?php require APP_ROOT . '/views/admin/layout/footer.php'; ?>
