-- Live-sync columns for the existing MyHabbo layout/guestbook tables.
-- Tables were created in 001_custom_tables.sql without synced_at.

ALTER TABLE `phpretro_myhabbo_layouts`
  ADD COLUMN `synced_at` TIMESTAMP NULL DEFAULT NULL;

ALTER TABLE `phpretro_myhabbo_guestbook`
  ADD COLUMN `synced_at` TIMESTAMP NULL DEFAULT NULL;
