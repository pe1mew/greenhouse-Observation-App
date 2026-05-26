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

  <?php if ($photoUrl): ?>
    <div style="margin-top:.75rem">
      <img src="<?= e($photoUrl) ?>" alt="foto"
           style="max-width:100%;border-radius:var(--radius);display:block">
      <?php if ($editable): ?>
        <form method="post"
              action="<?= e(app_url($ghId . '/observation/' . $obs['id'] . '/photo/delete')) ?>"
              style="margin-top:.4rem">
          <input type="hidden" name="_csrf" value="<?= e($user['csrf_token']) ?>">
          <button type="submit" class="btn btn-danger btn-sm">Foto verwijderen</button>
        </form>
      <?php endif; ?>
    </div>
  <?php endif; ?>
</section>

<?php if ($editable): ?>
<section>
  <h2 style="font-size:1rem;margin-bottom:.5rem"><?= e(lang('edit')) ?></h2>
  <form method="post"
        action="<?= e(app_url($ghId . '/observation/' . $obs['id'])) ?>"
        enctype="multipart/form-data">
    <input type="hidden" name="_csrf" value="<?= e($user['csrf_token']) ?>">
    <div style="margin-bottom:.5rem">
      <label style="display:block;color:var(--muted);font-size:.85rem;margin-bottom:.35rem"><?= e(lang('severity_label')) ?></label>
      <div class="sev-row">
        <label class="sev-btn"><input type="radio" name="severity" value="" <?= $obs['severity'] === null ? 'checked' : '' ?>><span>—</span></label>
        <?php for ($i = 1; $i <= 5; $i++): ?>
          <label class="sev-btn"><input type="radio" name="severity" value="<?= $i ?>" <?= (int)($obs['severity'] ?? 0) === $i ? 'checked' : '' ?>><span><?= $i ?></span></label>
        <?php endfor; ?>
      </div>
    </div>
    <div class="row">
      <label><?= e(lang('note_label')) ?></label>
      <textarea name="note" rows="3" style="width:100%;box-sizing:border-box"><?= e($obs['note'] ?? '') ?></textarea>
    </div>
    <?php if (!$photoUrl): ?>
    <div class="row" style="flex-direction:column;align-items:flex-start">
      <label style="margin-bottom:.25rem">Foto</label>
      <img id="photo-preview" src="" alt=""
           style="display:none;max-width:100%;border-radius:var(--radius);margin-bottom:.4rem">
      <input type="file" name="photo" id="photo-file" accept="image/*"
             style="color:var(--fg)">
      <p class="hint" style="margin-top:.25rem">JPEG, PNG of WebP · max 8 MB</p>
    </div>
    <script>
      document.getElementById('photo-file').addEventListener('change', function () {
        var file = this.files[0];
        if (!file) return;
        var preview = document.getElementById('photo-preview');
        var reader  = new FileReader();
        reader.onload = function (e) {
          preview.src   = e.target.result;
          preview.style.display = 'block';
        };
        reader.readAsDataURL(file);
      });
    </script>
    <?php endif; ?>
    <button type="submit" class="primary-cta cta-blue" style="margin-top:.5rem"><?= e(lang('save')) ?></button>
  </form>
</section>

<section style="margin-top:.5rem">
  <form method="post"
        action="<?= e(app_url($ghId . '/observation/' . $obs['id'] . '/delete')) ?>"
        onsubmit="return confirm('Waarneming verwijderen?')">
    <input type="hidden" name="_csrf" value="<?= e($user['csrf_token']) ?>">
    <button type="submit" class="primary-cta cta-red"><?= e(lang('delete')) ?></button>
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
