# Phase 6 — habblet batch 2 checkpoint

Status: implementation saved at the user's request to pause for usage limits. **Final review and PR creation remain outstanding. Do not merge.**

Branch: `feature/phase6-habblet-batch2`. Based on `feature/phase6-habblet-batch1` at `c3d7d703f101e3b4df0623445b81d4c3cfecf013`. PR #19 is still open; create batch 2 as a stacked PR targeting that branch, unless batch 1 has since merged. Deployment was not changed.

## Scope and implementation

The exact frozen batch-2 list contains 39 files, reproduced below. All 39 now dispatch through `includes/habblet_groups_actions.php` and `includes/habblet_groups.php`; original HTML was extracted into `includes/habblet-templates/`. Batch 1 and batch 3 handlers are unchanged. The original audit pattern now finds exactly 34 remaining habblets, corresponding to batch 3.

31 endpoints are migrated or partially supported. Eight explicitly return unavailable (501) after authentication: discussion deletepost; group purchase; start/save/cancel editing session; custom URL check; badge editor and badge update. No schema migrations, invented columns, or Polaris schema alterations.

Implemented native operations: forum topic creation, replies, previews, author/moderator edits, topic settings and permanent topic deletion; group join/request/leave; favorite selection; member acceptance/decline/removal and owner-only admin grants/revocation; settings updates; owner-only group deletion with verified cleanup; member lists, widget paging and group info.

Transactions protect multi-row writes. Group/thread/post IDs are scoped together. Native ranks are owner 0, admin 1, member 2, requested 3, blocked/deleted 4. Authorization does not reuse legacy numeric comparisons. Topic and message permissions remain independent. Membership removal clears only the affected matching favorite. No missing users_settings row is invented. Forum posting updates both thread and account counters. Permanent topic deletion follows the emulator's delete path; lifetime account post counters are retained as in its implementation.

## Verified schema and source

Required references read: `PHPRetro-Modernization-Plan.md`, `Polaris-Schema-Reference.md`, `references/schema/CleanDB.sql`, and `references/Custom-Schema-Additions.md` (the actual tracked custom-reference path).

| Table | Columns used |
| --- | --- |
| users | id, username, look, motto, online, mail_verified, account_created |
| users_settings | user_id, guild_id, hide_online, forums_post_count |
| users_badges | id, user_id, slot_id, badge_code |
| guilds | id, user_id, name, description, room_id, state, badge, date_created, forum, read_forum, post_messages, post_threads, mod_forum |
| guilds_members | id, guild_id, user_id, level_id, member_since |
| guilds_forums_threads | id, guild_id, opener_id, subject, posts_count, created_at, updated_at, state, pinned, locked, admin_id |
| guilds_forums_comments | id, thread_id, user_id, message, created_at, state |
| guild_forum_views | guild_id (cleanup); user_id and timestamp in test fixtures |
| rooms | id, owner_id, name, description, guild_id |
| items | guild_id (cleanup); id in test fixtures |

Semantics were verified in `duckietm/Polaris-Emulator` commit `0060de6b4668b8572d9b624c47eb3a141e3be30a`: GuildRank, GuildState, GuildManager, GuildBadgeBuilder, RequestGuildBuyEvent, GuildSetAdminEvent, GuildChangeSettingsEvent, GuildChangeBadgeEvent, ForumThread, ForumThreadState, GuildForumPostThreadEvent, GuildForumModerateThreadEvent and GuildForumModerateMessageEvent. A local source checkout is at the sibling `work/polaris-reference` directory.

## Explicit gaps and limits

- Homes/layout placement and editing-session ownership have no real equivalent. The simplified PHPRetro layout table is not substituted.
- Group URL aliases have no equivalent; numeric group links are used.
- Legacy Flash badges use two-digit part IDs; Polaris uses three-digit IDs. Editing is unavailable. Group-info/settings use the existing generic group icon; forum badge containers remain but incompatible group badge images are omitted. Native avatar badges remain supported. No CSS/JS assets were changed.
- Purchase lacks native required room, colors, badge parts and club/price policy. It does not charge or create a group; use the game client.
- Individual comment moderation is unresolved: ForumThreadState labels 10 staff-hidden and 20 guild-admin-hidden, while GuildForumModerateMessageEvent restricts 20 to staff. No guessed state is written. Both hidden values are redacted on read. Permanent topic deletion has a separate verified path and is implemented.
- No comment edit timestamp/history column exists; message edits do not fabricate one.
- Room transfer requires native room-rights effects; settings reject a changed roomId. Custom URLs are disabled. Existing native ADMINS-only reading, OWNER-only thread posting, and LARGE_CLOSED state cannot be represented by this legacy form; settings return 501 rather than downgrade them.
- The underlying groups table is latin1 with a 250-character description; unsupported characters and excessive descriptions are rejected. Legacy 30-character group names and 32-character forum titles are retained.
- Live emulator caches are not synchronized by direct CMS database writes. No verified bridge was supplied. Group/member/forum changes need live-emulator validation; do not claim instant client synchronization.
- Root `groups.php`, `discussions.php` and other out-of-batch flows still need live testing. In particular, discussions.php contains reversed rank checks and legacy helper calls; it was not rewritten in this batch. Full browser navigation is not claimed restored.
- Authenticated tests use a session fixture because the existing HoloUser/PDO session-serialization flow is outside this batch. No full browser login or pixel-comparison validation has been performed.

## Validation completed

- 163 assertions passed against a disposable MariaDB database created from checked-in CREATE definitions, with PHP warnings treated as exceptions. Every batch-2 endpoint is exercised. Tests cover native permissions, owner protection, cross-group/post attacks, malformed input, atomic rollback, favorite cleanup, real form parameter parsing, previews, counters, hidden-content redaction, captcha, native pagination, deletion cleanup and preserved HTML IDs/classes.
- Batch-1 regression suite: 62 assertions passed.
- PHP 8.5.10 lint: all 293 PHP files passed, zero failures.
- Actual HTTP requests through a temporary PHP server: all 39 anonymous AJAX requests redirected to login (302), without warnings/fatals. The temporary server and ignored .env were removed afterward. Deployment and application data were not changed by these anonymous requests.
- Frozen-list comparison: exactly 39 changed habblets, zero legacy audit matches in those files; 34 remaining matches.
- After the successful tests, the batch-2 fixture table list was trimmed to the ten tables actually needed. Rerun once after resuming to verify that final cleanup.

Run the database test with DB_DSN/DB_USER/DB_PASS for a MariaDB account allowed to create and drop a scratch database. `tests/phase6_habblet_batch2_test.php` never selects the application database for test operations.

## Resume checklist

1. Read this report; inspect git status and the saved diff. Do not redo the grouping.
2. Rerun batch-2 tests after the fixture-table cleanup, then finish code review. In particular review normal GET behavior for legacy no_ajax editing/badge actions, online-visibility handling in forum authors, native large-group labels versus legacy 5,000/unlimited text, and any frontend contract mismatches.
3. Finish the report with a per-endpoint result table if useful; do not mark unresolved features as restored.
4. Run git diff --check, create the requested PR (stacked on batch 1 while #19 is open), and record its URL. Do not merge or deploy.

## Frozen batch 2 inventory

### Batch 2 — Discussions, groups and member management (39 files)

- `habblet/discussions_actions_deletepost.php`
- `habblet/discussions_actions_deletetopic.php`
- `habblet/discussions_actions_newtopic.php`
- `habblet/discussions_actions_opentopicsettings.php`
- `habblet/discussions_actions_previewpost.php`
- `habblet/discussions_actions_previewtopic.php`
- `habblet/discussions_actions_savepost.php`
- `habblet/discussions_actions_savetopic.php`
- `habblet/discussions_actions_savetopicsettings.php`
- `habblet/discussions_actions_updatepost.php`
- `habblet/grouppurchase_purchase_ajax.php`
- `habblet/groups_actions_cancelEditingSession.php`
- `habblet/groups_actions_check_group_url.php`
- `habblet/groups_actions_confirm_delete_group.php`
- `habblet/groups_actions_confirm_select_favorite.php`
- `habblet/groups_actions_delete_group.php`
- `habblet/groups_actions_deselect_favorite.php`
- `habblet/groups_actions_group_settings.php`
- `habblet/groups_actions_join.php`
- `habblet/groups_actions_leave.php`
- `habblet/groups_actions_saveEditingSession.php`
- `habblet/groups_actions_select_favorite.php`
- `habblet/groups_actions_show_badge_editor.php`
- `habblet/groups_actions_startEditingSession.php`
- `habblet/groups_actions_update_group_badge.php`
- `habblet/groups_actions_update_group_settings.php`
- `habblet/myhabbo_avatarlist_membersearchpaging.php`
- `habblet/myhabbo_groups_batch_accept.php`
- `habblet/myhabbo_groups_batch_confirm_accept.php`
- `habblet/myhabbo_groups_batch_confirm_decline.php`
- `habblet/myhabbo_groups_batch_confirm_give_rights.php`
- `habblet/myhabbo_groups_batch_confirm_remove.php`
- `habblet/myhabbo_groups_batch_confirm_revoke_rights.php`
- `habblet/myhabbo_groups_batch_decline.php`
- `habblet/myhabbo_groups_batch_give_rights.php`
- `habblet/myhabbo_groups_batch_remove.php`
- `habblet/myhabbo_groups_batch_revoke_rights.php`
- `habblet/myhabbo_groups_groupinfo.php`
- `habblet/myhabbo_groups_memberlist.php`

