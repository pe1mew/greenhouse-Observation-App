<?php
$pageTitle   = lang('observation_saved');
$showPrivacy = false;
$homeUrl     = app_url($ghId . '/');
$updateUrl   = app_url($ghId . '/observation/' . $obs['id']);
require APP_ROOT . '/views/layout/header.php';
?>
<section style="text-align:center;padding:2rem 1rem">
  <p style="font-size:3rem;line-height:1;margin-bottom:.5rem">✓</p>
  <p style="font-size:1.1rem;font-weight:600"><?= e(lang('observation_saved')) ?></p>
  <p class="hint" style="margin-top:.5rem">
    <?= e($obs['cat_name']) ?> — <?= e($obs['tag_name']) ?>
  </p>
</section>

<form method="post" action="<?= e($updateUrl) ?>" enctype="multipart/form-data" id="photo-form">
  <input type="hidden" name="_csrf" value="<?= e($user['csrf_token']) ?>">
  <input type="hidden" name="_from" value="confirm">

  <section style="padding:.75rem 1rem">
    <div class="row">
      <label><?= e(lang('severity_label')) ?></label>
      <select name="severity">
        <option value="">—</option>
        <?php for ($i = 1; $i <= 5; $i++): ?>
          <option value="<?= $i ?>"><?= $i ?></option>
        <?php endfor; ?>
      </select>
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

  <img id="photo-preview" src="" alt="preview"
       style="display:none;max-width:100%;border-radius:var(--radius);margin-bottom:.75rem">

  <div id="btn-after" style="display:none">
    <button type="submit" class="primary-cta cta-teal"><?= e(lang('save')) ?></button>
    <button type="button" class="primary-cta cta-blue" style="margin-top:.5rem" onclick="retake()">Opnieuw</button>
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
    document.getElementById('photo-preview').style.display = 'none';
    document.getElementById('btn-after').style.display     = 'none';
    document.getElementById('btn-initial').style.display   = 'block';
    input.click();
  }

  document.getElementById('photo-input').addEventListener('change', function () {
    if (!this.files.length) return;
    var reader = new FileReader();
    reader.onload = function (e) {
      var preview = document.getElementById('photo-preview');
      preview.src           = e.target.result;
      preview.style.display = 'block';
      document.getElementById('btn-initial').style.display = 'none';
      document.getElementById('btn-after').style.display   = 'block';
    };
    reader.readAsDataURL(this.files[0]);
  });
</script>
<?php require APP_ROOT . '/views/layout/footer.php'; ?>
