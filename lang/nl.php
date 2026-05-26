<?php
/**
 * Dutch UI strings — canonical source for all user-facing text (TDS-UI-110).
 * Keys stay in English for code readability; values are Dutch.
 * Placeholders use :name syntax; replace with str_replace() or equivalent.
 */
return [

    // ── Handle / registration ────────────────────────────────────────
    'handle_required'               => 'Vul een naam in.',
    'handle_too_long'               => 'Naam mag maximaal 40 tekens zijn.',
    'handle_taken'                  => 'Deze naam is al in gebruik. Kies een andere naam.',
    'handle_changed'                => 'Naam gewijzigd.',

    // ── Admin login / session ────────────────────────────────────────
    'admin_invalid_credentials'     => 'Ongeldige inloggegevens.',
    'admin_rate_limited'            => 'Te veel mislukte pogingen. Probeer opnieuw over :seconds seconden.',
    'admin_session_expired'         => 'Sessie verlopen. Log opnieuw in.',

    // ── Admin setup wizard ───────────────────────────────────────────
    'setup_password_required'       => 'Vul een wachtwoord in.',
    'setup_password_mismatch'       => 'Wachtwoorden komen niet overeen.',
    'setup_complete'                => 'Beheerderswachtwoord ingesteld. U kunt nu inloggen.',

    // ── Configuration errors (TDS-CFG-080) ──────────────────────────
    'config_missing'                => 'Configuratiebestand (config.php) ontbreekt. Maak een kopie van template_config.php en vul de waarden in.',
    'config_admin_name_empty'       => 'config: admin_name mag niet leeg zijn.',
    'config_session_timeout'        => 'config: admin_session_timeout moet een geheel getal ≥ 60 zijn.',
    'config_db_path_invalid'        => 'config: db_path is niet beschrijfbaar of bevindt zich in de webroot.',
    'config_photo_root_invalid'     => 'config: photo_root is niet beschrijfbaar of bevindt zich in de webroot.',
    'config_admin_url_path_invalid' => 'config: admin_url_path mag alleen letters, cijfers, koppeltekens en underscores bevatten.',
    'config_edit_window_invalid'    => 'config: edit_window_hours moet een geheel getal ≥ 1 zijn.',
    'config_timezone_invalid'       => 'config: timezone is geen geldige IANA-tijdzonenaam (bijv. Europe/Amsterdam).',
    'config_retention_invalid'      => 'config: retention_days moet een geheel getal ≥ 0 zijn.',
    'config_unsafe_storage'         => 'Onveilige opslaglocatie: het opslagpad bevindt zich in de webroot. Pas db_path en photo_root in config.php aan.',

    // ── Greenhouse management ────────────────────────────────────────
    'greenhouse_id_format'          => 'Greenhouse-ID moet uit precies 4 hexadecimale tekens (hoofdletters) bestaan, bijv. 5E3F. Dit zijn doorgaans de laatste 2 bytes van het MAC-adres van de regelaar.',
    'greenhouse_name_required'      => 'Vul een naam in voor de kas.',
    'greenhouse_delete_has_obs'     => 'Deze kas kan niet worden verwijderd: er zijn :count waarneming(en) aan gekoppeld. Verwijder of verplaats de waarnemingen eerst.',
    'greenhouse_not_found'          => 'Kas niet gevonden.',
    'greenhouse_scan_qr'            => 'Scan de QR-code in de kas om de app te koppelen.',
    'greenhouse_scan_or_choose'     => 'Scan een QR-code of kies hieronder een kas:',
    'greenhouse_none_configured'    => 'Er is nog geen kas geconfigureerd. Neem contact op met de beheerder.',

    // ── Recording flow ───────────────────────────────────────────────
    'observation_saved'             => '✓ Opgeslagen',
    'observation_not_found'         => 'Waarneming niet gevonden.',
    'observation_not_owner'         => 'U hebt geen toegang tot deze waarneming.',
    'observation_read_only'         => 'Alleen-lezen na :hours uur.',
    'observation_edit_window_left'  => 'Nog :time bewerkbaar.',
    'observation_deleted'           => 'Waarneming verwijderd.',
    'category_required'             => 'Kies een categorie.',
    'tag_required'                  => 'Kies een tag.',
    'severity_invalid'              => 'Ernst moet een waarde tussen 1 en 5 zijn.',

    // ── Recent observations (FR-REV-010) ────────────────────────────
    'recent_empty'                  => 'Nog geen waarnemingen vandaag — tik op + Snelle waarneming om te beginnen.',
    'recent_heading'                => 'Recent (laatste 24 uur)',

    // ── Taxonomy management ──────────────────────────────────────────
    'tax_key_required'              => 'Vul een interne sleutel in.',
    'tax_display_name_required'     => 'Vul een weergavenaam in.',
    'tax_key_taken'                 => 'Deze interne sleutel bestaat al binnen deze categorie.',
    'tax_delete_has_obs'            => 'Waarschuwing: :count waarneming(en) verwijzen naar dit item. Na verwijdering wordt de interne sleutel als terugvallabel weergegeven.',
    'tax_archived'                  => 'Item gearchiveerd.',
    'tax_restored'                  => 'Item hersteld.',

    // ── User management ──────────────────────────────────────────────
    'user_forgotten'                => 'Cookie van gebruiker ":handle" ongeldig gemaakt.',
    'user_not_found'                => 'Gebruiker niet gevonden.',

    // ── Photo upload (Step 2) ────────────────────────────────────────
    'photo_too_large'               => 'Foto is te groot (maximum 8 MB). Deel een foto met een lagere resolutie.',
    'photo_invalid_type'            => 'Bestandstype wordt niet ondersteund. Gebruik JPEG, PNG of WebP.',
    'photo_heic_unsupported'        => 'HEIC-bestanden worden niet ondersteund op deze server. Deel de foto als JPEG.',
    'photo_dimensions_too_large'    => 'Fotodimensies zijn te groot (maximum 8192 × 8192 pixels).',

    // ── Security ─────────────────────────────────────────────────────
    'csrf_invalid'                  => 'Ongeldige formuliertoken. Laad de pagina opnieuw en probeer het nogmaals.',
    'forget_me_done'                => 'Uw cookie is verwijderd. U bent niet meer herkend op dit apparaat.',

    // ── Privacy notice headings (TDS-UI-080) ────────────────────────
    'privacy_heading_data'          => 'Welke gegevens verzamelen wij',
    'privacy_heading_basis'         => 'Grondslag',
    'privacy_heading_retention'     => 'Bewaartermijn',
    'privacy_heading_rights'        => 'Uw rechten',
    'privacy_heading_contact'       => 'Contact',

    // ── Error pages (TDS-UI-090) ─────────────────────────────────────
    'error_404_title'               => 'Pagina niet gevonden',
    'error_404_body'                => 'De gevraagde pagina bestaat niet.',
    'error_403_title'               => 'Geen toegang',
    'error_403_body'                => 'U hebt geen toegang tot deze pagina.',
    'error_413_title'               => 'Bestand te groot',
    'error_413_body'                => 'Het geüploade bestand is te groot. De maximale bestandsgrootte is 8 MB.',
    'error_429_title'               => 'Te veel pogingen',
    'error_429_body'                => 'Te veel mislukte inlogpogingen. Probeer opnieuw over :seconds seconden.',
    'error_500_title'               => 'Serverfout',
    'error_500_body'                => 'Er is een onverwachte fout opgetreden. Probeer het later opnieuw.',

    // ── Common UI labels ─────────────────────────────────────────────
    'back'                          => '← Terug',
    'save'                          => 'Opslaan',
    'delete'                        => 'Verwijderen',
    'cancel'                        => 'Annuleren',
    'confirm'                       => 'Bevestigen',
    'logout'                        => 'Uitloggen',
    'settings'                      => 'Instellingen',
    'see_all'                       => 'Alles bekijken',
    'quick_observation'             => '+ Snelle waarneming',
    'forget_me'                     => 'Vergeet mij',
    'change_name'                   => 'Naam wijzigen',
    'add_note'                      => '+ Opmerking toevoegen',
    'add_photo'                     => 'Foto toevoegen',
    'done'                          => 'Klaar',
    'edit'                          => 'Bewerken',
    'privacy'                       => 'Privacy',
    'download_my_data'              => 'Download mijn gegevens',
    'what_kind'                     => 'Wat voor waarneming?',
    'who_is_recording'              => 'Wie neemt waar?',
    'set_once'                      => 'Eenmalig instellen. Wijzigen via Instellingen.',

];
