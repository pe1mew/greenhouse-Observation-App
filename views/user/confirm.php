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

  <input type="file" name="photo" id="photo-input"
         accept="image/*" capture="environment" style="display:none">

  <div id="btn-after" style="display:none">
    <img id="photo-preview" src="" alt="preview"
         style="max-width:100%;border-radius:var(--radius);margin-bottom:.5rem;display:block">
    <button type="button" class="primary-cta cta-blue" onclick="retake()">📷 Foto opnieuw maken</button>
    <button type="submit" class="primary-cta cta-teal" style="margin-top:.5rem"><?= e(lang('save')) ?></button>
  </div>

  <div id="btn-initial">
    <button type="button" class="primary-cta cta-blue" onclick="openCamera()">
      📷 <?= e(lang('add_photo')) ?>
    </button>
    <button type="submit" class="primary-cta" style="margin-top:.5rem"><?= e(lang('done')) ?></button>
  </div>
</form>

<script>
  function openCamera() {
    document.getElementById('photo-input').click();
  }

  function retake() {
    var input = document.getElementById('photo-input');
    input.value = '';
    document.getElementById('btn-after').style.display  = 'none';
    document.getElementById('btn-initial').style.display = 'block';
    input.click();
  }

  document.getElementById('photo-input').addEventListener('change', function () {
    if (!this.files.length) return;
    var reader = new FileReader();
    reader.onload = function (e) {
      document.getElementById('photo-preview').src = e.target.result;
      document.getElementById('btn-initial').style.display = 'none';
      document.getElementById('btn-after').style.display   = 'block';
    };
    reader.readAsDataURL(this.files[0]);
  });
</script>
<?php require APP_ROOT . '/views/layout/footer.php'; ?>
