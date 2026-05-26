<footer>
  <span>Greenhouse Observation App &nbsp;&bull;&nbsp; v<?= e(APP_VERSION) ?></span>
  <?php if (!empty($showPrivacy)): ?>
    <a href="<?= e(app_url('privacy')) ?>"><?= e(lang('privacy')) ?></a>
  <?php endif; ?>
</footer>
</body>
</html>
