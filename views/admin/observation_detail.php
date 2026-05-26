<?php require APP_ROOT . '/views/admin/layout/header.php'; ?>
<section>
  <h2><?= e(lang('obs_detail')) ?> #<?= (int)$obs['id'] ?></h2>
  <table style="width:auto;margin-bottom:1rem">
    <tr><th style="text-align:right;padding-right:1rem;white-space:nowrap">Tijdstip</th>
        <td><?= e(tz_local($obs['ts'], $cfg['timezone'])) ?></td></tr>
    <tr><th style="text-align:right;padding-right:1rem">Kas</th>
        <td><?= e($obs['gh_name']) ?> (<?= e($obs['gh_id']) ?>)</td></tr>
    <tr><th style="text-align:right;padding-right:1rem">Gebruiker</th>
        <td><?= e($obs['handle']) ?> (#<?= (int)$obs['user_id'] ?>)</td></tr>
    <tr><th style="text-align:right;padding-right:1rem">Categorie</th>
        <td><?= e($obs['cat_name']) ?></td></tr>
    <tr><th style="text-align:right;padding-right:1rem">Tag</th>
        <td><?= e($obs['tag_name']) ?></td></tr>
    <tr><th style="text-align:right;padding-right:1rem">Ernst</th>
        <td><?= $obs['severity'] !== null ? (int)$obs['severity'] . '/5' : '—' ?></td></tr>
    <tr><th style="text-align:right;padding-right:1rem;vertical-align:top">Opmerking</th>
        <td style="white-space:pre-wrap"><?= e($obs['note'] ?? '—') ?></td></tr>
    <tr><th style="text-align:right;padding-right:1rem">Aangemaakt</th>
        <td><?= e(tz_local($obs['created_at'], $cfg['timezone'])) ?></td></tr>
    <tr><th style="text-align:right;padding-right:1rem">Bijgewerkt</th>
        <td><?= e(tz_local($obs['updated_at'], $cfg['timezone'])) ?></td></tr>
    <?php if (!empty($obs['photo_path'])): ?>
    <tr><th style="text-align:right;padding-right:1rem">Foto</th>
        <td><?= e(basename($obs['photo_path'])) ?></td></tr>
    <?php endif; ?>
  </table>

  <form method="post"
        action="<?= e($adminBase) ?>/observations/<?= (int)$obs['id'] ?>/delete"
        onsubmit="return confirm('Waarneming #<?= (int)$obs['id'] ?> definitief verwijderen?')">
    <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">
    <button type="submit" class="btn btn-danger btn-sm"><?= e(lang('delete')) ?></button>
  </form>
</section>

<p style="margin-top:1rem">
  <a href="<?= e($adminBase) ?>/observations" class="btn btn-sm"><?= e(lang('back')) ?></a>
</p>
<?php require APP_ROOT . '/views/admin/layout/footer.php'; ?>
