<?php require APP_ROOT . '/views/admin/layout/header.php'; ?>
<section>
  <h2>Exporteren</h2>
  <?php if (empty($greenhouses)): ?>
    <p class="hint">Er zijn nog geen kassen geconfigureerd.</p>
  <?php else: ?>
    <form method="get" action="">
      <div class="row">
        <label>Kas</label>
        <select name="gh_id" style="background:#0d1b2a;color:var(--fg);border:1px solid #334;border-radius:4px;padding:6px 10px;font-size:.9rem">
          <?php foreach ($greenhouses as $gh): ?>
            <option value="<?= e($gh['id']) ?>" <?= $selGhId === $gh['id'] ? 'selected' : '' ?>>
              <?= e($gh['name']) ?> (<?= e($gh['id']) ?>)
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="row">
        <label>Van (datum)</label>
        <input type="date" name="from" value="<?= e($selFrom) ?>" class="short" style="width:auto">
      </div>
      <div class="row">
        <label>Tot (datum)</label>
        <input type="date" name="to" value="<?= e($selTo) ?>" class="short" style="width:auto">
      </div>
      <div class="row">
        <label>Formaat</label>
        <select name="dialect" style="background:#0d1b2a;color:var(--fg);border:1px solid #334;border-radius:4px;padding:6px 10px;font-size:.9rem">
          <option value="A" <?= $selDialect === 'A' ? 'selected' : '' ?>>CSV — standaard (Python/R)</option>
          <option value="B" <?= $selDialect === 'B' ? 'selected' : '' ?>>CSV voor Excel (puntkomma, BOM)</option>
        </select>
      </div>
      <button type="submit" class="btn" style="margin-top:.5rem">Download CSV</button>
    </form>
  <?php endif; ?>
</section>
<?php require APP_ROOT . '/views/admin/layout/footer.php'; ?>
