<?php
$pageTitle   = lang('app_name');
$showPrivacy = true;
require APP_ROOT . '/views/layout/header.php';
?>
<section>
  <?php if (!empty($archivedMsg)): ?>
    <p class="hint"><?= e($archivedMsg) ?></p>
  <?php elseif (empty($greenhouses) && !$showList): ?>
    <p><?= e(lang('greenhouse_scan_qr')) ?></p>
  <?php elseif (empty($greenhouses)): ?>
    <p><?= e(lang('greenhouse_none_configured')) ?></p>
  <?php else: ?>
    <p><?= e(lang('greenhouse_scan_or_choose')) ?></p>
    <ul class="tap-list" style="margin-top:.75rem">
      <?php foreach ($greenhouses as $gh): ?>
        <li>
          <a href="<?= e(app_url($gh['id'] . '/')) ?>"><?= e($gh['name']) ?></a>
        </li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>
</section>
<?php require APP_ROOT . '/views/layout/footer.php'; ?>
