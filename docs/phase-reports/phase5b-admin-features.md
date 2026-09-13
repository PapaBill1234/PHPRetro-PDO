# Phase 5b — admin features

## Status
This branch adds the Phase 5b schema foundation and initial admin UI: audit log, staff sessions, TOTP enrollment and login check, reports queue and submission handler, unified search, maintenance-mode state, and dashboard metrics.

## Files
- migrations/002_admin_features.sql
- includes/AdminAudit.php
- includes/Totp.php
- housekeeping/auditlog.php
- housekeeping/reports.php
- housekeeping/search.php
- housekeeping/staffsessions.php
- housekeeping/twofactor.php
- housekeeping/maintenance.php
- housekeeping/dashboard.php
- housekeeping/index.php
- housekeeping/bans.php
- habblet/report_user.php
- includes/core.php

## Schema
All new storage is project-owned and defined in `migrations/002_admin_features.sql`: `phpretro_admin_action_log`, `phpretro_staff_sessions`, `phpretro_staff_totp`, `phpretro_user_reports`, and `phpretro_site_settings`. Each user reference has a foreign key to verified Polaris `users.id`.

## Verification
```sh
rg -n "CREATE TABLE IF NOT EXISTS \`users\`" references/schema/CleanDB.sql
# verified Polaris users.id, username, mail, ip_current, rank, account_created, last_online
```

## Open review items
PHP lint was not available in this environment. Validate login/TOTP, staff-session invalidation, bulk actions, and audit coverage in a PHP-enabled test environment before merge.
