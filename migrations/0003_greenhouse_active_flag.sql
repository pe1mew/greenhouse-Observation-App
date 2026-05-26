-- Migration 0003: add active_flag to greenhouse
-- Allows greenhouses to be archived (hidden from operators) without
-- losing historical observations. All existing rows get active_flag = 1.

ALTER TABLE greenhouse ADD COLUMN active_flag INTEGER NOT NULL DEFAULT 1;

INSERT OR REPLACE INTO schema_meta (id, version) VALUES (1, 3);
