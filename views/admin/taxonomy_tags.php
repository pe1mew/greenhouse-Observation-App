<?php require APP_ROOT . '/views/admin/layout/header.php'; ?>
<section>
  <h2><?= e($cat['display_name']) ?> — Tags</h2>
  <p class="hint" style="margin-bottom:.75rem">Categorie: <code><?= e($cat['internal_key']) ?></code></p>
  <table class="data-table">
    <thead>
      <tr><th>Interne sleutel</th><th>Naam</th><th>Waarnemingen</th><th>Status</th><th></th></tr>
    </thead>
    <tbody>
      <?php foreach ($tags as $tag): ?>
        <tr>
          <td><code><?= e($tag['internal_key']) ?></code></td>
          <td><?= e($tag['display_name']) ?></td>
          <td><?= (int)$tag['obs_count'] ?></td>
          <td><?= $tag['active_flag'] ? '<span class="badge online">Actief</span>' : '<span class="badge offline">Gearchiveerd</span>' ?></td>
          <td>
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
