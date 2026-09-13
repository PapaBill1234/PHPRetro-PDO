-- PHPRetro project-owned tables. These do not belong to Polaris migrations.
-- Run this once alongside Polaris's own schema setup.

CREATE TABLE IF NOT EXISTS `phpretro_news` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(255) NOT NULL,
  `summary` TEXT NOT NULL,
  `story` TEXT NOT NULL,
  `author` VARCHAR(100) NOT NULL,
  `categories` VARCHAR(255) NOT NULL DEFAULT '',
  `images` TEXT NOT NULL DEFAULT '',
  `time` INT NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `idx_time` (`time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `phpretro_collectibles` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  `description` TEXT NOT NULL,
  `image` VARCHAR(255) NOT NULL,
  `time` INT NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `idx_time` (`time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `phpretro_client_errors` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `user_id` INT NULL,
  `ip` VARCHAR(45) NOT NULL,
  `error_type` VARCHAR(50) NOT NULL,
  `message` TEXT NOT NULL,
  `stack_trace` TEXT NULL,
  `user_agent` VARCHAR(255) NULL,
  `url` VARCHAR(255) NULL,
  `client_version` VARCHAR(50) NULL,
  `created_at` INT NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `phpretro_email_verification_tokens` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `user_id` INT NOT NULL,
  `token_hash` CHAR(64) NOT NULL,
  `created_at` INT NOT NULL,
  `expires_at` INT NOT NULL,
  `used_at` INT NULL,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `idx_token_hash` (`token_hash`),
  INDEX `idx_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
 

CREATE TABLE IF NOT EXISTS `phpretro_transactions` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `user_id` INT NOT NULL,
  `type` VARCHAR(50) NOT NULL,
  `amount` INT NOT NULL,
  `balance_after` INT NOT NULL,
  `description` VARCHAR(255) NOT NULL DEFAULT '',
  `reference_id` VARCHAR(100) NULL,
  `created_at` INT NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `idx_user_id_created` (`user_id`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `phpretro_faq` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `category` VARCHAR(100) NOT NULL DEFAULT 'general',
  `question` VARCHAR(255) NOT NULL,
  `answer` TEXT NOT NULL,
  `sort_order` INT NOT NULL DEFAULT 0,
  `active` TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  INDEX `idx_category_sort` (`category`, `sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `phpretro_myhabbo_layouts` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `user_id` INT NOT NULL,
  `column_number` TINYINT NOT NULL,
  `widget_key` VARCHAR(50) NOT NULL,
  `position` INT NOT NULL DEFAULT 0,
  `visible` TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `idx_user_column_position` (`user_id`, `column_number`, `position`),
  INDEX `idx_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `phpretro_myhabbo_guestbook` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `profile_user_id` INT NOT NULL,
  `author_user_id` INT NOT NULL,
  `message` TEXT NOT NULL,
  `created_at` INT NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `idx_profile_user_id` (`profile_user_id`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
