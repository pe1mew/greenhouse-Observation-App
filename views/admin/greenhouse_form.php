<?php require APP_ROOT . '/views/admin/layout/header.php'; ?>
<section>
  <h2><?= e($pageTitle) ?></h2>
  <?php if ($error): ?>
    <p class="error-msg"><?= e($error) ?></p>
  <?php endif; ?>
  <form method="post" action="">
    <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">
    <?php if ($isNew): ?>
      <div class="row">
        <label>Kas-ID</label>
        <input type="text" name="id" value="<?= e($values['id']) ?>"
               maxlength="4" class="short" placeholder="5E3F" autofocus>
      </div>
      <p class="hint" style="margin-bottom:.5rem"><?= e(lang('greenhouse_id_format')) ?></p>
    <?php else: ?>
      <div class="row">
        <label>Kas-ID</label>
        <code><?= e($values['id']) ?></code>
      </div>
    <?php endif; ?>
    <div class="row">
      <label>Naam</label>
      <input type="text" name="name" value="<?= e($values['name']) ?>" class="wide"
             <?= $isNew ? '' : 'autofocus' ?>>
    </div>
    <div class="row">
      <label>Locatie</label>
      <input type="text" name="location" value="<?= e($values['location'] ?? '') ?>" class="wide">
    </div>
    <div class="row">
      <label>Notities</label>
      <textarea name="notes"><?= e($values['notes'] ?? '') ?></textarea>
    </div>
    <button type="submit" class="btn" style="margin-top:.5rem"><?= e(lang('save')) ?></button>
    <a href="<?= e($adminBase) ?>/greenhouses" class="btn btn-sm" style="margin-left:.5rem"><?= e(lang('cancel')) ?></a>
  </form>
</section>

<?php if (!$isNew): ?>
<section>
  <h2>Status</h2>
  <p class="hint" style="margin-bottom:.5rem">
    <?= $values['active_flag'] ? 'Deze kas is <strong>actief</strong> en zichtbaar voor gebruikers.' : 'Deze kas is <strong>gearchiveerd</strong> en niet meer zichtbaar voor gebruikers.' ?>
  </p>
  <form method="post" action="<?= e($adminBase) ?>/greenhouses/<?= e($ghId) ?>/archive" style="margin:0">
    <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">
    <button type="submit" class="btn <?= $values['active_flag'] ? '' : 'btn-sm' ?>">
      <?= $values['active_flag'] ? e(lang('archive')) : e(lang('restore')) ?>
    </button>
  </form>
</section>

<section>
  <h2>QR-code</h2>
  <p class="hint" style="margin-bottom:.5rem">URL: <code><?= e($qrUrl) ?></code></p>
  <img src="<?= e($adminBase) ?>/greenhouses/<?= e($ghId) ?>/qr"
       alt="QR code voor <?= e($values['name']) ?>"
       style="display:block;background:#fff;padding:8px;border-radius:4px;max-width:320px">
</section>

<section>
  <h2>Statistieken</h2>
  <p><?= (int)$obsCount ?> waarneming(en) opgeslagen voor deze kas.</p>
  <?php if ($obsCount === 0): ?>
    <form method="post" action="<?= e($adminBase) ?>/greenhouses/<?= e($ghId) ?>/delete"
          style="margin-top:.75rem">
      <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">
      <button type="submit" class="btn btn-danger"
              onclick="return confirm('Kas <?= e($values['name']) ?> verwijderen?')">
        <?= e(lang('delete')) ?>
      </button>
    </form>
  <?php endif; ?>
</section>
<?php endif; ?>
<?php require APP_ROOT . '/views/admin/layout/footer.php'; ?>
