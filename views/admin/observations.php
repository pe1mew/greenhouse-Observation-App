<?php require APP_ROOT . '/views/admin/layout/header.php'; ?>
<?php if (isset($_GET['deleted'])): ?>
  <p class="success-msg"><?= e(lang('obs_admin_deleted')) ?></p>
<?php endif; ?>
<section>
  <h2><?= e(lang('observations')) ?></h2>
  <form method="get" style="display:flex;gap:.5rem;flex-wrap:wrap;margin-bottom:1rem;align-items:flex-end">
    <div>
      <label style="display:block;font-size:.8rem;margin-bottom:.2rem">Kas</label>
      <select name="gh_id">
        <option value="">— alle —</option>
        <?php foreach ($greenhouses as $g): ?>
          <option value="<?= e($g['id']) ?>" <?= $selGhId === $g['id'] ? 'selected' : '' ?>>
            <?= e($g['name']) ?> (<?= e($g['id']) ?>)
          </option>
        <?php endforeach; ?>
      </select>
    </div>
    <div>
      <label style="display:block;font-size:.8rem;margin-bottom:.2rem">Van</label>
      <input type="date" name="from" value="<?= e($selFrom) ?>">
    </div>
    <div>
      <label style="display:block;font-size:.8rem;margin-bottom:.2rem">Tot</label>
      <input type="date" name="to" value="<?= e($selTo) ?>">
    </div>
    <button type="submit" class="btn btn-sm">Zoeken</button>
  </form>

  <?php if (empty($observations)): ?>
    <p class="hint"><?= e(lang('no_observations')) ?></p>
  <?php else: ?>
    <p class="hint" style="margin-bottom:.5rem"><?= count($observations) ?> waarneming(en)</p>
    <div style="overflow-x:auto">
      <table class="data-table">
        <thead>
          <tr>
            <th>#</th>
            <th>Tijdstip</th>
            <th>Kas</th>
            <th>Gebruiker</th>
            <th>Categorie</th>
            <th>Tag</th>
            <th>Ernst</th>
            <th>Opmerking</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($observations as $o): ?>
            <tr>
              <td><?= (int)$o['id'] ?></td>
              <td style="white-space:nowrap"><?= e(tz_local($o['ts'], $cfg['timezone'])) ?></td>
              <td><?= e($o['gh_name']) ?></td>
              <td><?= e($o['handle']) ?></td>
              <td><?= e($o['cat_name']) ?></td>
              <td><?= e($o['tag_name']) ?></td>
              <td><?= $o['severity'] !== null ? (int)$o['severity'] : '—' ?></td>
              <td style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
                <?= e($o['note'] ?? '') ?>
              </td>
              <td>
                <a href="<?= e($adminBase) ?>/observations/<?= (int)$o['id'] ?>" class="btn btn-sm">
                  Detail
                </a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</section>
<?php require APP_ROOT . '/views/admin/layout/footer.php'; ?>
