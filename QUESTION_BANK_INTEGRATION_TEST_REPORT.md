# PHASE 2–4 INTEGRATION TEST — SAFETY GATE AND PLAN

Date checked: 2026-11-17

## Decision: STOPPED BEFORE DATABASE WRITES

No isolated MySQL staging database or staging connection is currently verified. The Laravel backend `.env` points to `DB_CONNECTION=mysql`, host `127.0.0.1`, port `3306`, database **`smart_classroom_monitor`**, username `root` (password intentionally not recorded). This is the protected database explicitly excluded by the request. No database connection, migration, seed, test-account creation, or other database write was attempted.

No listener was found on the checked development ports `3306`, `8000`, `8001`, `5173`, or `8080`; `mysql`/`mysqld` command-line executables were not found. PHP has PDO MySQL and SQLite extensions. Laravel, Vite, and Reverb therefore were not available for live integration testing during this check.

The previous Phase 4 preparation recorded the Phase 2, Phase 3, and Phase 4 migrations as Pending on the then-configured Artisan connection. That connection was subsequently identified as `smart_classroom_monitor`; this report does not query it again. There is no verified staging migration status to report.

## Reports reviewed

- `QUESTION_BANK_INTEGRATION_PLAN.md`
- `QUESTION_BANK_PHASE2_REPORT.md`
- `QUESTION_BANK_PHASE3_REPORT.md`
- `QUESTION_BANK_PHASE4_REPORT.md`

The reports describe an additive question bank, preview snapshot and draft/publish workflow, and per-attempt order mappings. Phase 4's SQLite feature tests and frontend build passed, but MySQL concurrency and two-browser tests were explicitly not performed.

## Integration cases

Every live integration case below is **NOT RUN — staging unavailable**. There are no actual request/response captures, generated IDs, MySQL row comparisons, or browser evidence yet.

| Case | Planned request/action | Expected evidence |
|---|---|---|
| Create bank | As staging teacher, create 20 MC and 5 essay questions through `POST /api/giang-vien/ngan-hang-cau-hoi` | 25 staging-owned rows; MC options have one correct answer; essays have model answers; topic/difficulty counts match submitted payloads. |
| Availability and preview | `POST /api/giang-vien/ngan-hang-de/availability`, then `/preview` with a mixed distribution and both shuffle flags true | Availability reports exact requested groups; preview contains the intended number/type/difficulty and no duplicate source IDs. |
| Confirm/publish | `POST /api/giang-vien/ngan-hang-de/confirm`, then `POST /api/giang-vien/ngan-hang-de/{id}/publish` | One draft assignment, exact copied question/answer snapshot, total score 10; after publish it is visible to enrolled staging students. |
| Two student attempts | Two independent browser profiles call `POST /api/sinh-vien/bai-kiem-tra/{id}/bat-dau` | Two attempt rows keyed by each `sinh_vien.id`, each with complete question/answer mappings; order may differ and is never shared across accounts. |
| Refresh and draft | `GET /api/sinh-vien/bai-kiem-tra/{id}`, `PUT .../{id}/nhap`; use DevTools Offline and reconnect | Each student reloads the same order; saved answers stay keyed by question/answer IDs; failed offline save does not erase local/current answers. |
| Submit and grade | `POST .../{id}/nop`; teacher opens `/api/giang-vien/bai-nop/{submissionId}/chi-tiet` | One submission per student, correct student identity, answer IDs, received order and score; expected score recomputed from answer IDs and stored question points. |
| Concurrent start | Send two simultaneous start requests for the same student/assignment using separate workers against MySQL | One attempt and exactly one complete mapping set; both responses identify the same attempt/order. Sequential retry tests do not substitute for this case. |
| Shuffle off | Publish a separate bank-generated exam with both flags false | New attempts retain base question/answer order; no client-side shuffle. |
| Manual/history regression | Use a staging-only manual exam and a synthetic legacy attempt without order mappings | Manual exam follows existing order and submit/grade flow; legacy attempt falls back to `thu_tu`; no production/history rows are copied or changed. |

No fake data was created. Planned identities, to be used only after staging is verified: teacher `qa.teacher@scm-staging.test`, students `qa.student01@scm-staging.test` and `qa.student02@scm-staging.test`, class `QA-PHASE2-4`. Passwords/tokens must be generated for staging and must not be reused from real accounts.

## Review of the five previously failing Laravel tests

The prior full SQLite test run reported 51 passing and 5 failing tests. Static/test-source review for this integration gate yields:

1. **`DangNhapTest::login_is_rate_limited` — product issue confirmed by source, not just fixture.** The login route has no throttle middleware and `NguoiDungController::dangNhap()` contains no rate limiter; the test's sixth invalid request therefore gets the normal 401. Login rate limiting should be handled as a separate security fix. It was not changed in this phase.
2. **`DatabaseSeederTest` — stale expected fixture count.** The test expects 51 `canh_bao`, while the seeder currently creates one initial alert plus up to 30 in its five-class/two-assignment/three-member loops (31 total for the current seed). The assertion and seeder intent need reconciliation; no seed was run here.
3. **Two `ExamEvidenceTest` cases — environment dependency.** They call `UploadedFile::fake()->image()`, which requires PHP GD. `php -m` shows no GD extension. These cases cannot reach application assertions in this environment; configure GD or use a committed valid image fixture in a separately scoped test change.
4. **`ReportTabLeaveTest` — test fixture is incomplete for the asserted state.** The fixture creates a student and assignment but no `bai_kiem_tra_luot_lam` row. The endpoint correctly only counts a leave after an attempt exists, so it returns zero. Update the fixture to create a started attempt; no application behavior was changed here.

These classifications are based on the prior observed failures and current source/test inspection; they are not a rerun against MySQL staging.

## Safe setup and next actions

Before any write, one of these is required:

1. **Preferred:** provision a dedicated MySQL staging server/database and provide/configure its connection in a separate environment file (for example `.env.staging`). The database name must be visibly distinct, such as `smart_classroom_monitor_staging_phase24`, and must not equal `smart_classroom_monitor`.
2. **Alternatively:** explicitly authorize provisioning a local MySQL staging instance/database. This environment currently has no MySQL listener or CLI, so that also requires approval to install/start the service or use an available staging server.

Afterward, verify the effective Laravel connection without printing credentials, require an exact database-name allowlist and an explicit refusal when the name is `smart_classroom_monitor`, then check migration status on staging. Only then run the three additive migrations (never `migrate:fresh`), create the synthetic fixture and execute the cases above. Capture request method/path, sanitized payload, status/response fields, and before/after staging row counts. Use browser Network/Console evidence from two distinct student profiles and a teacher profile.

No migration, seeding, account creation, live API test, browser test, Git commit, or Git push was performed for this report. The report itself is the only file created in this check.
