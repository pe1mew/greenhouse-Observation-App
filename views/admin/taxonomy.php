<?php require APP_ROOT . '/views/admin/layout/header.php'; ?>
<section>
  <h2>Categorieën</h2>
  <table class="data-table">
    <thead>
      <tr><th>Volgorde</th><th>Interne sleutel</th><th>Naam</th><th style="width:3rem;text-align:center">Tags</th><th>Status</th><th></th></tr>
    </thead>
    <tbody>
      <?php foreach ($categories as $i => $cat): ?>
        <tr>
          <td style="white-space:nowrap">
            <form method="post" action="<?= e($adminBase) ?>/taxonomy/<?= (int)$cat['id'] ?>/move" style="display:inline;margin:0">
              <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">
              <input type="hidden" name="direction" value="up">
              <button type="submit" class="btn btn-sm" <?= $i === 0 ? 'disabled' : '' ?> title="Omhoog">↑</button>
            </form>
            <form method="post" action="<?= e($adminBase) ?>/taxonomy/<?= (int)$cat['id'] ?>/move" style="display:inline;margin:0">
              <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">
              <input type="hidden" name="direction" value="down">
              <button type="submit" class="btn btn-sm" <?= $i === count($categories) - 1 ? 'disabled' : '' ?> title="Omlaag">↓</button>
            </form>
          </td>
          <td><code><?= e($cat['internal_key']) ?></code></td>
          <td><?= e($cat['display_name']) ?></td>
          <td style="text-align:center"><?= (int)$cat['tag_count'] ?></td>
          <td><?= $cat['active_flag'] ? '<span class="badge online">Actief</span>' : '<span class="badge offline">Gearchiveerd</span>' ?></td>
          <td style="display:flex;gap:.25rem;flex-wrap:wrap">
            <a href="<?= e($adminBase) ?>/taxonomy/<?= (int)$cat['id'] ?>" class="btn btn-sm">Tags</a>
            <a href="<?= e($adminBase) ?>/taxonomy/<?= (int)$cat['id'] ?>/edit" class="btn btn-sm"><?= e(lang('edit')) ?></a>
            <form method="post" action="<?= e($adminBase) ?>/taxonomy/<?= (int)$cat['id'] ?>/archive" style="margin:0">
              <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">
              <button type="submit" class="btn btn-sm">
                <?= $cat['active_flag'] ? e(lang('archive')) : e(lang('restore')) ?>
              </button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <p style="margin-top:.75rem">
    <a href="<?= e($adminBase) ?>/taxonomy/new" class="btn">+ Categorie toevoegen</a>
  </p>
</section>
<?php require APP_ROOT . '/views/admin/layout/footer.php'; ?>
