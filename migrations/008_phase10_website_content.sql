-- Website-owned housekeeping content used by banners/campaigns/recommended/catalogue.
-- These tables are not in PolarIS CleanDB.sql.

CREATE TABLE IF NOT EXISTS `phpretro_banners` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `text` VARCHAR(255) NOT NULL DEFAULT '',
  `banner` VARCHAR(255) NOT NULL DEFAULT '',
  `url` VARCHAR(255) NOT NULL DEFAULT '',
  `status` CHAR(1) NOT NULL DEFAULT '1',
  `advanced` CHAR(1) NOT NULL DEFAULT '0',
  `html` TEXT NOT NULL,
  `sort_order` INT NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  INDEX `idx_status_order` (`status`, `sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `phpretro_campaigns` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL DEFAULT '',
  `desc` VARCHAR(255) NOT NULL DEFAULT '',
  `image` VARCHAR(255) NOT NULL DEFAULT '',
  `url` VARCHAR(255) NOT NULL DEFAULT '',
  `visible` CHAR(1) NOT NULL DEFAULT '1',
  `sort_order` INT NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  INDEX `idx_visible_order` (`visible`, `sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `phpretro_recommended` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `rec_id` INT NOT NULL,
  `type` VARCHAR(16) NOT NULL DEFAULT 'group',
  `sponsered` CHAR(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  INDEX `idx_type_sponsored` (`type`, `sponsered`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `phpretro_homes_catalogue` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL DEFAULT '',
  `desc` VARCHAR(255) NOT NULL DEFAULT '',
  `type` VARCHAR(32) NOT NULL DEFAULT '1',
  `data` VARCHAR(255) NOT NULL DEFAULT '',
  `price` INT NOT NULL DEFAULT 0,
  `amount` INT NOT NULL DEFAULT 1,
  `category` VARCHAR(100) NOT NULL DEFAULT '',
  `minrank` INT NOT NULL DEFAULT 1,
  `where` VARCHAR(8) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
