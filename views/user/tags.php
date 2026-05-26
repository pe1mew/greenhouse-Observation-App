<?php
$pageTitle   = $cat['display_name'];
$showPrivacy = false;
require APP_ROOT . '/views/layout/header.php';
?>
<section>
  <h2><?= e($cat['display_name']) ?></h2>
  <ul class="tap-list">
    <?php foreach ($tags as $tag): ?>
      <li>
        <form method="post"
              action="<?= e(app_url($ghId . '/observe/' . $cat['id'] . '/')) ?>"
              style="margin:0">
          <input type="hidden" name="_csrf" value="<?= e($user['csrf_token']) ?>">
          <input type="hidden" name="tag_id" value="<?= (int)$tag['id'] ?>">
          <button type="submit"><?= e($tag['display_name']) ?></button>
        </form>
      </li>
    <?php endforeach; ?>
  </ul>
</section>
<p><a href="<?= e(app_url($ghId . '/observe/')) ?>" class="btn btn-sm"><?= e(lang('back')) ?></a></p>
<?php require APP_ROOT . '/views/layout/footer.php'; ?>
