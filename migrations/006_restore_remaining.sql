-- Website-owned restorations. PolarIS support_tickets is the in-game mod tool,
-- not the CMS Help Tool. None of these tables exist in CleanDB.sql.

CREATE TABLE IF NOT EXISTS `phpretro_helpdesk_tickets` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `user_id` INT NULL,
  `username` VARCHAR(25) NOT NULL DEFAULT '',
  `email` VARCHAR(255) NOT NULL DEFAULT '',
  `ip` VARCHAR(45) NOT NULL,
  `subject` VARCHAR(50) NOT NULL,
  `message` TEXT NOT NULL,
  `room_id` INT NOT NULL DEFAULT 0,
  `status` ENUM('open','picked','closed') NOT NULL DEFAULT 'open',
  `picked_by` INT NULL,
  `created_at` INT NOT NULL,
  `synced_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  INDEX `idx_status_created` (`status`, `created_at`),
  CONSTRAINT `fk_phpretro_helpdesk_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_phpretro_helpdesk_picker` FOREIGN KEY (`picked_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `phpretro_collectible_purchases` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `user_id` INT NOT NULL,
  `collectible_id` INT NOT NULL,
  `created_at` INT NOT NULL,
  `synced_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `idx_user_collectible` (`user_id`, `collectible_id`),
  CONSTRAINT `fk_phpretro_collectible_purchase_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_phpretro_collectible_purchase_item` FOREIGN KEY (`collectible_id`) REFERENCES `phpretro_collectibles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `phpretro_club_gifts` (
  `month` TINYINT NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  `image` VARCHAR(255) NOT NULL DEFAULT '',
  `description` TEXT NOT NULL,
  `synced_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`month`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `phpretro_feed_dismissals` (
  `user_id` INT NOT NULL,
  `item_key` VARCHAR(64) NOT NULL,
  `dismissed_at` INT NOT NULL,
  `synced_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`user_id`, `item_key`),
  CONSTRAINT `fk_phpretro_feed_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `phpretro_object_reports` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `reporter_id` INT NOT NULL,
  `object_type` VARCHAR(32) NOT NULL,
  `object_id` INT NOT NULL,
  `reason` VARCHAR(100) NOT NULL DEFAULT '',
  `evidence` TEXT NOT NULL,
  `status` VARCHAR(20) NOT NULL DEFAULT 'open',
  `created_at` INT NOT NULL,
  `synced_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  INDEX `idx_reporter_created` (`reporter_id`, `created_at`),
  INDEX `idx_object` (`object_type`, `object_id`),
  INDEX `idx_status_created` (`status`, `created_at`),
  CONSTRAINT `fk_phpretro_object_reporter` FOREIGN KEY (`reporter_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
