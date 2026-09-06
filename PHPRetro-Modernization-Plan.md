# PHPRetro Modernization Plan
`# Codex Task Template — PHPRetro-on-Polaris`

`Use this structure for every task you hand Codex. Copy the template, fill in the`

`brackets, paste into a new Codex cloud task against your connected repo.`

`---`

`## Setup (one-time)`

``1. Go to `chatgpt.com/codex`, sign in (ChatGPT Go works — included on all plans,``

`usage limits are just lower than Plus/Pro).`

`2. Connect GitHub, select your PHPRetro fork.`

`3. Create an environment for the repo (Codex will ask for this on first task) — this`

`is where it installs dependencies, sets env vars, etc. Point it at PHP + whatever`

`local test setup you have; it doesn't need a live Polaris server to write code, only`

`to actually test the queries later.`

``4. Add `Polaris-Schema-Reference.md` and `PHPRetro-Modernization-Plan.md` to the repo``

`root (commit them for real — don't re-paste them every task). Reference them by`

`filename in every task instead of re-uploading.`

`## Per-task prompt template`

` ``` `

`Branch: feature/phase<N>-<short-description>`

`Context files already in the repo (read these first):`

`- PHPRetro-Modernization-Plan.md — global conventions + this phase's brief`

`- Polaris-Schema-Reference.md — the ONLY source of truth for table/column names.`

`  If a table/column you need isn't listed there, grep the full CleanDB.sql`

`  (fetch from https://github.com/duckietm/Polaris-Emulator if not already local)`

`  before writing any query against it. NEVER invent a table or column name.`

`Task: [Do Phase N as described in the plan — OR — Redo Phase N's existing DeepSeek`

`output, listed below, targeting Polaris's real schema instead of the old`

`Holograph-era assumptions it was originally written against.]`

`Reference-only files (existing DeepSeek output — logic/structure reference,`

`NOT verified against real schema, do not assume table names in these are correct):`

`[paste or attach the DeepSeek batch output here, if reworking existing code]`

`Required output:`

`1. Make the actual code changes on the branch above.`

`2. Open a pull request against main/dev with a clear title and description.`

`3. In the SAME PR, add a file at /docs/phase-reports/phase<N>-<description>.md`

`   containing:`

`   - Summary of what changed and why (2-3 sentences)`

`   - List of every file changed`

`   - List of every table/column referenced, with confirmation each one was`

`     verified against Polaris-Schema-Reference.md or CleanDB.sql directly`

`     (not guessed)`

`   - Any assumptions made, flagged explicitly`

`   - Any TODOs or things left unresolved, flagged explicitly`

`   - Test/lint results (php -l output, any test run, etc.)`

`4. Do NOT merge the PR yourself. Leave it open for review.`

` ``` `

`## After Codex opens the PR`

``1. Copy the PR link and the `/docs/phase-reports/...md` file content into a message``

`to Claude (paste the .md report — that's the compact, reviewable summary; only`

`paste the full diff if Claude asks for it after reading the report).`

`2. Claude reviews against the plan doc + schema reference, flags anything.`

`3. If clean: merge the PR yourself on GitHub.`

`4. If not clean: reply on the Codex task (or open a new one) with the specific fix`

`needed, referencing what Claude flagged.`

``5. Either way, the `/docs/phase-reports/` file stays in the repo permanently —``

`this becomes your durable project history, readable in any future session`

`without needing to re-paste anything.`

`## Recommended repo housekeeping alongside this`

``- **Commit `Polaris-Schema-Reference.md` and the plan doc to the repo itself**, not``

`just kept in chat — so Codex, DeepSeek, or a future you always has them without`

`re-uploading.`

``- **Keep `/docs/phase-reports/` as a permanent, append-only log** — never delete old``

`reports, even after a phase is redone; add a new dated report instead so the`

`history of "what changed and why" stays intact.`

``- **Never let Codex commit real DB credentials.** Confirm early that `.env`/``

`` `config.php` secrets stay out of the repo (should already be true per the plan's ``

`global conventions) — worth an explicit check in the very first task's report.`

`- **One task = one focused PR.** Don't let a single Codex task span multiple phases;`

`it defeats the whole point of small, reviewable checkpoints.`

`- **Ask Codex to flag scope-creep risks itself**, e.g. "I noticed X is also broken`

`but it's outside this phase's scope — noting here rather than fixing" — add this`

`as an explicit instruction if reports come back too narrow or too broad.`
