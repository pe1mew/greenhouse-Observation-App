<?php require APP_ROOT . '/views/admin/layout/header.php'; ?>
<section>
  <h2><?= e($pageTitle) ?></h2>

  <?php if ($error): ?>
    <p class="error-msg"><?= e($error) ?></p>
  <?php endif; ?>

  <form method="post"
        action="<?= e($adminBase) ?>/observations/<?= (int)$obs['id'] ?>/edit"
        enctype="multipart/form-data">
    <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">

    <table style="width:auto;margin-bottom:1rem">
      <tr>
        <th style="text-align:right;padding-right:1rem;white-space:nowrap"><?= e(lang('timestamp_label')) ?></th>
        <td>
          <input type="datetime-local" name="ts" value="<?= e($tsLocal) ?>"
                 style="min-width:200px">
          <span class="hint" style="font-size:.75rem"> (<?= e($obs['gh_name']) ?>-tijd)</span>
        </td>
      </tr>
      <tr>
        <th style="text-align:right;padding-right:1rem">Kas</th>
        <td><?= e($obs['gh_name']) ?> (<?= e($obs['greenhouse_id']) ?>)</td>
      </tr>
      <tr>
        <th style="text-align:right;padding-right:1rem">Categorie</th>
        <td>
          <select name="category_id" id="cat-sel" onchange="filterTags()">
            <?php foreach ($categories as $cat): ?>
              <option value="<?= (int)$cat['id'] ?>"
                      <?= (int)$cat['id'] === (int)$obs['category_id'] ? 'selected' : '' ?>>
                <?= e($cat['display_name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </td>
      </tr>
      <tr>
        <th style="text-align:right;padding-right:1rem">Tag</th>
        <td>
          <select name="tag_id" id="tag-sel">
            <?php foreach ($tags as $tag): ?>
              <option value="<?= (int)$tag['id'] ?>"
                      data-cat="<?= (int)$tag['category_id'] ?>"
                      <?= (int)$tag['id'] === (int)$obs['tag_id'] ? 'selected' : '' ?>>
                <?= e($tag['display_name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </td>
      </tr>
      <tr>
        <th style="text-align:right;padding-right:1rem"><?= e(lang('severity_label')) ?></th>
        <td>
          <div class="sev-row" style="max-width:380px">
            <label class="sev-btn"><input type="radio" name="severity" value="" <?= $obs['severity'] === null ? 'checked' : '' ?>><span>—</span></label>
            <?php for ($i = 1; $i <= 5; $i++): ?>
              <label class="sev-btn"><input type="radio" name="severity" value="<?= $i ?>" <?= (int)($obs['severity'] ?? 0) === $i ? 'checked' : '' ?>><span><?= $i ?></span></label>
            <?php endfor; ?>
          </div>
        </td>
      </tr>
      <tr>
        <th style="text-align:right;padding-right:1rem;vertical-align:top"><?= e(lang('note_label')) ?></th>
        <td>
          <textarea name="note" rows="3" style="width:320px;box-sizing:border-box"><?= e($obs['note'] ?? '') ?></textarea>
        </td>
      </tr>
      <tr>
        <th style="text-align:right;padding-right:1rem;vertical-align:top">Foto</th>
        <td>
          <?php if ($photoUrl): ?>
            <img src="<?= e($photoUrl) ?>" alt="foto"
                 style="max-width:300px;border-radius:var(--radius);display:block;margin-bottom:.4rem">
            <form method="post"
                  action="<?= e($adminBase) ?>/observations/<?= (int)$obs['id'] ?>/photo/delete"
                  style="display:inline">
              <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">
              <button type="submit" class="btn btn-danger btn-sm">Foto verwijderen</button>
            </form>
          <?php else: ?>
            <input type="file" name="photo" accept="image/*" style="color:var(--fg)">
            <p class="hint" style="margin-top:.25rem">JPEG, PNG of WebP · max 8 MB</p>
          <?php endif; ?>
        </td>
      </tr>
    </table>

    <button type="submit" class="btn"><?= e(lang('save')) ?></button>
    <a href="<?= e($adminBase) ?>/observations/<?= (int)$obs['id'] ?>"
       class="btn btn-sm" style="margin-left:.5rem"><?= e(lang('cancel')) ?></a>
  </form>
</section>

<script>
function filterTags() {
  var catId = String(document.getElementById('cat-sel').value);
  var sel   = document.getElementById('tag-sel');
  var first = true;
  for (var i = 0; i < sel.options.length; i++) {
    var match = sel.options[i].dataset.cat === catId;
    sel.options[i].style.display = match ? '' : 'none';
    if (match && first) { sel.value = sel.options[i].value; first = false; }
  }
}
filterTags();
document.getElementById('tag-sel').value = '<?= (int)$obs['tag_id'] ?>';
</script>
<?php require APP_ROOT . '/views/admin/layout/footer.php'; ?>
