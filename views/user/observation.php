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
    <button type="submit" class="primary-cta cta-blue" style="margin-top:.5rem"><?= e(lang('save')) ?></button>
  </form>

  <?php if (!$photoUrl): ?>
  <!--
    Photo input is OUTSIDE the edit form so the save above only handles
    note/severity. Auto-upload (FR-REC-040, TDS-UI-160) fires a separate
    POST to /<gh-id>/observation/<id>/photo on `change`.
  -->
  <div style="margin-top:1rem;display:flex;flex-direction:column;align-items:flex-start">
    <label style="margin-bottom:.25rem">Foto</label>
    <img id="photo-preview" src="" alt=""
         style="display:none;max-width:100%;border-radius:var(--radius);margin-bottom:.4rem">
    <input type="file" id="photo-file" accept="image/*"
           style="color:var(--fg)">
    <p id="photo-status" class="hint" style="margin:.25rem 0;font-size:.85rem"></p>
    <p class="hint" style="margin-top:.1rem">JPEG, PNG of WebP · max 8 MB</p>
  </div>
  <script>
  (function () {
    var photoUrl  = <?= json_encode(app_url($ghId . '/observation/' . $obs['id'] . '/photo'), JSON_UNESCAPED_SLASHES) ?>;
    var csrfToken = <?= json_encode($user['csrf_token']) ?>;
    var msgs = {
      uploading: <?= json_encode(lang('photo_uploading')) ?>,
      saved:     <?= json_encode(lang('photo_saved')) ?>,
      failed:    <?= json_encode(lang('photo_upload_failed')) ?>,
      conn:      <?= json_encode(lang('photo_connection_error')) ?>
    };

    var fileInput = document.getElementById('photo-file');
    var preview   = document.getElementById('photo-preview');
    var status    = document.getElementById('photo-status');

    fileInput.addEventListener('change', function () {
      if (!this.files.length) return;
      var file = this.files[0];

      // Local preview (instant UX)
      var reader = new FileReader();
      reader.onload = function (e) {
        preview.src           = e.target.result;
        preview.style.display = 'block';
      };
      reader.readAsDataURL(file);

      // Auto-upload (TDS-UI-160)
      status.textContent = msgs.uploading;
      status.style.color = '';

      var fd = new FormData();
      fd.append('photo', file);
      fd.append('_csrf', csrfToken);

      fetch(photoUrl, {
        method: 'POST',
        body:   fd,
        headers: { 'X-CSRF-Token': csrfToken },
        credentials: 'same-origin'
      })
      .then(function (r) { return r.json().then(function (d) { return { ok: r.ok, data: d }; }); })
      .then(function (res) {
        if (res.ok && res.data.ok) {
          status.textContent = res.data.message || msgs.saved;
          status.style.color = 'var(--ok, #2a8c4a)';
          // Reload after a moment so the page now shows the photo
          // in its proper place + offers the delete button.
          setTimeout(function () { window.location.reload(); }, 800);
        } else {
          status.textContent = (res.data && res.data.error) ? res.data.error : msgs.failed;
          status.style.color = 'var(--danger, #c0392b)';
        }
      })
      .catch(function () {
        status.textContent = msgs.conn;
        status.style.color = 'var(--danger, #c0392b)';
      });
    });
  })();
  </script>
  <?php endif; ?>
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
