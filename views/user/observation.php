<?php
$pageTitle   = lang('obs_detail');
$showPrivacy = false;
require APP_ROOT . '/views/layout/header.php';
$homeUrl = app_url($ghId . '/');
?>
<?php if ($success): ?>
  <p class="success-msg"><?= e($success) ?></p>
<?php endif; ?>
<?php if ($error): ?>
  <p class="error-msg"><?= e($error) ?></p>
<?php endif; ?>

<section>
  <p class="hint" style="font-size:.85rem;margin-bottom:.25rem">
    <?= e(tz_local($obs['ts'], $cfg['timezone'])) ?>
  </p>
  <p style="font-size:1.1rem;font-weight:600;margin-bottom:.25rem">
    <?= e($obs['cat_name']) ?> — <?= e($obs['tag_name']) ?>
  </p>
  <?php if ($obs['severity'] !== null): ?>
    <p class="hint"><?= e(lang('severity_label')) ?>: <strong><?= (int)$obs['severity'] ?>/5</strong></p>
  <?php endif; ?>
  <?php if ($obs['note'] !== null && $obs['note'] !== ''): ?>
    <p style="margin-top:.5rem;white-space:pre-wrap"><?= e($obs['note']) ?></p>
  <?php endif; ?>
</section>

<?php if ($editable): ?>
<section>
  <h2 style="font-size:1rem;margin-bottom:.5rem"><?= e(lang('edit')) ?></h2>
  <form method="post" action="<?= e(app_url($ghId . '/observation/' . $obs['id'])) ?>">
    <input type="hidden" name="_csrf" value="<?= e($user['csrf_token']) ?>">
    <div class="row">
      <label><?= e(lang('severity_label')) ?></label>
      <select name="severity">
        <option value="">—</option>
        <?php for ($i = 1; $i <= 5; $i++): ?>
          <option value="<?= $i ?>" <?= ((int)($obs['severity'] ?? 0) === $i) ? 'selected' : '' ?>><?= $i ?></option>
        <?php endfor; ?>
      </select>
    </div>
    <div class="row">
      <label><?= e(lang('note_label')) ?></label>
      <textarea name="note" rows="3" style="width:100%;box-sizing:border-box"><?= e($obs['note'] ?? '') ?></textarea>
    </div>
    <button type="submit" class="btn" style="margin-top:.25rem"><?= e(lang('save')) ?></button>
  </form>
</section>

<section style="margin-top:.5rem">
  <form method="post"
        action="<?= e(app_url($ghId . '/observation/' . $obs['id'] . '/delete')) ?>"
        onsubmit="return confirm('<?= e(addslashes(lang('observation_deleted'))) ?> — zeker weten?')">
    <input type="hidden" name="_csrf" value="<?= e($user['csrf_token']) ?>">
    <button type="submit" class="btn btn-danger btn-sm"><?= e(lang('delete')) ?></button>
  </form>
</section>
<?php else: ?>
  <p class="hint" style="margin-top:.5rem;font-size:.85rem">
    <?= e(str_replace(':hours', (string)$editWindow, lang('observation_read_only'))) ?>
  </p>
<?php endif; ?>

<p style="margin-top:1rem">
  <a href="<?= e($homeUrl) ?>" class="btn btn-sm"><?= e(lang('back')) ?></a>
</p>
<?php require APP_ROOT . '/views/layout/footer.php'; ?>
