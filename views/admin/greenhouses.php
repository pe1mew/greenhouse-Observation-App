<?php require APP_ROOT . '/views/admin/layout/header.php'; ?>
<section>
  <h2>Kassen</h2>
  <?php if (empty($greenhouses)): ?>
    <p class="hint">Nog geen kassen. Voeg een kas toe om te beginnen.</p>
  <?php else: ?>
    <table class="data-table">
      <thead>
        <tr><th>ID</th><th>Naam</th><th>Locatie</th><th>Waarnemingen</th><th>Status</th><th></th></tr>
      </thead>
      <tbody>
        <?php foreach ($greenhouses as $gh): ?>
          <tr>
            <td><code><?= e($gh['id']) ?></code></td>
            <td><?= e($gh['name']) ?></td>
            <td><?= e($gh['location'] ?? '—') ?></td>
            <td><?= (int)$gh['obs_count'] ?></td>
            <td><?= $gh['active_flag'] ? '<span class="badge online">Actief</span>' : '<span class="badge offline">Gearchiveerd</span>' ?></td>
            <td style="white-space:nowrap;display:flex;gap:.25rem">
              <button class="btn btn-sm"
                      data-gh-id="<?= e($gh['id']) ?>"
                      data-gh-name="<?= e($gh['name']) ?>"
                      onclick="openQr(this.dataset.ghId, this.dataset.ghName)">
                QR
              </button>
              <a href="<?= e($adminBase) ?>/greenhouses/<?= e($gh['id']) ?>" class="btn btn-sm">Bewerken</a>
              <form method="post"
                    action="<?= e($adminBase) ?>/greenhouses/<?= e($gh['id']) ?>/archive"
                    style="margin:0">
                <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">
                <button type="submit" class="btn btn-sm">
                  <?= $gh['active_flag'] ? e(lang('archive')) : e(lang('restore')) ?>
                </button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
  <p style="margin-top:.75rem">
    <a href="<?= e($adminBase) ?>/greenhouses/new" class="btn">+ Kas toevoegen</a>
  </p>
</section>

<!-- QR modal -->
<div id="qr-modal" role="dialog" aria-modal="true"
     style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.75);
            z-index:1000;align-items:center;justify-content:center">
  <div style="background:var(--card);border-radius:var(--radius);padding:1.5rem;
              max-width:360px;width:90%;text-align:center">
    <h3 id="qr-title" style="margin-bottom:1rem;font-size:1rem"></h3>
    <img id="qr-img" src="" alt="QR code"
         style="display:block;background:#fff;padding:8px;border-radius:4px;
                margin:0 auto 1rem;max-width:300px;width:100%">
    <div style="display:flex;gap:.5rem;justify-content:center">
      <a id="qr-download" href="" download="" class="btn">Downloaden</a>
      <button onclick="closeQr()" class="btn btn-sm">Sluiten</button>
    </div>
  </div>
</div>

<script>
var adminBase = <?= json_encode($adminBase) ?>;

function openQr(ghId, ghName) {
  var url = adminBase + '/greenhouses/' + ghId + '/qr';
  document.getElementById('qr-title').textContent = ghName + ' (' + ghId + ')';
  document.getElementById('qr-img').src = url;
  document.getElementById('qr-download').href = url;
  document.getElementById('qr-download').download = 'qr-' + ghId + '.png';
  var modal = document.getElementById('qr-modal');
  modal.style.display = 'flex';
  modal.focus();
}

function closeQr() {
  document.getElementById('qr-modal').style.display = 'none';
  document.getElementById('qr-img').src = '';
}

document.getElementById('qr-modal').addEventListener('click', function (e) {
  if (e.target === this) closeQr();
});

document.addEventListener('keydown', function (e) {
  if (e.key === 'Escape') closeQr();
});
</script>
<?php require APP_ROOT . '/views/admin/layout/footer.php'; ?>
