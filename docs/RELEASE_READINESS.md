# ElevateHer360 release readiness register

**Assessment date:** 11 October 2026  
**Status:** Not approved for production release. This is a repository-local assessment, not production or stakeholder acceptance evidence.

## Prioritised issue register

| Priority | Issue / required evidence | Status | Release effect |
| --- | --- | --- | --- |
| P0 | Branding upload used the client filename extension on a public disk | Fixed in this change: extension is derived from decoded PNG/JPEG/WebP/GIF image bytes; regression test covers a PNG payload named `.jpg`. | Code-level fix verified; hosting must still disable script execution in uploaded/public storage. |
| P1 | ICO favicon accepted although delivery only safely recognizes raster formats | Fixed in this change by removing ICO from accepted upload formats. Add safe ICO decode/delivery before re-enabling. | Closed for currently supported upload workflow. |
| P1 | Five Flutter analyzer infos prevented a clean analyzer gate | Fixed and verified: final `flutter analyze --no-pub` reports no issues. | Closed for current source. |
| P1 | Full install/upgrade migration run and production-like MySQL validation | Not run; configured local MySQL at `127.0.0.1:3306` refused connection during `schedule:list`. | Blocker. |
| P1 | Offline rejected-write recovery, durable push retries, stale file-cache policy | Existing limitations documented in `DEVELOPER_SYSTEM_REPORT_AND_USER_MANUAL.md`; no complete remediation/evidence in this pass. | Blocker for reliability-dependent launch claims. |
| P1 | Role-based UAT (admin, programme team, instructor, ordinary staff, mentor, employer, participant) | Not performed; no named acceptance users or production-like data available. | Blocker. |
| P1 | Backup restoration, deployment recovery, monitoring and notification delivery drills | Not performed; requires approved staging infrastructure and credentials. | Blocker. |
| P1 | Android signed release build/device verification | Not performed; release signing configuration and physical device unavailable. | Blocker for Android distribution. |
| P1 | iOS build/signing/APNs/TestFlight validation | Not performed; requires macOS/Xcode and Apple signing access. | Blocker for iOS distribution. |
| P2 | Clean dependency install, browser/device integration, load and security testing | Not completed in this pass. Existing tests are not a penetration or performance audit. | Required before broad production approval. |

## Verified checks in this work session

| Check | Result |
| --- | --- |
| `php artisan test --filter=BrandManifestTest` | Passed: 15 tests, 181 assertions. |
| `php artisan test --compact` | Passed: 538 tests, 3,375 assertions. |
| `node --test tests/js/sw-branding.test.cjs` | Passed: 6 tests. |
| `npm run build` | Passed: Vite production asset build. |
| `flutter test --no-pub --reporter expanded` | Passed: 120 tests. This reuses existing resolved packages. |
| `flutter analyze --no-pub` | Passed after fixes: no issues found. |
| `php artisan schedule:list` | Blocked: configured MySQL at `127.0.0.1:3306` refused connection. |

## Release checklist (must be signed by release owner)

- [ ] Review/approve release commit and independent security review.
- [ ] Confirm production environment secrets, HTTPS, secure cookies, trusted proxies, CORS, debug disabled, and non-executable upload storage.
- [ ] Clean dependency install and locked dependency/security review.
- [ ] Apply migrations to a clean supported MySQL database and a restored upgrade copy; reconcile data.
- [ ] Verify scheduled tasks, durable queue workers, retry/backoff and failed-job alerting.
- [ ] Prove database and file backup restoration; record recovery point/time and rollback procedure.
- [ ] Verify API auth and every critical participant endpoint against the shipped Flutter client.
- [ ] Complete UAT with all listed roles, including offline/reconnect and private-file authorization scenarios.
- [ ] Verify email, PWA push and FCM delivery with approved non-production credentials.
- [ ] Pass clean Flutter analysis/tests and build signed Android artifact; test on supported physical devices.
- [ ] Build/sign iOS on macOS/Xcode, verify APNs/capabilities/privacy and TestFlight acceptance if iOS is in scope.
- [ ] Confirm dashboards/alerts, support ownership, incident escalation, and rollback rehearsal.
- [ ] Obtain authorized stakeholder acceptance and record outstanding issues.

## Deployment, backup, monitoring and rollback notes

Follow `docs/INSTALL.md` and `docs/` operational guides only after review against the target environment. Do not run migrations, seeders, cache clears, or service restarts against production without an approved change window and a verified backup. Keep the previous application artifact available; deploy code and migrations using the organization’s tested expand/contract procedure. If health checks or smoke tests fail, stop traffic to the new release and restore the previous artifact; reverse database changes only when a reviewed rollback is safe. Monitor application errors, queue failures, scheduler heartbeat, API latency, upload rejection, sync failures, notification errors, disk capacity, and backup completion without logging credentials or tokens.

This repository assessment does not demonstrate that a backup was restored or that rollback was executed.
