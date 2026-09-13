# Restore remaining Phase 4 features

## Summary

This combined PR follows the revised review scope: transaction history, FAQ, and the bounded MyHabbo layout/guestbook reconstruction. Email verification remains in its separate PR.

## Files changed

- `migrations/001_custom_tables.sql` — adds `phpretro_transactions`, `phpretro_faq`, `phpretro_myhabbo_layouts`, and `phpretro_myhabbo_guestbook`.
- `references/Custom-Schema-Additions.md` — documents those four custom tables.
- `history.php` — restores the signed-in transaction-history display.
- `habblet/ajax_redeemvoucher.php` — records credit and furniture voucher redemption.
- `habblet/ajax_collectiblesPurchase.php` — records collectible purchase.
- `habblet/habboclub_habboclub_subscribe.php` — records club subscription spend.
- `habblet/myhabbo_store_purchase.php` — records MyHabbo store spend.
- `habblet/grouppurchase_purchase_ajax.php` — records the existing group-purchase credit spend.
- `housekeeping/users.php` — records an admin credit adjustment when the saved balance differs.
- `help.php` and `housekeeping/faq.php` — restore public FAQ display and rank-gated FAQ management.
- `habblet/myhabbo_layout_save.php`, `habblet/myhabbo_layouts.php`, and `habblet/myhabbo_guestbook.php` — provide PDO-backed layout and personal-guestbook endpoints.

## Tables and columns used

### Polaris schema

Command run against the checked-in source of truth:

```bash
grep -n "CREATE TABLE IF NOT EXISTS \`users\`" references/schema/CleanDB.sql
```

Result:

```text
55189:CREATE TABLE IF NOT EXISTS `users` (
```

`users.id`, `users.username`, and `users.credits` are used. The verified `users` definition includes all three (and the real Polaris spelling is `username`, not the legacy `name`).

### Custom schema

Commands run:

```bash
grep -n "CREATE TABLE IF NOT EXISTS \`phpretro_transactions\`" references/schema/CleanDB.sql
grep -n "CREATE TABLE IF NOT EXISTS \`phpretro_faq\`" references/schema/CleanDB.sql
grep -n "CREATE TABLE IF NOT EXISTS \`phpretro_myhabbo_layouts\`" references/schema/CleanDB.sql
grep -n "CREATE TABLE IF NOT EXISTS \`phpretro_myhabbo_guestbook\`" references/schema/CleanDB.sql
```

Result: no matches for all four. They are intentionally project-owned tables, defined in this PR's `migrations/001_custom_tables.sql` and documented in `references/Custom-Schema-Additions.md`.

- `phpretro_transactions`: `user_id`, `type`, `amount`, `balance_after`, `description`, `reference_id`, `created_at`.
- `phpretro_faq`: `id`, `category`, `question`, `answer`, `sort_order`, `active`.
- `phpretro_myhabbo_layouts`: `user_id`, `column_number`, `widget_key`, `position`, `visible`.
- `phpretro_myhabbo_guestbook`: `id`, `profile_user_id`, `author_user_id`, `message`, `created_at`.

## Transaction write-point audit

The full repository scan found these actual credit mutations and the implementation records each one:

- Voucher credit redemption — `habblet/ajax_redeemvoucher.php` — `voucher_redeem`.
- Collectible purchase — `habblet/ajax_collectiblesPurchase.php` — `purchase` with `-25`.
- Club subscription — `habblet/habboclub_habboclub_subscribe.php` — `club_subscription`.
- MyHabbo store purchase — `habblet/myhabbo_store_purchase.php` — `purchase`.
- Group purchase — `habblet/grouppurchase_purchase_ajax.php`; the legacy `group_purchase_sql::insert2()` performs `UPDATE users SET credits = credits - 10` — `purchase`.
- Housekeeping user save — `housekeeping/users.php`; a changed requested credit value is recorded as `admin_grant` with the signed delta.

## MyHabbo audit and scope

`habblet/myhabbo_widgets.php` has these original widget cases: `profilewidget`, `guestbookwidget`, `highscoreswidget`, `badgeswidget`, `friendswidget`, `groupswidget`, `roomswidget`, `traxplayerwidget`, and `ratingwidget`.

The supplied two-table design covers widget placement/visibility/order and a personal user guestbook. It does not model original widget skins, pixel coordinates/z-index, backgrounds, stickers, stickies/notes, music selection, ratings, tags, group-home widgets, per-widget configuration/privacy, inventory/store ownership, or guestbook reporting/moderation. Those are deliberately not invented here; the new layout endpoint only accepts the confirmed user-widget keys and the report flags the missing data model for a later focused design.

## Assumptions and unresolved items

- `group_purchase_sql::insert2()` is the real legacy credit write and charges 10 credits; the existing UI JavaScript incorrectly displays a 20-credit subtraction. The ledger records the verified server-side 10-credit change.
- The existing Phase 6 legacy MyHabbo renderer still reads old non-Polaris home tables. The new custom endpoints are intentionally isolated until a dedicated Phase 6 MyHabbo renderer migration can switch the UI to them.
- The email-verification table/documentation belongs to the separate email PR, so this branch only adds the three combined-feature schemas.

## PHP lint

`php -l` was not run. This task was performed GitHub-only as requested, and no PHP executable is available in this Codex environment. The attempted local command reports:

```text
The term 'php' is not recognized as a name of a cmdlet, function, script file, or executable program.
```

Run this after checkout in an environment with PHP 8.3:

```bash
php -l history.php help.php housekeeping/faq.php housekeeping/users.php habblet/ajax_redeemvoucher.php habblet/ajax_collectiblesPurchase.php habblet/habboclub_habboclub_subscribe.php habblet/myhabbo_store_purchase.php habblet/grouppurchase_purchase_ajax.php habblet/myhabbo_layout_save.php habblet/myhabbo_layouts.php habblet/myhabbo_guestbook.php
```