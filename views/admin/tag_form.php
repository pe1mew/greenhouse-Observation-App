<?php require APP_ROOT . '/views/admin/layout/header.php'; ?>
<section>
  <h2><?= e($pageTitle) ?></h2>
  <p class="hint" style="margin-bottom:.5rem">Categorie: <strong><?= e($cat['display_name']) ?></strong></p>
  <?php if ($error): ?>
    <p class="error-msg"><?= e($error) ?></p>
  <?php endif; ?>
  <form method="post" action="">
    <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">
    <div class="row">
      <label>Interne sleutel</label>
      <input type="text" name="internal_key" value="<?= e($values['internal_key'] ?? '') ?>"
             pattern="[a-z0-9_]+" placeholder="bijv. all_good" autofocus class="wide">
    </div>
    <p class="hint" style="margin-bottom:.5rem">Alleen kleine letters, cijfers en underscores. Uniek binnen deze categorie.</p>
    <div class="row">
      <label>Weergavenaam</label>
      <input type="text" name="display_name" value="<?= e($values['display_name'] ?? '') ?>" class="wide">
    </div>
    <button type="submit" class="btn" style="margin-top:.5rem"><?= e(lang('save')) ?></button>
    <a href="<?= e($adminBase) ?>/taxonomy/<?= (int)$cat['id'] ?>" class="btn btn-sm" style="margin-left:.5rem"><?= e(lang('cancel')) ?></a>
  </form>
</section>
<?php require APP_ROOT . '/views/admin/layout/footer.php'; ?>
