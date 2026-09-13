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
