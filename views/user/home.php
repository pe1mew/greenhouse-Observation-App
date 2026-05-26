<?php
$pageTitle   = e($gh['name']);
$showPrivacy = true;
require APP_ROOT . '/views/layout/header.php';
?>
<?php if (isset($_GET['deleted'])): ?>
  <p class="success-msg" style="margin-bottom:.5rem"><?= e(lang('observation_deleted')) ?></p>
<?php endif; ?>
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
        <a href="<?= e(app_url($ghId . '/observation/' . $obs['id'])) ?>"
           style="display:flex;align-items:center;padding:.6rem 1rem;min-height:52px;gap:.5rem;color:inherit;text-decoration:none">
          <span style="flex:1;font-size:.95rem">
            <?= e($obs['cat_name']) ?> — <?= e($obs['tag_name']) ?>
            <?php if ($obs['severity'] !== null): ?>
              <span class="hint" style="font-size:.8rem"> ·  <?= (int)$obs['severity'] ?>/5</span>
            <?php endif; ?>
          </span>
          <span class="hint" style="font-size:.8rem;white-space:nowrap">
            <?= e(substr(tz_display($obs['ts'], $cfg['timezone']), 11, 5)) ?>
          </span>
        </a>
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
