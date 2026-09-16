# PHPRetro-PDO

PolarIS-backed Habbo CMS. PHP 8.5, PDO, per-session CSRF. This is a modernization of [Quackster/PHPRetro](https://github.com/Quackster/PHPRetro), not a drop-in for the 2009 Holograph schema.

## Requirements

- PHP 8.5 with PDO MySQL (8.4 may boot; 8.5 is the target)
- MariaDB / MySQL with a PolarIS `CleanDB.sql` imported first
- A PolarIS hotel that owns `users`, `rooms`, `guilds`, `bans`, `vouchers`, `chatlogs_room`

## Setup

1. Import PolarIS `CleanDB.sql` into an empty database.
2. Copy [`.env.example`](.env.example) to `.env` and fill in at least `DB_DSN`, `DB_USER`, `DB_PASS`.
3. Open `/install/` in a browser and complete the PolarIS installer. It writes `.env` if you use the Database step, then applies every file in `migrations/` in name order.
4. Sign in with an existing PolarIS staff account (rank ≥ housekeeping threshold).
5. **Delete or lock `install/` after setup.** The installer refuses to run when `includes/config.php` exists (no `?bypass=` override). Removing the folder is still required for production.

Never commit a real `.env`.

## Environment variables

Documented in [`.env.example`](.env.example):

| Variable | Required | Purpose |
| --- | --- | --- |
| `DB_DSN` | yes | PDO DSN (`mysql:host=...;dbname=...;charset=utf8mb4`) |
| `DB_USER` / `DB_PASS` | yes | Database login |
| `MAIL_FROM` / `MAIL_FROM_NAME` / `MAIL_LOG` | no | Override `phpretro_site_settings` mail fields |
| `POLARIS_CMS_URL` / `POLARIS_CMS_KEY` / `POLARIS_CMS_SECRET` | no | PolarIS CMS HTTP |
| `POLARIS_RCON_HOST` / `POLARIS_RCON_PORT` | no | PolarIS RCON |
| `CACHE_DRIVER` | no | `file` (default) or `redis` |
| `CACHE_PATH` / `CACHE_PREFIX` | no | File cache directory and key prefix |
| `REDIS_HOST` / `REDIS_PORT` / `REDIS_PASSWORD` / `REDIS_DATABASE` | no | Used only when `CACHE_DRIVER=redis` |

`includes/config.php` is an `.env` loader. It must not contain credentials.

## Caching

Settings (`HoloSettings::find`) and language bundles go through `includes/Cache.php`. File cache is `./cache/*.cache`. Redis is optional and falls back to files if ext-redis / Predis is missing. After changing site settings in housekeeping, cache is rebuilt automatically.

## CSRF

Every state-changing POST uses a per-session `csrf_token` field in the POST body (not a custom header). HTML forms emit `Csrf::field()`. Prototype `Ajax.Request` is hooked by `Csrf::hookScript()`. Wrong or missing tokens return HTTP 403 `Request could not be completed.`

## What is still 501

Website features that need a PolarIS plugin, a different badge encoding, or a take-credits RCON stay HTTP 501. See [docs/phase-reports/phase10-final-status.md](docs/phase-reports/phase10-final-status.md) for the current list (club subscribe, Trax, room transfer, website voucher redeem, Flash badge editor). Group tags live on website-owned `phpretro_guild_tags` ([docs/phase-reports/guild-tags.md](docs/phase-reports/guild-tags.md)).


## License

GNU GPL 3.0.
