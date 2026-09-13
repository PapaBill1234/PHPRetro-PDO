# Phase 4 batch 2 — Polaris schema rework

## Summary

- forgot.php now uses prepared PDO queries against Polaris users. The actionList email input is bound.
- groups.php now uses Polaris guilds, guilds_members, and users.
- home.php now uses Polaris users.
- email.php, help.php, history.php, and the index tag cloud remove unsupported legacy SQL.
- error.php and intermediate.php were reviewed and contain no SQL, so remain unchanged.

## Files changed

- email.php
- forgot.php
- groups.php
- help.php
- history.php
- home.php
- index.php
- docs/phase-reports/phase4-batch2.md

## Schema verification

The full authoritative references/schema/CleanDB.sql blob f68768b909ebbb9afda21459d2e263254d2d615c was read directly from GitHub (5,563,655 bytes; 55,909 lines; 178 tables). No local checkout was created.

Reproducible lookup commands and literal results:

~~~bash
grep -n -A 14 -m 1 'CREATE TABLE IF NOT EXISTS `users`' references/schema/CleanDB.sql
~~~

~~~text
55189:CREATE TABLE IF NOT EXISTS `users` (
55190-  `id` int(11) NOT NULL AUTO_INCREMENT,
55191-  `username` varchar(25) NOT NULL,
55193-  `password` varchar(64) NOT NULL,
55194-  `mail` varchar(500) DEFAULT NULL,
55195-  `mail_verified` enum('0','1') NOT NULL DEFAULT '0',
55199-  `last_online` int(11) NOT NULL DEFAULT 0,
55200-  `motto` varchar(127) NOT NULL DEFAULT '',
55201-  `look` varchar(256) NOT NULL DEFAULT ...,
55203-  `rank` int(11) NOT NULL DEFAULT 1,
~~~

~~~bash
grep -n -A 22 -m 1 'CREATE TABLE IF NOT EXISTS `guilds`' references/schema/CleanDB.sql
grep -n -A 13 -m 1 'CREATE TABLE IF NOT EXISTS `guilds_members`' references/schema/CleanDB.sql
~~~

~~~text
40776:CREATE TABLE IF NOT EXISTS `guilds` (
40777-  `id`, `user_id`, `name`, `description`, `room_id`, `state`,
40783-  `rights`, ... `badge`, `date_created`, ...
41336:CREATE TABLE IF NOT EXISTS `guilds_members` (
41337-  `id`, `guild_id`, `user_id`, `level_id`, `member_since`
~~~

~~~bash
grep -nEi 'CREATE TABLE IF NOT EXISTS `[^`]*(verify|transaction|homes|faq|tags)[^`]*`' references/schema/CleanDB.sql
~~~

~~~text
(no matches)
~~~

## Tables and columns used

| File | Table | Verified columns |
| --- | --- | --- |
| forgot.php | users | id, username, mail, mail_verified, password |
| groups.php | guilds | id, user_id, name, description, room_id, state, rights, badge, date_created |
| groups.php | users | id, username |
| groups.php | guilds_members | guild_id, user_id, level_id |
| home.php | users | id, username, motto, look, rank, last_online |

## Flagged / unresolved

- Polaris has mail_verified but no verified token-table equivalent for legacy verify. The email verification link flow is disabled; users.secret_key was not reused without a documented contract.
- No Polaris equivalent exists for the legacy transactions ledger, faq, standalone tags, homes, homes_edit, or homes_catalogue tables.
- rooms.tags is denormalized and not a drop-in tag-cloud aggregate.
- Polaris guilds do not expose the legacy SEO alias column, so groups.php accepts numeric IDs only.
- The legacy MyHabbo layout editor is removed from group and profile pages because its backing tables do not exist in Polaris.

## Assumptions

- Password reset remains restricted to mail_verified = '1'.
- PHP 8.3 is the target: reset passwords use bin2hex(random_bytes(8)) and password_hash(..., PASSWORD_DEFAULT).

## PHP lint

Not run: this GitHub-only environment has no php executable. The attempted php -l - command returned:

~~~text
The term 'php' is not recognized as a name of a cmdlet, function, script file, or executable program.
~~~

Run before merge:

~~~bash
php -l email.php
php -l error.php
php -l forgot.php
php -l groups.php
php -l help.php
php -l history.php
php -l home.php
php -l index.php
php -l intermediate.php
~~~