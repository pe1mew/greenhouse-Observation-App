<?php
$pageTitle   = lang('history_heading');
$showPrivacy = false;
require APP_ROOT . '/views/layout/header.php';
$homeUrl = app_url($ghId . '/');
?>
<h2 style="font-size:1rem;margin-bottom:.75rem"><?= e(lang('history_heading')) ?></h2>

<?php if (empty($grouped)): ?>
  <p class="hint"><?= e(lang('history_empty')) ?></p>
<?php else: ?>
  <?php foreach ($grouped as $date => $rows): ?>
    <h3 style="font-size:.8rem;color:var(--muted);margin:.75rem 0 .25rem">
      <?= e((new DateTimeImmutable($date))->format('d-m-Y')) ?>
    </h3>
    <ul class="tap-list" style="margin-bottom:.5rem">
      <?php foreach ($rows as $obs): ?>
        <a href="<?= e(app_url($ghId . '/observation/' . $obs['id'])) ?>"
           style="display:flex;align-items:center;padding:.6rem 1rem;min-height:52px;gap:.5rem;color:inherit;text-decoration:none">
          <span style="flex:1;font-size:.95rem">
            <?= e($obs['cat_name']) ?> — <?= e($obs['tag_name']) ?>
            <?php if ($obs['severity'] !== null): ?>
              <span class="hint" style="font-size:.8rem"> · <?= (int)$obs['severity'] ?>/5</span>
            <?php endif; ?>
            <?php if (!empty($obs['photo_path'])): ?>
              <span style="font-size:.8rem"> 📷</span>
            <?php endif; ?>
          </span>
          <span class="hint" style="font-size:.8rem;white-space:nowrap">
            <?= e(substr(tz_local($obs['ts'], $cfg['timezone']), 11, 5)) ?>
          </span>
        </a>
      <?php endforeach; ?>
    </ul>
  <?php endforeach; ?>
<?php endif; ?>

<p style="margin-top:1rem">
  <a href="<?= e($homeUrl) ?>" class="btn btn-sm"><?= e(lang('back')) ?></a>
</p>
<?php require APP_ROOT . '/views/layout/footer.php'; ?>
