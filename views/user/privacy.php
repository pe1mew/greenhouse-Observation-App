<?php
$pageTitle  = lang('privacy');
$showPrivacy = false;
require APP_ROOT . '/views/layout/header.php';
?>
<section>
  <h2><?= e(lang('privacy_heading_data')) ?></h2>
  <p>Wij slaan per waarneming de volgende gegevens op: tijdstip, een zelf gekozen bijnaam (geen echte naam vereist), de gekozen categorie en tag, en optioneel een korte notitie of foto. Er worden geen accountgegevens, wachtwoorden of persoonlijke identificatienummers gevraagd of opgeslagen.</p>
  <p style="margin-top:.5rem">Uw apparaat wordt herkend via een anonieme cookie die op uw apparaat wordt bewaard. De cookie bevat geen persoonsgegevens.</p>
</section>

<section>
  <h2><?= e(lang('privacy_heading_basis')) ?></h2>
  <p>De grondslag voor de verwerking is het gerechtvaardigde belang van de kasbeheerder om het gedrag van de klimaatregelaar te monitoren en te verbeteren. Deelname is vrijwillig.</p>
</section>

<section>
  <h2><?= e(lang('privacy_heading_retention')) ?></h2>
  <?php if ($retention > 0): ?>
    <p>Waarnemingen worden automatisch verwijderd na <strong><?= (int)$retention ?> dagen</strong>. Uw gegevens worden niet langer bewaard dan noodzakelijk.</p>
  <?php else: ?>
    <p>Er is geen automatische verwijdering ingesteld. Neem contact op met de beheerder voor informatie over de bewaartermijn.</p>
  <?php endif; ?>
</section>

<section>
  <h2><?= e(lang('privacy_heading_rights')) ?></h2>
  <p>U kunt uw gegevens op elk moment verwijderen door op <strong><?= e(lang('forget_me')) ?></strong> te tikken in de Instellingen. Daarna wordt uw cookie ongeldig gemaakt en bent u niet meer herkenbaar in de app. Uw eerder opgeslagen waarnemingen blijven bewaard ten behoeve van het onderzoek, maar zijn niet meer aan u te herleiden.</p>
</section>

<section>
  <h2><?= e(lang('privacy_heading_contact')) ?></h2>
  <?php if (!empty($adminContact)): ?>
    <p>Vragen over privacy? Neem contact op via: <strong><?= e($adminContact) ?></strong></p>
  <?php else: ?>
    <p>Neem contact op met de beheerder van deze installatie.</p>
  <?php endif; ?>
</section>

<p><a href="javascript:history.back()" class="btn btn-sm"><?= e(lang('back')) ?></a></p>
<?php require APP_ROOT . '/views/layout/footer.php'; ?>
