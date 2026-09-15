-- Website-owned minimail and the shared PolarIS outbox.
-- These tables are not PolarIS schema. A later Java worker may poll the outbox.

CREATE TABLE IF NOT EXISTS `phpretro_emulator_outbox` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `event_type` VARCHAR(64) NOT NULL,
  `payload_json` JSON NOT NULL,
  `status` ENUM('pending','processing','done','failed') NOT NULL DEFAULT 'pending',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `processed_at` DATETIME NULL,
  PRIMARY KEY (`id`),
  INDEX `idx_status_created` (`status`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- One row per recipient, matching original PHPRetro minimail plus synced_at.
CREATE TABLE IF NOT EXISTS `phpretro_minimail` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `sender_id` INT NOT NULL,
  `recipient_id` INT NOT NULL,
  `subject` VARCHAR(100) NOT NULL,
  `body` TEXT NOT NULL,
  `conversation_id` INT NOT NULL DEFAULT 0,
  `sent_at` INT NOT NULL,
  `read_at` INT NULL,
  `deleted` TINYINT(1) NOT NULL DEFAULT 0,
  `deleted_at` INT NULL,
  `synced_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  INDEX `idx_inbox` (`recipient_id`, `deleted`, `id`),
  INDEX `idx_sent` (`sender_id`, `id`),
  INDEX `idx_conversation` (`conversation_id`),
  CONSTRAINT `fk_phpretro_minimail_sender` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_phpretro_minimail_recipient` FOREIGN KEY (`recipient_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
