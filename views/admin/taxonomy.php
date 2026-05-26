<?php require APP_ROOT . '/views/admin/layout/header.php'; ?>
<section>
  <h2>Categorieën</h2>
  <table class="data-table">
    <thead>
      <tr><th>Interne sleutel</th><th>Naam</th><th>Tags (actief)</th><th>Status</th><th></th></tr>
    </thead>
    <tbody>
      <?php foreach ($categories as $cat): ?>
        <tr>
          <td><code><?= e($cat['internal_key']) ?></code></td>
          <td><?= e($cat['display_name']) ?></td>
          <td><?= (int)$cat['tag_count'] ?></td>
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
