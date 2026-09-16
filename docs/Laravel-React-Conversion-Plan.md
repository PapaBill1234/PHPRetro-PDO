# Laravel + React conversion plan

## Goal

Replace the legacy PHPRetro PHP and Prototype application with a maintainable
Laravel and React application while keeping the public and staff-facing visual
design **1:1** with the current site. This is a staged rewrite: the existing
site stays live until each replacement area has passed functional and visual
parity checks.

The first release is intentionally not a redesign. It retains `web-gallery`
CSS, images, sprites, page chrome, class names, dimensions, and copy. A later
project may redesign the UI after the replacement is stable.

## Proposed stack

| Area | Choice | Why |
| --- | --- | --- |
| Application | Laravel | Mature PHP framework for routing, validation, queues, caching, policies, migrations, and tests. |
| Browser UI | React + TypeScript | Widely used component model for replacing Prototype behaviours without returning HTML fragments from every endpoint. |
| Laravel/React bridge | Inertia.js | Lets Laravel supply routing, authentication, policies, and server responses while React renders pages. It avoids building and maintaining a separate public API for ordinary website screens. |
| Staff CMS | Filament | Mature Laravel admin framework for forms, tables, filters, role gates, audit views, and CRUD workflows. It should be styled to preserve required staff workflows before replacing old housekeeping pages. |
| Database access | Laravel Query Builder / Eloquent, with explicit repository services | Use parameter binding everywhere. Query Polaris-owned tables through narrowly scoped services; do not let generic CMS models modify them. |
| Client state / forms | React Hook Form + Zod | Explicit validation and predictable form submissions for dialogs and editor screens. |
| Server state | TanStack Query | Caches server data and supports reliable optimistic updates for messaging, groups, staff tools, and the Homes editor. |
| Styling | Existing `web-gallery` CSS and assets first | Provides exact visual parity. New isolated CSS may be added only when a legacy asset has no safe equivalent. |
| Tests | Pest + Playwright | Pest verifies services, permissions, and database work. Playwright checks real browser flows and screenshot parity. |
| Cache, sessions, queue, locks | Redis | Keeps sessions and cache fast; provides rate-limit counters, background jobs, and atomic locks for conflicting Homes edits. |
| Queue operations | Laravel Horizon | Shows queued email, media, cleanup, and scheduled CMS work, including failures and retries. |
| Realtime events | Laravel Reverb | Delivers minimail notifications, friend-presence changes, staff alerts, and live CMS updates over WebSockets. |
| Homes drag/drop | dnd-kit | Modern accessible drag/drop behavior while retaining the original Homes visuals and dimensions. |
| Media | S3-compatible object storage | Stores CMS media, uploads, cached avatar images, and backups outside web-server disk; use MinIO locally and an S3-compatible provider in production. |
| Search | Laravel Scout + Meilisearch | Adds fast user, room, group, article, and staff-log search when database `LIKE` searches are no longer sufficient. |
| Monitoring | Sentry + OpenTelemetry-compatible metrics | Captures production errors and exposes request, queue, database, and WebSocket health for operations dashboards. |
| Delivery | Docker Compose + GitHub Actions | Makes the Laravel, MariaDB, Redis, worker, Reverb, and imager environment repeatable and verifies every pull request. |

No ORM is allowed to "take ownership" of Polaris. Polaris continues to own
hotel users, rooms, guilds, inventory, catalog, balances, and hotel-side
permissions. Laravel owns new website/CMS tables under the `phpretro_` prefix.

## Service boundaries

This is the modern equivalent of a large hotel website's separation of
responsibilities. It keeps website code scalable without pretending that
Laravel can replace the game server.

```text
Browser
  └─ React + TypeScript + existing web-gallery design
       └─ Laravel application and Filament CMS
            ├─ Redis: sessions, cache, queues, locks, realtime coordination
            ├─ phpretro_* tables: CMS and website-owned state
            ├─ Polaris services/tables: verified hotel operations only
            ├─ Reverb: website realtime events
            ├─ S3-compatible storage: media and image caches
            └─ Horizon / Sentry / metrics: jobs, errors, and operations
                 └─ Polaris hotel server and Nitro client
```

Laravel is the website and CMS layer. Polaris and Nitro remain the hotel/game
layer. Features such as catalog-owned Club purchase, room transfer, Trax, and
native badge editing still require verified emulator/client support.

## Adoption order for supporting services

Start the first vertical slice with Laravel, React, TypeScript, Inertia,
Filament, Redis, Pest, Playwright, Docker Compose, and GitHub Actions. Add
Horizon when the first queued work is introduced and Reverb when minimail or
presence notifications are migrated. Add object storage before accepting CMS
uploads. Add Meilisearch only when real search volume makes MySQL searches
insufficient. Add Sentry and metrics before public cutover.

## Rules that apply to every phase

1. Do not alter a Polaris-owned table unless a verified Polaris extension or
   emulator change explicitly requires it.
2. Preserve a legacy page's HTML structure and CSS classes before attempting
   visual improvement.
3. Put every Polaris query behind a service with explicit allowed operations.
4. Use request validation, authorization policies, CSRF protection, prepared
   queries, output-context encoding, and audit records from the first route.
5. Add a functional test and a Playwright flow before switching a production
   route from legacy PHP to Laravel.
6. Record every unsupported hotel operation as an explicit feature gap; never
   simulate success or invent a Polaris table/column.

## Phase 1 — Architecture baseline and parity inventory

**Outcome:** an agreed map of what exists, who owns each table, and what is
being replaced.

- Freeze a route and feature inventory for the 34 public pages, 136 habblets,
  Homes/groups interactions, and 28 housekeeping screens.
- Classify every query as Polaris-owned, PHPRetro-owned, legacy-only, or
  unsupported by Polaris.
- Capture reference screenshots and browser flows for guest, normal user,
  group owner/moderator, and staff roles.
- Define acceptance criteria for each page: matching markup/classes, behavior,
  authorization, and screenshot tolerance.
- Keep the existing security/XSS audit as a migration input, not as a reason
  to reproduce unsafe output contracts.

**Exit condition:** a signed-off parity backlog with no ambiguous table owner
or unsupported feature hidden in a ticket.

## Phase 2 — Laravel foundation and local delivery

**Outcome:** a Laravel application that can run beside the legacy site.

- Create a separate Laravel app directory/repository boundary; do not replace
  `C:\xampp\htdocs` in place.
- Configure environment loading, logging, error handling, health checks,
  Redis cache/sessions/queues/locks, scheduler, and secure session defaults.
- Define Docker Compose services for Laravel, MariaDB, Redis, queue worker,
  Reverb, Polaris-imager, and local S3-compatible storage where needed.
- Import `web-gallery` assets through Vite without changing their paths or
  appearance.
- Add CI for PHP linting, Laravel tests, TypeScript checks, and Playwright.
- Configure Sentry-style error reporting and baseline request/queue metrics
  before any public route moves to Laravel.
- Create a route switch/proxy plan so individual URLs can move from legacy PHP
  to Laravel one at a time and roll back immediately.

**Exit condition:** a blank Laravel page renders under the existing chrome and
passes automated deployment and rollback checks.

## Phase 3 — Authentication, authorization, and Polaris access layer

**Outcome:** safe shared identity and an explicit boundary around hotel data.

- Implement login, logout, remember-me, password reset, session regeneration,
  email verification, and staff step-up authentication as required.
- Implement Laravel policies for visitor, user, group member, group moderator,
  group owner, and each staff rank/capability.
- Build read/write services for verified Polaris schema operations, beginning
  with users, settings, guilds, rooms, messenger, and forums.
- Build the existing client SSO handoff only from verified Polaris fields and
  documented emulator behavior.
- Add audit logging for staff and sensitive account actions.

**Exit condition:** a user can authenticate, see their identity, open the
hotel client, and cannot call a Polaris operation outside an authorized
service method.

## Phase 4 — CMS and public content

**Outcome:** staff can safely manage content through Laravel, while public
pages remain visually identical.

- Introduce `phpretro_` models/migrations for news, campaigns, collectables,
  banners, FAQ, site settings, maintenance, and page content.
- Implement a Filament CMS with validation, preview, scheduled publishing,
  role restrictions, and audit history.
- Treat raw HTML, scripts, tracking snippets, and arbitrary CSS as explicit
  high-trust features with dedicated permission gates and warnings.
- Move the public landing, community, articles, FAQ, collectables,
  maintenance, and RSS routes to React/Inertia.
- Create screenshot tests using the original pages as the baseline.

**Exit condition:** staff can manage public content and the converted public
pages meet visual and functional parity without raw request output.

## Phase 5 — Account, profile, credits, Club, and client entry

**Outcome:** core user journeys work in Laravel without changing the look.

- Convert account, registration, recovery, reauthentication, profile, `me`,
  credits, Club display, and client-entry pages.
- Use Polaris services only for verified balance, subscription, and profile
  reads/writes.
- Preserve existing dialogs, button IDs, page states, and visual messages in
  React components.
- Keep unsupported operations explicit: website voucher redemption and native
  Club purchase require actual emulator support before being marked complete.

**Exit condition:** a new and existing user can complete the supported account
and hotel-entry journeys with matching screenshots and permission tests.

## Phase 6 — Messaging and friend management

**Outcome:** minimail, requests, and friendship flows replace legacy habblets.

- Convert mailbox lists, threads, compose, delete/read state, friend requests,
  friend search, categories, and online presence displays.
- Replace HTML-fragment RPC responses with typed React form/action results.
- Introduce Reverb only for real user-visible events, beginning with new-mail
  notifications and friend-presence updates.
- Enforce recipient, friendship, ownership, pagination, rate-limit, and
  anti-spam checks in service methods.
- Test users attempting to read, delete, or send as another account.

**Exit condition:** the complete supported minimail/friends flow works through
React, with browser tests covering common and unauthorized actions.

## Phase 7 — Groups, discussions, and group profile surfaces

**Outcome:** group browsing and administration work against real Polaris
guild/forum data.

- Convert group pages, member lists, membership requests, owner/admin tools,
  settings, forum topics/posts/moderation, tags, and group widgets.
- Keep all guild and room relationships mapped to verified Polaris columns.
- Preserve unsupported items as client handoffs: Flash badge editing, group
room transfer, and any permissions Polaris cannot represent.
- Use rich-text/BBCode rendering with a defined allowlist; never reproduce raw
HTML behavior for member content.

**Exit condition:** owner, moderator, member, and visitor workflows pass
permission, browser, and visual-parity tests.

## Phase 8 — MyHabbo Homes and interactive editor

**Outcome:** the pixel playground is rebuilt deliberately, rather than copied
through a compatibility layer.

- Define a versioned JSON layout API over `phpretro_myhabbo_*` tables and
  verified widget/catalogue data.
- Build React components for canvas/grid, drag/drop, z-index, widget
  positions, notes, stickers, store, ratings, guestbook, and privacy.
- Use dnd-kit for pointer/keyboard drag behavior and TanStack Query for
  cached layout state, optimistic updates, and recovery after failed saves.
- Retain the current images, sprites, dimensions, and interaction visuals.
- Add optimistic updates with transactions, version checks, and rollback to
  prevent conflicting editor writes.
- Port group Homes only after user Homes passes all interaction tests.

**Exit condition:** edited layouts persist correctly, concurrent edits do not
silently overwrite one another, and screenshot plus drag/drop tests match the
legacy experience.

## Phase 9 — Housekeeping replacement and operations

**Outcome:** all supported staff workflows leave legacy housekeeping.

- Finish Filament resources/custom pages for user management, moderation,
  bans, alerts, news, banners, campaigns, catalogue, collectables, vouchers,
  reports, logs, staff sessions, and settings.
- Add approval/confirmation flows for destructive actions, staff audit trails,
  search/filtering, exports, and operational dashboards.
- Integrate only verified Polaris RCON/API commands. Do not present disabled
  controls as working features.
- Add load, authorization, audit-log, and failure-mode tests.

**Exit condition:** staff can operate all supported website functions without
the old housekeeping code, with every action attributed and reversible where
possible.

## Phase 10 — Cutover, hardening, and legacy retirement

**Outcome:** Laravel becomes the production website with a clear support
boundary.

- Run a route-by-route parallel verification period and compare production-like
  screenshots, responses, logs, and database effects.
- Complete security reviews for SQL injection, reflected/stored/DOM XSS, CSRF,
  IDOR, redirects, file handling, sessions, and rate limits.
- Complete accessibility, performance, cache, queue, backup, recovery, and
  observability checks.
- Cut routes over gradually, retain a tested rollback path, then archive
  legacy PHP only after stable operation.
- Publish an unsupported-feature register for emulator-bound work: Trax,
  native Club purchase, voucher redemption, room transfer, and badge editing.

**Exit condition:** all planned supported routes serve Laravel/React, the
legacy app is read-only/retired, and remaining hotel-side work is tracked as
separate Polaris/Nitro projects.

## Suggested first delivery slice

The first vertical slice should include Phase 2 plus the smallest safe part of
Phase 3 and Phase 5: Laravel bootstrapping, existing visual chrome, login,
logout, `me`, profile read, and open-hotel client handoff. It establishes the
real architecture, proves Polaris integration, and gives screenshot tests a
baseline before the high-complexity Homes and group work begins.

## Deliberate non-goals for the conversion

- Rebuilding Flash assets by pretending they are React components.
- Changing Polaris schema ownership to make Laravel models convenient.
- A visual redesign or Tailwind replacement during parity work.
- Declaring emulator-limited actions complete without a verified Polaris/Nitro
  implementation.
