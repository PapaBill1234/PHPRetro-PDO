-- Website-owned MyHabbo store, ratings, guestbook privacy, and group homes.
-- None of these tables or columns exist in CleanDB.sql. PolarIS room_votes is
-- an in-room thumbs-up and is never written here.

ALTER TABLE `phpretro_myhabbo_layouts`
  ADD COLUMN `privacy` ENUM('public','private') NOT NULL DEFAULT 'public',
  ADD COLUMN `guild_id` INT NOT NULL DEFAULT 0,
  DROP INDEX `idx_user_column_position`,
  ADD UNIQUE INDEX `idx_user_guild_column_position` (`user_id`, `guild_id`, `column_number`, `position`),
  ADD INDEX `idx_guild_id` (`guild_id`);

CREATE TABLE IF NOT EXISTS `phpretro_home_ratings` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `profile_user_id` INT NOT NULL,
  `rater_id` INT NOT NULL,
  `rating` TINYINT NOT NULL,
  `created_at` INT NOT NULL,
  `synced_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `idx_profile_rater` (`profile_user_id`, `rater_id`),
  INDEX `idx_profile` (`profile_user_id`),
  CONSTRAINT `fk_phpretro_home_ratings_profile` FOREIGN KEY (`profile_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_phpretro_home_ratings_rater` FOREIGN KEY (`rater_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `phpretro_homes_catalogue` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  `description` VARCHAR(255) NOT NULL DEFAULT '',
  `type` ENUM('sticker','widget','note','background') NOT NULL,
  `data` VARCHAR(255) NOT NULL,
  `price` INT NOT NULL DEFAULT 0,
  `amount` INT NOT NULL DEFAULT 1,
  `category` VARCHAR(255) NOT NULL DEFAULT 'Default',
  `category_id` INT NOT NULL DEFAULT 0,
  `min_rank` INT NOT NULL DEFAULT 1,
  `placement` ENUM('homes','groups','anywhere') NOT NULL DEFAULT 'anywhere',
  PRIMARY KEY (`id`),
  INDEX `idx_type_category` (`type`, `category_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `phpretro_homes_items` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `user_id` INT NOT NULL,
  `guild_id` INT NOT NULL DEFAULT 0,
  `catalogue_id` INT NOT NULL,
  `item_type` ENUM('sticker','stickie','background') NOT NULL,
  `skin` VARCHAR(64) NOT NULL DEFAULT '',
  `data` TEXT NOT NULL,
  `x` INT NOT NULL DEFAULT 0,
  `y` INT NOT NULL DEFAULT 0,
  `z` INT NOT NULL DEFAULT 0,
  `placed` TINYINT(1) NOT NULL DEFAULT 0,
  `synced_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  INDEX `idx_user_placed_type` (`user_id`, `placed`, `item_type`),
  INDEX `idx_guild_placed` (`guild_id`, `placed`),
  CONSTRAINT `fk_phpretro_homes_items_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_phpretro_homes_items_catalogue` FOREIGN KEY (`catalogue_id`) REFERENCES `phpretro_homes_catalogue` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `phpretro_group_guestbook` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `guild_id` INT NOT NULL,
  `author_user_id` INT NOT NULL,
  `message` TEXT NOT NULL,
  `created_at` INT NOT NULL,
  `synced_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  INDEX `idx_guild_created` (`guild_id`, `created_at`),
  CONSTRAINT `fk_phpretro_group_guestbook_guild` FOREIGN KEY (`guild_id`) REFERENCES `guilds` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_phpretro_group_guestbook_author` FOREIGN KEY (`author_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `phpretro_homes_catalogue` (`id`, `name`, `description`, `type`, `data`, `price`, `amount`, `category`, `category_id`, `min_rank`, `placement`) VALUES
(101, 'Profile Widget', 'Your profile.', 'widget', 'profilewidget', 0, 1, 'Widgets', 100, 1, 'homes'),
(102, 'Guestbook Widget', 'Comments on your page.', 'widget', 'guestbookwidget', 0, 1, 'Widgets', 100, 1, 'homes'),
(103, 'High Scores Widget', 'High scores.', 'widget', 'highscoreswidget', 0, 1, 'Widgets', 100, 1, 'homes'),
(104, 'Badges Widget', 'Your badges.', 'widget', 'badgeswidget', 0, 1, 'Widgets', 100, 1, 'homes'),
(105, 'Friends Widget', 'Your friends.', 'widget', 'friendswidget', 0, 1, 'Widgets', 100, 1, 'homes'),
(106, 'Groups Widget', 'Your groups.', 'widget', 'groupswidget', 0, 1, 'Widgets', 100, 1, 'homes'),
(107, 'Rooms Widget', 'Your rooms.', 'widget', 'roomswidget', 0, 1, 'Widgets', 100, 1, 'homes'),
(109, 'Rating Widget', 'Allows others to vote on your page. You cannot vote for yourself.', 'widget', 'ratingwidget', 0, 1, 'Widgets', 100, 1, 'homes'),
(110, 'Group Info Widget', 'Group information.', 'widget', 'groupinfowidget', 0, 1, 'Widgets', 100, 1, 'groups'),
(111, 'Group Guestbook', 'Comments on the group page.', 'widget', 'guestbookwidget', 0, 1, 'Widgets', 100, 1, 'groups'),
(112, 'Members Widget', 'Members of this group.', 'widget', 'memberwidget', 0, 1, 'Widgets', 100, 1, 'groups'),
(114, 'Notes', '', 'note', 'stickienote', 2, 5, 'Notes', 101, 1, 'anywhere'),
(116, 'Trax Sfx', '', 'sticker', 'trax_sfx', 1, 1, 'Trax', 102, 1, 'anywhere'),
(117, 'Trax Rock', '', 'sticker', 'trax_rock', 1, 2, 'Trax 2', 103, 1, 'anywhere'),
(118, 'Wood Background', '', 'background', 'bg_wood', 3, 1, 'Backgrounds', 104, 1, 'anywhere');
