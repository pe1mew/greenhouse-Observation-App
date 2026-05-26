-- Migration 0002: climate-impact taxonomy
-- Adds three categories describing greenhouse climate effects on crops.
-- Uses INSERT OR IGNORE so re-running is safe (idempotent).

INSERT OR IGNORE INTO category (internal_key, display_name, active_flag, sort_order) VALUES
    ('temperature',  'Temperatuur',               1, 10),
    ('humidity_high','Luchtvochtigheid — te hoog', 1, 11),
    ('humidity_low', 'Luchtvochtigheid — te laag', 1, 12);

-- Temperatuur tags
INSERT OR IGNORE INTO tag (category_id, internal_key, display_name, active_flag, sort_order)
SELECT id, 'heat_stress',    'Hittestress',             1, 0 FROM category WHERE internal_key = 'temperature';
INSERT OR IGNORE INTO tag (category_id, internal_key, display_name, active_flag, sort_order)
SELECT id, 'cold_damage',    'Koudeschade',             1, 1 FROM category WHERE internal_key = 'temperature';
INSERT OR IGNORE INTO tag (category_id, internal_key, display_name, active_flag, sort_order)
SELECT id, 'frost_damage',   'Vorstschade',             1, 2 FROM category WHERE internal_key = 'temperature';
INSERT OR IGNORE INTO tag (category_id, internal_key, display_name, active_flag, sort_order)
SELECT id, 'day_night_diff', 'Groot dag-nachtverschil', 1, 3 FROM category WHERE internal_key = 'temperature';

-- Luchtvochtigheid hoog tags
INSERT OR IGNORE INTO tag (category_id, internal_key, display_name, active_flag, sort_order)
SELECT id, 'condensation', 'Condensatie op gewas', 1, 0 FROM category WHERE internal_key = 'humidity_high';
INSERT OR IGNORE INTO tag (category_id, internal_key, display_name, active_flag, sort_order)
SELECT id, 'mold',         'Schimmelvorming',      1, 1 FROM category WHERE internal_key = 'humidity_high';
INSERT OR IGNORE INTO tag (category_id, internal_key, display_name, active_flag, sort_order)
SELECT id, 'wet_rot',      'Natrot / stengelrot',  1, 2 FROM category WHERE internal_key = 'humidity_high';
INSERT OR IGNORE INTO tag (category_id, internal_key, display_name, active_flag, sort_order)
SELECT id, 'fruit_spot',   'Smet op vruchten',     1, 3 FROM category WHERE internal_key = 'humidity_high';

-- Luchtvochtigheid laag tags
INSERT OR IGNORE INTO tag (category_id, internal_key, display_name, active_flag, sort_order)
SELECT id, 'wilting',          'Verwelking',             1, 0 FROM category WHERE internal_key = 'humidity_low';
INSERT OR IGNORE INTO tag (category_id, internal_key, display_name, active_flag, sort_order)
SELECT id, 'leaf_curl_down',   'Bladkrul omlaag',        1, 1 FROM category WHERE internal_key = 'humidity_low';
INSERT OR IGNORE INTO tag (category_id, internal_key, display_name, active_flag, sort_order)
SELECT id, 'flower_fruit_dry', 'Verdroging bloem/vrucht',1, 2 FROM category WHERE internal_key = 'humidity_low';
INSERT OR IGNORE INTO tag (category_id, internal_key, display_name, active_flag, sort_order)
SELECT id, 'dry_leaf_edges',   'Droge bladranden',       1, 3 FROM category WHERE internal_key = 'humidity_low';

INSERT OR REPLACE INTO schema_meta (id, version) VALUES (1, 2);
