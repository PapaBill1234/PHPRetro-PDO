-- Website-owned group URL aliases. PolarIS guilds have no alias column.
-- guild_id references guilds.id, never a legacy groups table.

CREATE TABLE IF NOT EXISTS `phpretro_group_url_aliases` (
  `alias` VARCHAR(64) NOT NULL,
  `guild_id` INT NOT NULL,
  `created_at` INT NOT NULL,
  `synced_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`alias`),
  UNIQUE INDEX `idx_guild_id` (`guild_id`),
  CONSTRAINT `fk_phpretro_group_url_guild` FOREIGN KEY (`guild_id`) REFERENCES `guilds` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
