<?php
$pageTitle   = lang('observation_saved');
$showPrivacy = false;
$homeUrl     = app_url($ghId . '/');
$updateUrl   = app_url($ghId . '/observation/' . $obs['id']);
require APP_ROOT . '/views/layout/header.php';
?>
<section style="text-align:center;padding:2rem 1rem">
  <p style="font-size:3rem;line-height:1;margin-bottom:.5rem">✓</p>
  <p style="font-size:1.6rem;font-weight:600"><?= e(lang('observation_saved')) ?></p>
  <p class="hint" style="margin-top:.5rem">
    <?= e($obs['cat_name']) ?> — <?= e($obs['tag_name']) ?>
  </p>
</section>

<form method="post" action="<?= e($updateUrl) ?>" enctype="multipart/form-data" id="photo-form">
  <input type="hidden" name="_csrf" value="<?= e($user['csrf_token']) ?>">
  <input type="hidden" name="_from" value="confirm">

  <section style="padding:.75rem 1rem">
    <div class="row">
      <label><?= e(lang('timestamp_label')) ?></label>
      <input type="datetime-local" name="ts" value="<?= e($tsDefault) ?>"
             max="<?= e($tsDefault) ?>" style="flex:1">
    </div>
    <div style="margin-bottom:.5rem">
      <label style="display:block;color:var(--muted);font-size:.85rem;margin-bottom:.35rem"><?= e(lang('severity_label')) ?></label>
      <div class="sev-row">
        <label class="sev-btn"><input type="radio" name="severity" value="" checked><span>—</span></label>
        <?php for ($i = 1; $i <= 5; $i++): ?>
          <label class="sev-btn"><input type="radio" name="severity" value="<?= $i ?>"><span><?= $i ?></span></label>
        <?php endfor; ?>
      </div>
    </div>
    <div class="row" style="align-items:flex-start;flex-direction:column">
      <label style="margin-bottom:.25rem"><?= e(lang('note_label')) ?></label>
      <textarea name="note" rows="2"
                style="width:100%;box-sizing:border-box"
                ></textarea>
    </div>
  </section>

  <!--
    Photo input is OUTSIDE the form so the form submit (note/severity/ts)
    doesn't include the file. Auto-upload (FR-REC-040, TDS-UI-160) fires
    a separate POST to /<gh-id>/observation/<id>/photo on `change`, so
    the photo is committed the moment it's picked — no save step needed.
  -->
  <input type="file" id="photo-input"
         accept="image/*" capture="environment" style="display:none">

  <div id="btn-after" style="display:none">
    <img id="photo-preview" src="" alt="preview"
         style="max-width:100%;border-radius:var(--radius);margin-bottom:.5rem;display:block">
    <p id="photo-status" class="hint" style="margin:.25rem 0;font-size:.85rem"></p>
    <button type="button" class="primary-cta cta-blue" onclick="retake()">📷 Foto opnieuw maken</button>
    <button type="submit" class="primary-cta cta-teal" style="margin-top:.5rem"><?= e(lang('done')) ?></button>
  </div>

  <div id="btn-initial">
    <button type="button" class="primary-cta cta-blue" onclick="openCamera()">
      📷 <?= e(lang('add_photo')) ?>
    </button>
    <button type="submit" class="primary-cta" style="margin-top:.5rem"><?= e(lang('done')) ?></button>
  </div>
</form>

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

  var fileInput = document.getElementById('photo-input');
  var preview   = document.getElementById('photo-preview');
  var status    = document.getElementById('photo-status');

  window.openCamera = function () { fileInput.click(); };

  window.retake = function () {
    fileInput.value = '';
    document.getElementById('btn-after').style.display   = 'none';
    document.getElementById('btn-initial').style.display = 'block';
    status.textContent = '';
    status.className   = 'hint';
    fileInput.click();
  };

  fileInput.addEventListener('change', function () {
    if (!this.files.length) return;
    var file = this.files[0];

    // Local preview first (instant UX)
    var reader = new FileReader();
    reader.onload = function (e) {
      preview.src = e.target.result;
      document.getElementById('btn-initial').style.display = 'none';
      document.getElementById('btn-after').style.display   = 'block';
    };
    reader.readAsDataURL(file);

    // Then auto-upload (FR-REC-040 / TDS-UI-160)
    status.textContent = msgs.uploading;
    status.className   = 'hint';
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
<?php require APP_ROOT . '/views/layout/footer.php'; ?>
