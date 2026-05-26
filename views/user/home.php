<?php
$pageTitle   = e($gh['name']);
$showPrivacy = true;
require APP_ROOT . '/views/layout/header.php';
?>
<a href="<?= e(app_url($ghId . '/observe/')) ?>" class="primary-cta">
  <?= e(lang('quick_observation')) ?>
</a>

<section style="margin-top:1rem">
  <h2><?= e(lang('recent_heading')) ?></h2>
  <?php if (empty($recent)): ?>
    <p class="hint"><?= e(lang('recent_empty')) ?></p>
  <?php else: ?>
    <ul class="tap-list">
      <?php foreach ($recent as $obs): ?>
        <li style="display:flex;align-items:center;padding:.6rem 1rem;min-height:52px;gap:.5rem">
          <span style="flex:1;font-size:.95rem">
            <?= e($obs['cat_name']) ?> — <?= e($obs['tag_name']) ?>
          </span>
          <span class="hint" style="font-size:.8rem;white-space:nowrap">
            <?= e(substr(tz_display($obs['ts'], $cfg['timezone']), 11, 5)) ?>
          </span>
        </li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>
</section>

<p style="margin-top:.5rem">
  <a href="<?= e(app_url($ghId . '/settings')) ?>" class="btn btn-sm">
    <?= e(lang('settings')) ?>
  </a>
</p>
<?php require APP_ROOT . '/views/layout/footer.php'; ?>
