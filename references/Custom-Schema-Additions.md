# Custom Schema Additions (Polaris + PHPRetro-specific)

These tables do NOT exist in Polaris's stock schema (`CleanDB.sql`) — they are
custom additions this project owns. Prefixed with `phpretro_` specifically so
they're never confused with a real Polaris table, and so future Polaris updates
never collide with them.

Give this file to Codex alongside `Polaris-Schema-Reference.md` for any task
touching these three features.

---

## 1. `phpretro_news` — restores articles.php's full feature set

```sql
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
```
Matches the original PHPRetro news table's shape (categories as a comma-separated
string, images as a comma-separated string of URLs — same convention the
original `articles.php` already expected, so the PHP-side parsing logic can be
reused largely as-is). `idx_time` supports the "today/yesterday/this week/this
month" and archive-pagination queries efficiently.

## 2. `phpretro_collectibles` — restores collectables.php's feature

```sql
CREATE TABLE IF NOT EXISTS `phpretro_collectibles` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  `description` TEXT NOT NULL,
  `image` VARCHAR(255) NOT NULL,
  `time` INT NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `idx_time` (`time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```
`time` is unique since the original logic assumes one collectible per calendar
month (`mktime(0,0,0,$month,1,$year)`) — this constraint prevents accidentally
creating two "current" collectibles for the same period.

## 3. Client error logging — adapt for Nitro, don't just port the Flash version

**Do not assume a specific Nitro error-reporting convention without checking.**
No standard Habbo-Nitro-client error-reporting format was found via general
research — Codex should check the actual Nitro client source/docs directly
(the modern HTML5 Habbo client, not to be confused with the unrelated
`nitrojs/nitro` Node.js framework or Nitro PDF) for any existing
error-reporting call/endpoint convention it already makes. If one exists,
match it. If not, use this as a sensible new design:

```sql
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
```
This replaces the old Flash-specific fields (`hookerror`, `mus_errorcode`,
`neterr_cast`, etc. — all SWF/Shockwave-runtime concepts with no meaning in a
browser context) with generic, modern client-error fields that make sense for
a JS/TS-based HTML5 client. `ip` widened to VARCHAR(45) to support IPv6.

## Migration approach

Since Polaris auto-creates/migrates its own schema on startup, these custom
tables should be added via **your own separate migration step** — either a
one-time SQL script you run manually alongside Polaris's own schema setup, or
(cleaner) a small PHP-side "ensure table exists" check run once. Do NOT try to
register these as Polaris migrations — that's Polaris's own internal system and
mixing in project-specific tables there risks conflicts with future Polaris
updates.

## 4. phpretro_email_verification_tokens — restores email verification

~~~sql
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
~~~

Store only SHA-256 token hashes. A token is valid only while unused and before expires_at.

## 5. phpretro_transactions — restores transaction/purchase history

~~~sql
CREATE TABLE IF NOT EXISTS `phpretro_transactions` (
  `id` INT NOT NULL AUTO_INCREMENT, `user_id` INT NOT NULL, `type` VARCHAR(50) NOT NULL, `amount` INT NOT NULL, `balance_after` INT NOT NULL, `description` VARCHAR(255) NOT NULL DEFAULT '', `reference_id` VARCHAR(100) NULL, `created_at` INT NOT NULL, PRIMARY KEY (`id`), INDEX `idx_user_id_created` (`user_id`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
~~~

## 6. phpretro_faq — restores FAQ content

~~~sql
CREATE TABLE IF NOT EXISTS `phpretro_faq` (
  `id` INT NOT NULL AUTO_INCREMENT, `category` VARCHAR(100) NOT NULL DEFAULT 'general', `question` VARCHAR(255) NOT NULL, `answer` TEXT NOT NULL, `sort_order` INT NOT NULL DEFAULT 0, `active` TINYINT(1) NOT NULL DEFAULT 1, PRIMARY KEY (`id`), INDEX `idx_category_sort` (`category`, `sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
~~~

## 7. MyHabbo personal layout customization

~~~sql
CREATE TABLE IF NOT EXISTS `phpretro_myhabbo_layouts` (
  `id` INT NOT NULL AUTO_INCREMENT, `user_id` INT NOT NULL, `column_number` TINYINT NOT NULL, `widget_key` VARCHAR(50) NOT NULL, `position` INT NOT NULL DEFAULT 0, `visible` TINYINT(1) NOT NULL DEFAULT 1, PRIMARY KEY (`id`), UNIQUE INDEX `idx_user_column_position` (`user_id`, `column_number`, `position`), INDEX `idx_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `phpretro_myhabbo_guestbook` (
  `id` INT NOT NULL AUTO_INCREMENT, `profile_user_id` INT NOT NULL, `author_user_id` INT NOT NULL, `message` TEXT NOT NULL, `created_at` INT NOT NULL, PRIMARY KEY (`id`), INDEX `idx_profile_user_id` (`profile_user_id`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
~~~

Layouts are a simplified reconstruction; inspect real widgets before implementation.
## 5. `phpretro_transactions` — restores transaction/purchase history

```sql
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
```

`type` examples: `voucher_redeem`, `purchase`, `admin_grant`, `club_subscription`.
NaN is a snapshot for fast display without recomputing from history every time.

## 6. `phpretro_faq` — restores FAQ content

```sql
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
```

Straightforward CMS-style content table. `active` allows hiding an entry without deleting it.

## 7. MyHabbo personal layout customization — two tables

```sql
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
```

`phpretro_myhabbo_layouts` stores which widgets a user has enabled, in which column and order. `phpretro_myhabbo_guestbook` covers the classic "leave a comment on someone's page" widget specifically, since it needs its own table shape (not just a layout position).

**This is a simplified reconstruction, not a guaranteed match to the original feature set.**
