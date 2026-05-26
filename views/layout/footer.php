<footer>
  <span><?= e(lang('app_name')) ?> &nbsp;&bull;&nbsp; v<?= e(APP_VERSION) ?></span>
  <span>
    <?php if (!empty($showPrivacy)): ?>
      <a href="<?= e(app_url('privacy')) ?>"><?= e(lang('privacy')) ?></a>
      &nbsp;&bull;&nbsp;
    <?php endif; ?>
    <a href="https://github.com/pe1mew/greenhouse-Observation-App" target="_blank" rel="noopener">GitHub</a>
  </span>
</footer>
</body>
</html>
