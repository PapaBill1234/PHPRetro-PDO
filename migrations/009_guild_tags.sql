-- Website-owned group tags. PolarIS guilds has no tags column
-- (CleanDB.sql:40776). Do not write users_settings.tags.

CREATE TABLE IF NOT EXISTS `phpretro_guild_tags` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `guild_id` INT NOT NULL,
  `tag` VARCHAR(20) NOT NULL,
  `created_by_user_id` INT NULL,
  `created_at` INT NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `idx_guild_tag` (`guild_id`, `tag`),
  INDEX `idx_tag` (`tag`),
  CONSTRAINT `fk_phpretro_guild_tags_guild` FOREIGN KEY (`guild_id`) REFERENCES `guilds` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_phpretro_guild_tags_user` FOREIGN KEY (`created_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
