<?php
declare(strict_types=1);
namespace GreenhouseObs;

class Database
{
    /** Open (or create) the SQLite database with WAL mode and FK enforcement. */
    public static function connect(string $dbPath): \PDO
    {
        $dir = dirname($dbPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $pdo = new \PDO('sqlite:' . $dbPath);
        $pdo->setAttribute(\PDO::ATTR_ERRMODE,            \PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);

        // Applied on every connection (SQLite PRAGMAs are per-connection)
        $pdo->exec('PRAGMA journal_mode = WAL');
        $pdo->exec('PRAGMA busy_timeout = 5000');
        $pdo->exec('PRAGMA foreign_keys = ON');

        return $pdo;
    }

    /**
     * Run any pending migration files from $migrationsDir (TDS-STO-080).
     * Files must be named NNNN_<slug>.sql where NNNN is a zero-padded version.
     */
    public static function migrate(\PDO $db, string $migrationsDir): void
    {
        // schema_meta may not exist yet on very first boot
        $db->exec('CREATE TABLE IF NOT EXISTS schema_meta (
            id      INTEGER PRIMARY KEY CHECK(id = 1),
            version INTEGER NOT NULL
        )');

        $row     = $db->query('SELECT version FROM schema_meta WHERE id = 1')->fetch();
        $current = $row ? (int)$row['version'] : 0;

        $files = glob($migrationsDir . '/[0-9][0-9][0-9][0-9]_*.sql') ?: [];
        sort($files);

        foreach ($files as $file) {
            $version = (int)basename($file);
            if ($version <= $current) {
                continue;
            }
            $sql = file_get_contents($file);
            $db->exec($sql);
            $current = $version;
        }
    }

    /**
     * Seed the launch taxonomy if the category table is empty (TDS-STO-130).
     * Internal keys stay English; display names are Dutch (FR-UI-070).
     */
    public static function seedTaxonomy(\PDO $db): void
    {
        $count = (int)$db->query('SELECT COUNT(*) FROM category')->fetchColumn();
        if ($count > 0) {
            return;
        }

        $taxonomy = [
            ['wellbeing',      'Welzijnscheck', [
                ['all_good', 'Alles goed'],
                ['concern',  'Punt van zorg'],
            ]],
            ['environment',    'Omgeving', [
                ['weather_storm',    'Storm'],
                ['weather_overcast', 'Bewolkt'],
                ['obstacle_seen',    'Obstakel gezien'],
                ['external_noise',   'Extern geluid'],
            ]],
            ['crop',           'Gewas', [
                ['crop_stage_change', 'Groeifase gewijzigd'],
                ['crop_pest',         'Plaag'],
                ['crop_disease',      'Ziekte'],
                ['crop_other',        'Overig gewas'],
            ]],
            ['sensor_control', 'Sensor/regeling', [
                ['sensor_drift_suspect', 'Sensorafwijking vermoed'],
                ['control_too_open',     'Raam te ver open'],
                ['control_too_closed',   'Raam te ver gesloten'],
                ['oscillation_noticed',  'Oscillatie opgemerkt'],
                ['manual_override',      'Handmatige ingreep'],
            ]],
            ['maintenance',    'Onderhoud', [
                ['maint_clean_sensors', 'Sensoren schoongemaakt'],
                ['maint_window_check',  'Raaminspectie'],
                ['maint_other',         'Overig onderhoud'],
            ]],
            ['temperature',   'Temperatuur', [
                ['heat_stress',    'Hittestress'],
                ['cold_damage',    'Koudeschade'],
                ['frost_damage',   'Vorstschade'],
                ['day_night_diff', 'Groot dag-nachtverschil'],
            ]],
            ['humidity_high',  'Luchtvochtigheid — te hoog', [
                ['condensation', 'Condensatie op gewas'],
                ['mold',         'Schimmelvorming'],
                ['wet_rot',      'Natrot / stengelrot'],
                ['fruit_spot',   'Smet op vruchten'],
            ]],
            ['humidity_low',   'Luchtvochtigheid — te laag', [
                ['wilting',            'Verwelking'],
                ['leaf_curl_down',     'Bladkrul omlaag'],
                ['flower_fruit_dry',   'Verdroging bloem/vrucht'],
                ['dry_leaf_edges',     'Droge bladranden'],
            ]],
        ];

        $stmtCat = $db->prepare(
            'INSERT INTO category (internal_key, display_name, active_flag, sort_order)
             VALUES (:key, :name, 1, :ord)'
        );
        $stmtTag = $db->prepare(
            'INSERT INTO tag (category_id, internal_key, display_name, active_flag, sort_order)
             VALUES (:cat_id, :key, :name, 1, :ord)'
        );

        $catOrd = 0;
        foreach ($taxonomy as [$catKey, $catName, $tags]) {
            $stmtCat->execute([':key' => $catKey, ':name' => $catName, ':ord' => $catOrd++]);
            $catId  = (int)$db->lastInsertId();
            $tagOrd = 0;
            foreach ($tags as [$tagKey, $tagName]) {
                $stmtTag->execute([
                    ':cat_id' => $catId,
                    ':key'    => $tagKey,
                    ':name'   => $tagName,
                    ':ord'    => $tagOrd++,
                ]);
            }
        }
    }

    /** Used by the /health endpoint (FR-INST-060). */
    public static function isReachable(\PDO $db): bool
    {
        try {
            $db->query('SELECT 1');
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /** Create the photo root directory if it does not yet exist. */
    public static function ensurePhotoRoot(string $photoRoot): void
    {
        if (!is_dir($photoRoot)) {
            mkdir($photoRoot, 0755, true);
        }
    }
}
