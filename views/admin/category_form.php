<?php require APP_ROOT . '/views/admin/layout/header.php'; ?>
<section>
  <h2><?= e($pageTitle) ?></h2>
  <?php if ($error): ?>
    <p class="error-msg"><?= e($error) ?></p>
  <?php endif; ?>
  <form method="post" action="<?= e($editAction ?? '') ?>">
    <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">
    <div class="row">
      <label>Interne sleutel</label>
      <?php if (!empty($isEdit)): ?>
        <input type="text" value="<?= e($values['internal_key'] ?? '') ?>" disabled class="wide">
      <?php else: ?>
        <input type="text" name="internal_key" value="<?= e($values['internal_key'] ?? '') ?>"
               pattern="[a-z0-9_]+" placeholder="bijv. environment" autofocus class="wide">
        <p class="hint" style="margin-bottom:.5rem">Alleen kleine letters, cijfers en underscores. Niet te wijzigen na aanmaken.</p>
      <?php endif; ?>
    </div>
    <div class="row">
      <label>Weergavenaam</label>
      <input type="text" name="display_name" value="<?= e($values['display_name'] ?? '') ?>"
             class="wide" <?= !empty($isEdit) ? 'autofocus' : '' ?>>
    </div>
    <button type="submit" class="btn" style="margin-top:.5rem"><?= e(lang('save')) ?></button>
    <a href="<?= e($adminBase) ?>/taxonomy" class="btn btn-sm" style="margin-left:.5rem"><?= e(lang('cancel')) ?></a>
  </form>
</section>
<?php require APP_ROOT . '/views/admin/layout/footer.php'; ?>
