# Laravel + React AI handoff prompts

These prompts prepare work only. They do not authorize implementation,
dependency installation, schema changes, deployment, pull requests, or merges.
Use the handoff prompt first, then send one phase prompt in a separate task.

## Reusable project handoff prompt

```text
You are preparing a safe, evidence-based plan for the PHPRetro-PDO Laravel and
React conversion. Do not write or modify application code, migrations, package
files, configuration, Docker files, database rows, or GitHub state. Do not run
destructive commands. Do not create a PR or merge anything.

Repository: PapaBill1234/PHPRetro-PDO
Current live PHP/XAMPP checkout: C:\xampp\htdocs
Target hotel server/database: Polaris CleanDB schema. Polaris-owned tables must
never be altered or treated as Laravel-owned without verified schema and
emulator evidence. Website/CMS-owned tables use the phpretro_ prefix.

Read first:
- docs/Laravel-React-Conversion-Plan.md
- PHPRetro-Modernization-Plan.md
- Polaris-Schema-Reference.md
- references/schema/CleanDB.sql
- references/schema/Custom-Schema-Additions.md
- relevant docs/phase-reports/*.md

Product requirement: preserve the current public and staff visual design 1:1
for the conversion. Reuse web-gallery CSS, images, sprites, class names,
dimensions, and page structure. This is not a redesign.

Target architecture:
- current supported Laravel release, React, TypeScript, Inertia, Vite
- Filament for staff CMS
- Redis for cache, sessions, queues, locks, and rate limiting
- Horizon for Redis queue operations; Reverb for user-visible realtime events
- Pest and Playwright for behavior and screenshot-parity testing
- existing web-gallery CSS/assets during parity work
- S3-compatible storage, Meilisearch, Sentry, and metrics at the plan-defined
  adoption points

Security requirements: prepared statements, request validation, authorization
policies, CSRF, output-context encoding, secure sessions, audit logs, rate
limits, and no raw member HTML. Never reproduce a legacy unsafe behavior just
to match an old endpoint.

Polaris limits: do not claim website voucher redemption, native Club purchase,
room transfer, Trax, or Flash badge editing works unless verified Polaris/Nitro
support exists. Record unsupported features as gaps with evidence.

Your deliverable is a preparation report only. Include:
1. What you inspected, with exact files/lines and schema references.
2. Verified current behavior and data ownership.
3. Proposed Laravel/React components, services, routes, policies, tests, and
   migration approach. Label every item as verified, inferred, or blocked.
4. Dependencies and their purpose; use current maintained versions only.
5. Visual-parity requirements, screenshots, and Playwright scenarios.
6. Risks, unsupported Polaris operations, and questions that cannot be safely
   answered from repository evidence.
7. A small implementation checklist, ordered so each item is reviewable.

Wait for explicit approval before making any change.
```

## Phase 1 — architecture baseline and parity inventory

```text
Using the PHPRetro Laravel/React handoff instructions, prepare Phase 1 only.
Inventory every public route, habblet/AJAX route, housekeeping route, job-like
process, and legacy client handoff. Map each feature to its current PHP files,
database tables, schema owner, actor roles, and visual reference page. Identify
all 501/unsupported paths separately. Propose a parity matrix and a screenshot
fixture plan. Do not change code or database state.
```

## Phase 2 — Laravel foundation and delivery

```text
Using the PHPRetro Laravel/React handoff instructions, prepare Phase 2 only.
Design a fresh current-Laravel application that runs beside the legacy XAMPP
site. Specify the repository/directory boundary, environment variables,
Docker Compose services, Vite asset import strategy, route-by-route proxy or
cutover design, Redis configuration, queue/scheduler setup, health checks,
logging, CI, local development workflow, and rollback plan. Preserve
web-gallery asset URLs and visuals. Do not create files or install packages.
```

## Phase 3 — identity, policies, and Polaris access

```text
Using the PHPRetro Laravel/React handoff instructions, prepare Phase 3 only.
Trace existing login, logout, registration, password recovery, email
verification, staff authentication, sessions, client SSO, user reads/writes,
and rank gates. Verify every relevant Polaris CleanDB column. Propose Laravel
authentication, session regeneration, policies/capabilities, audit events, and
narrow Polaris service interfaces. Identify operations that must not be exposed
through generic Eloquent models. Do not implement anything.
```

## Phase 4 — CMS and public content

```text
Using the PHPRetro Laravel/React handoff instructions, prepare Phase 4 only.
Trace public landing/community/news/articles/FAQ/collectables/maintenance/RSS
behavior and their housekeeping writers. Separate Polaris tables from existing
phpretro_ data. Propose Filament resources, Laravel content services, React
pages, media handling, scheduling, permissions, audit events, URL validation,
and screenshot tests. Identify every intentional raw-HTML/tracking setting and
its required trust boundary. Do not modify code or database state.
```

## Phase 5 — account, profile, balances, Club, and client entry

```text
Using the PHPRetro Laravel/React handoff instructions, prepare Phase 5 only.
Trace account, registration, recovery, reauthentication, profile, me, credits,
Club display, and client-entry flows. Verify all Polaris user, balance,
subscription, and SSO schema references. Propose Laravel routes, React
components, validation, policies, service methods, error states, and Playwright
flows that retain the old page structure. Separate display-only data from every
emulator-bound purchase/redemption feature. Do not implement anything.
```

## Phase 6 — minimail and friends

```text
Using the PHPRetro Laravel/React handoff instructions, prepare Phase 6 only.
Trace minimail, inbox/thread/compose/read/delete operations, friend search,
friend requests, categories, and online indicators. Verify Polaris table and
column mappings. Propose typed Laravel actions, authorization checks,
anti-spam/rate limits, React state/query design, Reverb event boundaries, and
Pest/Playwright scenarios including unauthorized access attempts. Do not alter
code or database state.
```

## Phase 7 — groups and discussions

```text
Using the PHPRetro Laravel/React handoff instructions, prepare Phase 7 only.
Trace group pages, member lists, requests, ownership/admin controls, settings,
forum topics/posts/moderation, tags, room links, and group widgets. Verify all
guild/forum schema data against Polaris CleanDB. Propose policies for visitor,
member, moderator, owner, and staff; Laravel services and React components;
BBCode/rich-text rules; and visual/permission tests. Explicitly flag badge
editor, room transfer, and other emulator-bound gaps. Do not implement.
```

## Phase 8 — MyHabbo Homes editor

```text
Using the PHPRetro Laravel/React handoff instructions, prepare Phase 8 only.
Trace the complete user and group Homes experience: layouts, widgets, notes,
stickers, catalogue/store, inventory, guestbook, ratings, privacy, drag/drop,
z-index, and concurrent saves. Verify every phpretro_myhabbo table/column and
its relationship to Polaris. Propose versioned layout APIs, transaction and
locking design, dnd-kit component model, TanStack Query state model, rollback
rules, screenshot fixtures, and browser drag/drop tests. Do not build it.
```

## Phase 9 — housekeeping and operations

```text
Using the PHPRetro Laravel/React handoff instructions, prepare Phase 9 only.
Inventory every housekeeping screen and classify it as a Filament resource,
custom Filament page, read-only operational dashboard, or unsupported Polaris
operation. Trace rank gates, destructive actions, RCON/API calls, vouchers,
reports, alerts, bans, audit logs, staff sessions, exports, and media. Propose
approval/confirmation behavior, audit events, queue work, Horizon visibility,
and failure-state tests. Do not make changes.
```

## Phase 10 — cutover and retirement

```text
Using the PHPRetro Laravel/React handoff instructions, prepare Phase 10 only.
Design a route-by-route parallel-run, verification, rollout, rollback, backup,
and legacy-retirement process. Include security review gates, screenshot and
functional parity thresholds, performance/load checks, queue/realtime
monitoring, observability, production secrets, incident recovery, and an
explicit remaining Polaris/Nitro feature register. Do not deploy or modify any
environment.
```

## How to use these prompts

1. Start a new task and send the reusable project handoff prompt.
2. Send the single phase prompt you want prepared.
3. Review the resulting evidence and checklist.
4. Send a separate explicit implementation instruction only after approving
   that phase's preparation report.

