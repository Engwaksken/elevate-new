# ElevateHer360: Developer System Report and Role-Based User Manual

**Report date:** 12 October 2026

**Organisation shown in the platform:** Women in Technology Uganda

**Repository baseline:** `ffa36b9` on `main`
**Platforms:** Laravel web application, participant PWA, and Flutter participant application  
**Flutter application version:** `1.1.0+2`  
**Audience:** project owners, developers, system administrators, programme teams, and end users

## Contents

1. Executive summary and reporting boundaries
2. Architecture and developer handover
3. Development achievements by phase
4. Platform capability and readiness matrix
5. Verification and quality assurance
6. Challenges, limitations, and outstanding risks
7. Recommendations and proposed next phases
8. Deployment and operational procedures
9. Participant user manual
10. Staff user manual
11. Mentor user manual
12. Employer user manual
13. Troubleshooting and support
14. Acceptance criteria and evidence register

---

## 1. Executive summary and reporting boundaries

### 1.1 Purpose and achievement

ElevateHer360 has developed into an integrated programme-delivery and organisational-management system. Laravel provides the public website, role-specific portals, administration, business rules, data storage, file delivery, reporting, and participant APIs. Flutter provides an offline-first participant experience for learning, progress, assignments, mentorship information, appointments, career documents, notifications, and support.

The platform connects a participant's journey from registration and assessment through learning, mentorship, career preparation, and opportunities. It also supports the staff operations behind that journey: programme structures, course administration, attendance, reporting, human resources, staff performance, procurement, assets, and support-ticket handling.

Recent development has strengthened role separation, participant identity, multi-branch delivery, notification preferences, admissions workflows, file viewing, mobile offline content, and responsive navigation. Recent committed changes include the PWA branding work, feedback-modal validation recovery, and clearer workplan attachment guidance. The dynamic manifest is not a prerequisite for service-worker installation.

### 1.2 What this report does and does not establish

- **Implemented** means that the capability is supported by current repository code and, where indicated, tests.
- **Verified locally** means a stated check completed in the development environment. It does not mean the production configuration was inspected.
- **Configuration-dependent** means the code requires working services, settings, permissions, or credentials.
- **Not verified** means no current deployment, physical-device, store-release, or acceptance evidence was obtained for this report.
- Recommendations are proposed work, not completed features or approved commitments.

This is a repository-based report, not a production audit, penetration test, financial assessment, or formal customer acceptance certificate. No adoption statistics, uptime measurements, cost savings, production transaction counts, or delivery budgets are asserted.

### 1.3 Phase interpretation

The reporting phases below organise the observed development sequence into a readable account. They are **not a reconstruction of every original package phase number**. The repository includes older Phase 1–11 integration notes and a Phase 25/26 hardening commit, but those labels are not sufficient to certify every historical milestone or its original acceptance scope.

Git history reviewed spans 22 September–11 October 2026. Dates indicate recorded commit activity, not necessarily when implementation began, when users received it, or when production deployment occurred.

### 1.5 Current repository verification (12 October 2026)

The current branch is `main` at `ffa36b9`; the worktree was clean during this assessment. The full backend suite, web asset build, Flutter tests, and Flutter static analysis passed locally. The locked Composer audit found no advisories. The npm production-dependency audit found no vulnerabilities, but the full npm audit reports three vulnerabilities in development dependency paths (one high and two critical), including `shell-quote` and `source-map-js`; resolve and re-audit before release. These are repository-level checks only: no live production configuration, hosting infrastructure, stakeholder UAT, device/store release, or operational drills were inspected.

### 1.4 Overall readiness conclusion

The codebase has substantial implemented functionality and passing local regression suites. The branding extension handling, favicon-format alignment, and Flutter analyzer cleanup described below are present in the repository history. The 12 October checks are recorded in Section 5. These results do **not** establish full production readiness. Public upload-host execution configuration, production MySQL migration/scheduler checks, push/email delivery, role-based UAT, backup restoration, and Android/iOS builds, device and store verification remain unproven. The latest Flutter checks used the current local dependency resolution and do not establish a clean-machine release build. iOS source configuration must not be described as a completed App Store release.

---

## 2. Architecture and developer handover

### 2.1 System structure

```text
Public website / participant, staff, mentor and employer web portals
                         |
                Laravel 12 application
      routes -> middleware/controllers -> domain services
                         |
          Eloquent models, database and file storage
                         |
    mail / scheduler / queued parsing / AI provider / FCM
                         ^
                         |
       Sanctum participant API: /api/v1/participant
                         |
       Flutter participant app: Dio + secure token storage
                         |
       SQLite cache / offline operations / private app files

Participant web PWA: service worker + browser caches + offline sync
```

The Laravel server remains authoritative for permissions, enrolment, publication, deadlines, approval decisions, and ownership. A cached mobile screen or queued operation is not proof that the server has accepted an action.

### 2.2 Technology inventory

| Area | Repository evidence | Developer implication |
| --- | --- | --- |
| Backend | Laravel `^12.0`, PHP `^8.2` in `composer.json` | Preserve framework conventions and validate dependency changes against the lockfile. |
| Web UI | Blade templates, reusable components, CSS/JavaScript, Vite build configuration | Shared layouts and role shells should be preferred over duplicated markup. |
| Identity and API | Sanctum, custom role/permission middleware, participant checks | Web sessions and mobile bearer tokens have different transport requirements. |
| Persistence | Eloquent models and ordered migrations | Test both clean installation and upgrade migration paths. |
| Production database plan | MySQL named in deployment guidance | This report does not inspect production database credentials or configuration. |
| Document processing | Dompdf, PhpSpreadsheet, PHPWord, PDF parser | Preview conversion and parsing have dependency, resource, and fidelity considerations. |
| Flutter | Dart SDK constraint `>=3.3.0 <4.0.0`; local Flutter 3.44.9 / Dart 3.12.2 | Pin supported build tooling in CI rather than assuming every SDK satisfying the broad constraint is validated. |
| Mobile networking | Dio | Keep error mapping and API-host credential restrictions centralised. |
| Offline persistence | SQLite, shared preferences, app-private files | Review freshness, retention, storage size, and rejected-operation recovery. |
| Mobile security | Secure token storage and device-local biometric authentication | Biometrics unlock an existing device session; they do not replace server authentication. |
| Notifications | Firebase Messaging and local notifications | Device permissions, Firebase configuration, scheduler operation, and platform signing affect delivery. |
| Mobile files | File picker, PDF printing/viewing, media playback, sharing | Native plugin behavior requires device testing, not only widget tests. |

### 2.3 Important code locations

| Path | Responsibility |
| --- | --- |
| `bootstrap/app.php` | Canonical route loading, middleware aliases, security headers, stateful Sanctum API integration, JSON exception handling. |
| `routes/web.php` | Public, authentication, administration, learning, mentorship, employer, career, and operational web routes. |
| `routes/workspaces.php` | Workspace, import, performance, task, KPI, purchase-request, and staff-support routes. |
| `routes/api.php` | Current participant and other versioned API routes. |
| `routes/console.php` | Actual scheduled reminders, daily IT review, and preview cleanup. |
| `app/Services/` | Domain rules including admissions, learning, appointments, HR, career, reporting, notifications, and AI. |
| `app/Services/Push/` | FCM client and server push delivery. |
| `database/seeders/RolePermissionSeeder.php` | System role and permission catalogue and selected initial grants. |
| `app/Support/RoleShell.php` | Participant, mentor, and employer landing/navigation definitions. |
| `resources/views/` | Page templates, shared layouts, role components, and branding partials. |
| `public/sw.js`, `public/pwa.js` | PWA caching, offline integration, update behavior, and push-event handling. |
| `participant_app/lib/core/` | Configuration, parsers, formatting, theme, reading-time logic, and error types. |
| `participant_app/lib/services/` | API, authentication side effects, sync, cache, downloads, biometrics, and notifications. |
| `participant_app/lib/screens/`, `lib/widgets/` | Participant screens and shared presentation. |
| `tests/`, `participant_app/test/` | Backend regression, worker behavior, and Flutter unit/widget tests. |

Legacy route files and package integration notes should not be treated as independent installation instructions without checking canonical route loading and current code.

### 2.4 Participant integration contract

The default Flutter API base is `https://site.elevateher360.org/api/v1/participant`. It can be changed at build time with `API_BASE_URL`; website, privacy-policy, terms, and support values also have build-time overrides in `participant_app/lib/core/app_config.dart`.

The participant API supports authentication, dashboard data, courses/lessons, assignments, extensions, mentorship information and attendance, appointments, personal goals, profile/password/photo operations, progress, jobs, events, announcements, notifications, career documents/uploads/AI, certificates, sync, and device registration.

Key integration rules:

1. Use Sanctum bearer tokens for the Flutter app. The API enforces active participant identity through `EnsureParticipantApi`.
2. The app uses email-verification information to gate its user flow. Do not assume the active-participant middleware alone establishes server-side email verification for every API action; verify sensitive endpoint requirements separately.
3. Same-origin PWA calls can use the Laravel session through the stateful Sanctum integration.
4. Consume authenticated lesson, attachment, and profile-photo endpoints; do not invent public `/storage/` URLs for private files.
5. Send readable JSON errors to the app. Current bootstrap configuration prevents API failures from turning into HTML login pages.
6. Preserve existing named-resource and paginator response shapes. This API does not have one universal response wrapper.
7. Use stable client operation/submission identifiers when retrying writes. A lost response must not cause duplicated learning progress or submissions.
8. Treat server validation as authoritative, even if client validation has already succeeded.

### 2.5 Offline and notification design

Flutter stores collection data locally, queues supported writes, and synchronises when connectivity returns. Submission files and mentorship attendance use their specific endpoints; other supported operations are batched through `/offline-actions`. Concurrent sync/flush calls share a running operation to reduce duplicate sends.

Wi-Fi/ethernet content caching attempts to prepare course reading files, assignment attachments, certificates, and generated career documents. Cache availability depends on prior sync, eligibility, successful downloads, file type, and local storage. Content is not guaranteed to be current indefinitely; some files are skipped once their local cache key already exists.

Mobile push delivery is implemented through `app/Services/Push/PushNotificationService.php`. It defers sending until the HTTP response terminates, sends to registered devices, and removes invalid tokens. This is **not a durable queued-delivery guarantee**: production reliability and retries must be evaluated separately. Local reminder scheduling is an additional mechanism, not evidence of successful remote push delivery.

The PWA worker now refreshes the dynamic manifest at runtime, refreshes branding assets without HTTP-cache pinning, and uses a trusted same-origin uploaded-logo endpoint for push icon and badge. A three-second branding deadline retains bundled fallback behavior when a fetch or JSON read stalls. The presence of a push handler does not, by itself, prove end-to-end browser subscription and server delivery in production.

---

## 3. Development achievements by phase

### Reporting Phase 1: Foundation and initial integration

**Observed period:** 22 September 2026.

**Laravel achievements:** Initial Laravel 12 application, integrated module foundation, database dependency corrections, public landing page, maroon/gold styling, navigation improvements, registration flow, consent model, and participant navigation/tab restructuring.

**Flutter position:** No separate completed mobile foundation is claimed for this phase from the reviewed early history.

**Outcome:** A common foundation for identities, programme structures, public access, and future portal modules.

**Challenges and follow-up:** Modular migrations required dependency ordering. Historical package-copy instructions are now insufficient for a reliable clean installation; maintain an integrated installation/upgrade runbook.

**Evidence:** `cd322d6`, `3c6ab02`, `dbf77ea`, `f552557`; `docs/INSTALL.md` and migration guidance.

### Reporting Phase 2: Administration, access control, and instructor workspaces

**Observed period:** 23–28 September 2026, with later refinements.

**Laravel achievements:** Administration restructuring, hardening checkpoint, role-aware dashboard/sidebar access, participant authentication fixes, profile/branding corrections, assigned-course instructor workspaces, modal course-content management, submissions, announcements, and participant API foundation.

**Flutter achievements:** Initial participant-app test correction and mobile integration groundwork around the API foundation.

**Outcome:** Improved separation of ordinary participant access, staff administration, and instructor responsibility for assigned courses.

**Challenges and follow-up:** Historical role aliases, duplicated route loading, and dashboard fallbacks caused access/navigation inconsistencies. Test permissions at controller/service level as well as menu visibility. A hidden link is not an authorisation boundary.

**Evidence:** `fa1bad1`, `bdf9166`, `752dc27`, `1a5a5f6`, `8b0f69a`; middleware, `RoleShell`, and instructor tests.

### Reporting Phase 3: Participant mobile learning and offline foundation

**Observed period:** 28–29 September 2026.

**Laravel achievements:** Expanded participant sync services, consolidated web/API routing, lesson/progress integration, offline operation processing, and device-registration foundations.

**Flutter achievements:** Participant learning workflows, local persistence, offline operations, download support, production notification/release preparation, and Android Firebase/signing configuration.

**Outcome:** A participant app that can display previously cached data and synchronise supported changes after reconnection.

**Challenges and follow-up:** Server/client response mismatches and duplicated loading paths required consolidation. Connectivity detection does not guarantee internet reachability; retain retries and explicit server-acceptance states.

**Evidence:** `2a9cb3d`, `cae7d2c`, `9b8e23b`, `5605918`, `b45cfbf`; Flutter API/database/sync services.

### Reporting Phase 4: PWA, lesson delivery, profile, and progress

**Observed period:** 30 September 2026.

**Laravel achievements:** Participant PWA, authenticated lesson/file API corrections, profile/progress endpoints, photo handling, assignment-extension rules, public privacy/terms pages, and instructor course-management improvements.

**Flutter achievements:** Lesson viewer, friendly error handling, redesigned navigation, profile and progress screens, reading-time tracking, support contact integration, branded app assets, and an iOS platform directory.

**Outcome:** A more complete participant experience across browser and mobile, including progress visibility and structured late-work handling.

**Challenges and follow-up:** Lesson downloads previously returned 404; private delivery needed explicit enrolment/publication/lock checks. iOS configuration was added from Windows and still requires a Mac/Xcode release-validation path.

**Evidence:** `7d1f286`, `d830743`, `d2039f3`, `36b23e1`, `ef7827d`, `8ee894c`; participant API tests.

### Reporting Phase 5: Staff performance, administration usability, and reporting

**Observed period:** 30 September–1 October 2026.

**Laravel achievements:** Appraisal workflow expansion, return-for-revision, HR lock/reopen, behavioural competencies, staff workspaces, import/tracking reports, file previews, accessible long-form tabs, audit permission diffs, broader event notifications, certificate recommendations/templates, participant IDs, multi-branch instructors, staff daily/weekly tasks, KPI-linked evidence, and contract KPI plans.

**Flutter achievements:** Participant progress/assignment interface integration with the developing backend. No Flutter HR portal is claimed.

**Outcome:** Better staff productivity and accountability, with a link between daily tasks, contract objectives, and quarterly performance records.

**Challenges and follow-up:** Permission-sensitive workspaces, legacy page dependencies, CSV templates, and multi-step appraisal state transitions needed regression protection. Avoid bypassing HR closure controls when correcting records.

**Evidence:** `5236bd6`, `f7de37e`, `259c123`, `1f926b3`, `1f4de64`, `d05ccd2`, `c297b1f`; appraisal/task/KPI tests.

### Reporting Phase 6: Career documents, AI, timetables, and content management

**Observed period:** 2–3 October 2026.

**Laravel achievements:** Career-document sharing, resumes with projects/referees/portfolio files, private upload parsing, configurable system AI and provider testing, timetable calendars/reminders, participant certificates, CMS section/grid building, programme targets, workplan approvals, goals, AI career-mentor guidance, and mentor goal review.

**Flutter achievements:** Career upload/review/import, resume and cover-letter editing, reviewable AI drafts, career-document dashboard integration, personal goals, and AI mentorship screens.

**Outcome:** Learning progression now connects to career preparation and goal-oriented mentorship, while staff can manage public content and programme planning.

**Challenges and follow-up:** AI can be unavailable or produce unsuitable text. Proposed content must remain reviewable and explicitly saved. Private files and expiring share links require ownership and nested-resource checks.

**Evidence:** `a1090cd`, `aa24e0c`, `b3626a4`, `4f56f34`, `351795e`, `a3715d7`; career/AI/sharing tests.

### Reporting Phase 7: Admissions, partner portals, and participant identity

**Observed period:** 3–4 October 2026.

**Laravel achievements:** Email verification, required registration phone/consent, self-enrolment entry-assessment checks, pass-marked/scored survey admissions, assessor assignment, compulsory-course handling, dedicated participants administration, partner authentication/approval, employer recruitment statuses and notifications, mentor selection/recommendations, three-month match windows, notification preferences, and participant-ID programme-prefix resynchronisation.

**Flutter achievements:** Verification gating and use of admissions/identity-aware participant data. Web-only partner/admission actions must not be assumed to have an equivalent native app flow.

**Outcome:** Clearer admission rules, partner account separation, and participant identity across programme delivery.

**Challenges and follow-up:** Platform-level and course-specific entry requirements need clear messaging. Returning-participant matching must stage ambiguous identities rather than silently merge accounts. IDs described as permanent in older tests can still have programme-derived prefixes resynchronised; governance should document stable database identity separately from displayed codes.

**Evidence:** `95e0d87`, `fd2b3db`, `8dfe874`, `54bb099`, `792cf9f`, `a2d4fdd`; admission, partner, and identity tests.

### Reporting Phase 8: Support, operational controls, files, and appointments

**Observed period:** 5–6 October 2026.

**Laravel achievements:** Participant/staff support tickets and IT queue, learning reports, role-specific shells, scoped CSV/PDF exports, bulk administration actions, self-service purchase requests and leave, restricted leave approvals, procurement access controls, departments/funding sources, multi-file learning/assignment handling, view-only file policies, instructor appointments, and server FCM sending.

**Flutter achievements:** Participant IT support submission/list/detail, view-only and multi-file learning interfaces, instructor appointments, attachment/submission integration, and faster sign-in through asynchronous initial sync/device registration.

**Outcome:** More complete operational support and direct participant–instructor contact, with less sign-in blocking and better role-specific access.

**Challenges and follow-up:** Bulk deletion must be controlled and auditable. View-only access is a policy/UI restriction, not an absolute guarantee against copying. Permission updates must not give ordinary staff HR, procurement, or IT queue authority.

**Evidence:** `28277f2`, `24ac6b4`, `9b51b78`, `a9580bb`, `a48e1c1`, `617c5c4`, `6a13717`, `9e15087`.

### Reporting Phase 9: Mobile reading continuity and account administration

**Observed period:** 7 October 2026.

**Laravel achievements:** Word-file preview support, consistent branding on errors, multi-branch cohorts, staff onboarding emails/verification, custom roles/permissions, targeted notifications, and canonical verification redirects.

**Flutter achievements:** Wi-Fi reading-content caching, offline certificates/career PDFs, Word-reader integration, and optional biometric unlock with career previews.

**Outcome:** Improved continuity when connectivity is limited and more targeted administration of user accounts and communications.

**Challenges and follow-up:** Same-key cached files can become stale, device storage is finite, and biometric behavior requires real-device validation. Staff onboarding communication should be reviewed against current credential-distribution policy.

**Evidence:** `70e937e`, `4f88d5f`, `5bb9ec8`, `e067971`, `f621878`, `620062f`.

### Reporting Phase 10: Application imports, navigation, and PWA branding completion

**Observed period:** 11 October 2026.

**Laravel/web achievements:** Application-call templates, bulk applicant/import-source guidance, additional import and staff-notification preferences, mentor/employer user-type administration, collapsible/readable sidebars, calendar/agenda tabs, small-screen header corrections, and uploaded PWA site branding.

**PWA branding details:** Dynamic `/app-manifest.webmanifest`; validated raster asset delivery with bundled fallbacks; content-versioned manifest logo URLs; no dynamic manifest in the install-critical shell; runtime freshness; worker version `eh360-v1-20261011-2`; same-origin exact-path push-logo selection for icon and badge; bounded branding fetch; and safe escaped, nonrecursive HTML icon fallback handlers.

**Flutter position:** The uploaded Laravel site logo is not automatically the native launcher icon. Flutter retains bundled branding and generated platform icons; changing native assets requires a new mobile build.

**Outcome:** More usable administration and safer participant-web branding continuity, with independent review and expanded regression coverage.

**11 October 2026 baseline:** ICO favicons conflicted with the raster-only delivery detector; public branding uploads retained the client filename extension, creating a deployment-dependent execution risk; and Flutter analysis had five informational lint findings. These were open findings at the repository baseline `5e1e053` and are preserved here as historical evidence, not represented as though they were already fixed.

**Follow-up now present in repository history:** Branding upload storage derives its extension from decoded image content rather than the client filename; favicon validation no longer accepts ICO until safe ICO delivery is supported; and all five Flutter lint findings have been corrected. Earlier regression/analyzer results are recorded in Section 5. These code changes do not prove public-host execution configuration is safe or establish production readiness.

**Evidence:** `5bdf792`, `f3957ea`, `6b59350`, `072b4a6`, `5e1e053`; `BrandManifestTest` and executable worker tests.

---

## 4. Platform capability and readiness matrix

| Capability | Laravel web/server | Flutter participant app | Readiness qualification |
| --- | --- | --- | --- |
| Registration and role login | Participant and partner entry; staff login; verification and approvals | Participant login/verification | Native registration is not claimed; use the website where needed. |
| Learning and progress | Course structures, publication, access, submissions, grading, progress | Learning tree, lesson reader, progress and reading time | Publication, enrolment and module release rules apply. |
| Assignments/extensions | Multi-file submission, deadline/attempt rules, instructor decisions | Submission/queue and extension interface | Queued work is provisional until accepted by the server. |
| Certificates | Recommendations, templates, course/event issue and share | List, preview/download/share and cached copies | Issuance and availability depend on programme decisions and permissions. |
| Mentorship/goals | Matching, profiles, sessions, reports and goal review | Session information, attendance, goals and AI guidance | Do not claim full native matching/reporting parity. |
| Instructor appointments | Booking, conflicts, approval/proposal/cancel/completion | Participant booking/detail/actions | Eligible course instructors only; appointment limits apply. |
| Careers and AI | Resume/letter builders, private parsing, portfolio, sharing and AI | Document CRUD, uploads/review/import, AI draft application | Provider configuration and human review required. |
| Jobs/employers | Posting approval, applications/status pipeline and partner ownership | Job browsing/saving; opens website to apply | Employer operations are web-based. |
| Events/calendar | Public events, registration, attendance, reports and calendar | Event listings and timetable integration | Native feature parity is not assumed. |
| Support | Participant/staff submissions and authorised IT management | Participant ticket workflows and contact links | An ordinary requester cannot access the support-team queue. |
| HR and performance | Leave, employee records, appraisals, contract KPIs and tasks | No staff module | Role-sensitive web workflows. |
| Programme planning/MEAL | Programme/project/cohort structures, workplans, indicators and reports | Participant-facing data only | Operational data reconciliation and UAT required. |
| Procurement/assets | Purchase requests, approval/receiving and asset-related workflows | No module | Restricted organisational web operations. |
| CMS/imports/admin | Content blocks, data imports, role management and communications | No module | Test import templates and permissions before bulk changes. |
| Notifications | In-app/email mechanisms, configured FCM sending, PWA push handler | FCM registration, local reminders and notification screens | Delivery needs real credentials, permissions and devices. |
| Offline support | Participant PWA shell/cache and sync | SQLite, queued writes and eligible cached files | Not universal offline functionality; freshness and acknowledgement matter. |
| Android distribution | API/service support | Android release-signing/Firebase configuration | No fresh release build or store-publication verification performed for this report. |
| iOS distribution | API/service support | iOS source platform present | Mac/Xcode, signing, capabilities, Firebase/APNs and device verification remain release gates. |

---

## 5. Verification and quality assurance

### 5.1 Recorded local results

| Check | Result | Evidence timing and limitation |
| --- | --- | --- |
| `php artisan test --compact` | **538 passed; 3,375 assertions** | Current follow-up verification. Not a production/database-load test. |
| Branding regression suite (`BrandManifestTest`) | **15 passed; 181 assertions** | Current follow-up verification, including detected-extension behavior and branding/manifest regressions. |
| `node --test tests/js/sw-branding.test.cjs` | **6 passed** | Current follow-up verification; covers install independence, trusted/unsafe icons, failures, stalled fetch/body, and runtime branding freshness. |
| `npm run build` | **Passed** | Current follow-up verification; local asset build only. |
| `flutter test --no-pub --reporter expanded` | **120 passed** | Current follow-up verification on Flutter 3.44.9 / Dart 3.12.2. Unit/widget tests, not physical-device UAT. `--no-pub` uses existing resolved dependencies; fresh dependency resolution was not validated. |
| `flutter analyze --no-pub` | **Clean; no findings** | Current follow-up verification; all five baseline informational findings were corrected. Uses existing dependencies (`--no-pub`), so fresh dependency resolution was not validated. |
| Plain `flutter test --reporter expanded` | **Blocked before test completion** | Generated iOS `.packages` deletion failure; successful retry used existing dependencies with `--no-pub`. |
| Initial `flutter analyze` | **Timed out after 120 seconds** | Subsequent `--no-pub` analysis completed and is the reported result. |
| Independent PWA code review | Completed | Cache/timeout/test gaps corrected; ICO compatibility still disclosed. |
| Independent PWA security review | No new actionable vulnerabilities found in reviewed change | Narrow change review, not certification of the entire platform; preexisting upload risk identified. |

### 5.1.1 Additional checks completed on 12 October 2026

| Check | Result | Limitation |
| --- | --- | --- |
| `php artisan test` | **551 passed; 3,457 assertions** | Local test environment only; does not exercise production MySQL or load. |
| `npm run build` | **Passed; 74 modules transformed** | Local Vite production asset build, not a deployment smoke test. |
| `flutter test` | **All 120 tests passed** | Unit/widget suite; no physical-device or store validation. |
| `flutter analyze` | **No issues found** | Static analysis only. |
| `composer audit --locked` | **No security vulnerability advisories found** | Dependency advisories only; not an application security assessment. |
| `npm audit --omit=dev --audit-level=high` | **0 production dependency vulnerabilities** | Does not clear development-tooling advisories. |
| `npm audit --audit-level=high` | **Failed: 3 vulnerabilities (1 high, 2 critical)** | Development dependency paths include `concurrently` → `shell-quote` and `source-map-js`. Review the lockfile update with `npm audit fix`, then rerun build and audit. |

### 5.2 Informational Flutter findings

- `participant_app/lib/screens/profile_screen.dart:556`: braces around an `if` statement.
- `participant_app/lib/services/download_service.dart:194`: braces around an `if` statement.
- `participant_app/lib/services/participant_data_service.dart:175`: braces around an `if` statement.
- `participant_app/lib/services/sync_service.dart:405`: braces around an `if` statement.
- `participant_app/test/biometric_gate_test.dart:12`: widget `child` argument should be last.

At the 11 October baseline these were style findings, not observed runtime failures. All five have since been corrected in committed code; the current `flutter analyze` result is clean. This does not validate device builds.

### 5.3 Coverage strengths and remaining proof

The backend suite includes role boundaries, partner separation, admissions, identity, learning-file policy, appointments, support tickets, appraisals, KPI/task behavior, exports, certificates, career uploads/AI, private sharing, notifications, and participant API behavior. Flutter tests cover parsing, appointment requests, careers, goals, file-policy widgets, support, reading-time acknowledgement, assignment deadlines, accessibility layouts, and friendly errors.

Passing tests do not establish production mail delivery, FCM/APNs delivery, high-volume performance, backup restoration, browser install compatibility, native plugin correctness on every device, or absence of security vulnerabilities. Obtain those results separately and attach the evidence to the release record.

---

## 6. Challenges, limitations, and outstanding risks

| Challenge or risk | Observed evidence / status | Impact and required response |
| --- | --- | --- |
| Migration dependency order | Historical migration fixes; deployment notes describe module integration | Test empty-database install and upgrade on a restored staging copy; never blindly apply old module packages. |
| Role and route inconsistency | Repeated historical access/dashboard/route consolidation fixes | Maintain permission tests and one canonical route-loading strategy. |
| Backend/mobile contract drift | Old API documentation says no FCM sender, while current server code implements one | Reconcile the API contract with current routes/services and test request/response compatibility. |
| Stale release documentation | iOS checklist still describes default icons despite later branded-asset work | Update the checklist using current generated assets and actual device/build evidence. |
| Windows Android build paths | `android/gradle.properties` documents C:/D: Kotlin cache-root problems | Existing incremental-compilation disablement is a workaround; validate reproducibility and build-time trade-offs in CI. |
| Android plugin requirements | Desugaring and Java 17 configured | Preserve compatible Gradle/SDK/plugin versions; verify signed APK/AAB builds after upgrades. |
| Generated iOS tooling access | Exact test output: `Unable to delete file or directory at "D:\projects\elevate_her\participant_app\ios\Flutter\ephemeral\Packages\.packages". This may be due to the project being in a read-only volume. Consider relocating the project and trying again.` | Diagnose permissions/locks and platform generation; `--no-pub` validates existing dependencies, not a fresh dependency/bootstrap path. No broad cache deletion was performed. |
| Incomplete iOS release proof | Platform folder/checklist present; no current Mac/Xcode build evidence collected | Validate signing, entitlements, APNs, purpose strings, privacy disclosures, icons, TestFlight and store review. |
| Offline rejected writes | Permanent server validation failures can remove queued operations; overdue submissions are marked | Provide clear recovery instructions and consider retaining a user-readable failed-operation history. |
| Offline file freshness | Automatic cache skips an existing local key; cached reading depends on prior access/sync | Add revision-aware invalidation, storage limits and revocation/retention policies. |
| View-only copying limits | Android secure-screen flag exists; iOS cannot block screenshots through this service | Communicate limits honestly; private caching/UI restrictions are not DRM and cannot prevent external-camera capture. |
| ICO favicon compatibility | **Baseline:** settings accepted ICO while delivery detection supported raster formats. **Current follow-up:** ICO is rejected by validation until safe delivery is supported. | Current validation/delivery formats are aligned; safe ICO handling remains future work. |
| Public upload executable extension | **Baseline:** `PlatformSettingsController` retained the client extension. **Current follow-up:** the stored suffix is derived from decoded image bytes; branding regression coverage verifies a misleading `.jpg` client name is stored as `.png`. | This reduces the identified extension risk in application code; it does not prove the public upload host disables script execution or establish absence of other upload risks. No exploit or production exposure was verified. |
| Push delivery durability | FCM sending uses application termination callbacks rather than a durable queue | Add queued retries, delivery/error metrics and rate-aware processing before relying on high-volume notification guarantees. |
| Local data sensitivity | Participant data cached in SQLite/private files; tokens use secure storage | App-private is not synonymous with encrypted. Review device backups, encryption, retention and shared-device policy. |
| AI accuracy/privacy | Provider-dependent career suggestions and contextual mentorship | Require review, minimise transmitted sensitive data, define consent/retention, and avoid invented qualifications or employment guarantees. |
| Administrative destructive actions | Bulk deletion/import capabilities implemented | Enforce permissions, preview/reconcile inputs, audit actions and test restoration. |
| Tool delegation limitations | Prior PWA implementation agents returned `Permission denied: shell` and `Permission denied: edit` | The remaining patch was completed by the parent session. Verify agent grants before future delegation; these are development-tool denials, not end-user application permissions. |

---

## 7. Recommendations and proposed next phases

These are proposed priorities. Owners, dates, budgets and scope require project approval.

### Proposed Phase A: Release blockers and security alignment

**Suggested owners:** technical lead, backend developer, system administrator, security reviewer.

1. Verify public-host upload execution configuration and prohibit executable content in public storage; expand spoofed-content/polyglot tests as appropriate. The application now derives stored extensions from decoded image bytes.
2. Retain the aligned raster-only favicon validation/delivery behavior; implement safe ICO handling only with suitable tests.
3. Verify least-privilege role grants across participant, ordinary staff, instructor, HR, procurement, IT, mentor and employer accounts.
4. Validate server-side verification/admissions requirements for sensitive APIs, not only client navigation.
5. Inspect HTTPS, secure cookies, debug mode, headers, CORS, trusted proxies, secrets, private-file paths and backup access in staging/production.
6. Require a successful backup restoration and incident escalation exercise before go-live approval.

**Exit criteria:** fixes reviewed; security/role regression tests pass; configuration checklist and restore evidence signed off.

### Proposed Phase B: Documentation and reproducible quality gates

**Suggested owners:** Laravel/Flutter developers and QA lead.

1. Preserve the clean Flutter analyzer result as a CI gate; the five baseline lint findings are corrected in committed code.
2. Replace the generic Flutter README with actual installation, environment, signing, API and test instructions.
3. Update the API contract, scheduler guidance and iOS checklist; clearly archive historical package instructions.
4. Add CI jobs for PHP tests, worker tests, Flutter analysis/tests, dependency checks and clean migration installation.
5. Add production-like MySQL tests where database behavior differs from the test environment.
6. Add browser/device integration tests for login/verification, role redirects, offline recovery, private previews and notifications.

**Exit criteria:** documented clean checkout is reproducible; CI passes; contract and runtime code agree.

### Proposed Phase C: Offline reliability, performance and observability

**Suggested owners:** mobile/backend developers and operations.

1. Add content revision hashes/ETags or version metadata to invalidate stale files safely.
2. Provide download quotas, cache-size visibility and selective removal without losing unsynchronised work.
3. Preserve an actionable failed-sync history, with clear status for queued, sent, acknowledged and rejected submissions.
4. Move important remote notification delivery to durable jobs with retries and appropriate idempotency.
5. Monitor failed jobs, sync failures, API latency, document-conversion resource use, upload rejection and push failures without logging secrets.
6. Load-test large imports, reporting/export, notification fan-out, document conversion and initial sync.

**Exit criteria:** agreed performance targets met; stale/rejected-work scenarios tested; alerts and recovery playbooks operational.

### Proposed Phase D: Mobile release and role-based adoption

**Suggested owners:** mobile release engineer, QA, programme owners and support.

1. Produce signed Android release artifacts from controlled configuration and test on representative devices.
2. Complete Mac/Xcode iOS builds, APNs/Firebase configuration, capabilities, privacy review and TestFlight validation.
3. Verify store disclosures and account-deletion request/process requirements against actual implementation.
4. Run UAT using one account per role and realistic programme data; include poor connectivity and accessibility scenarios.
5. Train staff and partners with the manuals below, publish approved support contacts, and collect structured feedback.
6. Decide native parity priorities from user needs rather than adding staff/partner modules to Flutter by assumption.

**Exit criteria:** signed releases/device tests and role UAT approved; operational ownership and support materials published.

---

## 8. Deployment and operational procedures

### 8.1 Safe release preparation

1. Confirm the release commit and approved change list. This report does not authorise production changes.
2. Back up the database and files; prove restoration on an isolated environment.
3. Prepare a staging copy with production-like services and nonproduction credentials.
4. Install locked PHP/JavaScript dependencies; build frontend assets with the project's supported tooling.
5. Review migration order and pending migrations before execution. Reconcile existing user/programme/learning data.
6. Provision SMTP, queue processing, scheduler, filesystem permissions, AI and push configuration only where approved.
7. Verify role seeds/grants without creating predictable production passwords or running old development seeds indiscriminately.
8. Run the test/UAT gates and capture outcomes before deployment approval.

Useful developer checks, run from the repository root in a prepared environment:

```powershell
php artisan migrate --pretend
php artisan route:list
php artisan schedule:list
php artisan test --compact
node --test tests/js/sw-branding.test.cjs
npm run build
```

Migration execution, seeding, cache rebuilding and service restarts are deployment actions and should follow the approved runbook, not be copied blindly into a live server.

### 8.2 Scheduler and queue operation

The current `routes/console.php` schedules:

- `events:send-reminders` every five minutes.
- `timetable:send-reminders` every minute.
- Daily IT queue review generation at midnight in `Africa/Kampala`.
- Daily pruning of generated preview PDFs older than seven days.

Older `docs/SCHEDULER.md` names a different reminder command. Use current code and `schedule:list` as the runtime reference. Configure the host scheduler to invoke Laravel regularly, and supervise any required queue workers. Restart workers appropriately when deploying code; monitor failed jobs and conversion failures.

### 8.3 Flutter development and release

From `participant_app`, a normal reproducible setup should include dependency resolution followed by analysis/tests:

```powershell
flutter pub get
flutter analyze
flutter test
```

The report's successful tests used `--no-pub` because of a generated-file access failure. That workaround does not replace fixing clean setup before release.

Android release builds require a properly managed signing configuration; current Gradle code explicitly fails release builds when `android/key.properties` is absent. Do not commit keystores or signing passwords. Configure Firebase through approved project settings. Use a release build number higher than the last distributed build, and verify the target API environment.

iOS release work must follow an updated `participant_app/docs/IOS_RELEASE_CHECKLIST.md` on a Mac. Validate icons, entitlements, capabilities and provider configuration against current files, not stale notes.

### 8.4 Daily and periodic operations

| Frequency | Suggested checks |
| --- | --- |
| Daily | Application health/errors, failed jobs, scheduler activity, support queue, email/push failures, backup completion and storage pressure. |
| Each release | Migration review, smoke/UAT checks, private-file access, canonical redirects, PWA updates, mobile API compatibility and rollback readiness. |
| Monthly or agreed interval | Role/access review, dependency/security updates, backup restore exercise, audit review, data-retention and expired-share checks. |
| Each programme/cohort cycle | Validate admission rules, pass marks, instructor assignment, participant IDs, timetable, certificates and report reconciliation. |

Define actual response times, recovery-time/recovery-point objectives, and incident owners with the organisation; no approved SLA is inferred here.

---

## 9. Participant user manual

### 9.1 Access and first-time setup

**Web:** use the organisation's approved website and participant sign-in at `/login`; registration is at `/register`. **Mobile:** use the approved ElevateHer360 participant app. Installation/store availability must be confirmed with programme support.

1. Register on the website if you do not already have an account. Supply the requested identity/contact fields, including phone, and accept the required terms/consent.
2. Check your email and open the verification link. Check spam/junk if it does not arrive.
3. Sign in using the participant portal, not staff, mentor or employer login.
4. In the app, follow the verification prompt if your account is unverified; use resend where available.
5. Open **Profile** on the web or **My profile** in the app. Review contact details, interests and other editable fields.
6. Use the account/password controls to change your password. Mobile email, branch, account type and status are administrator-controlled.
7. Allow initial sync to finish. Notification permission is optional, but declining it means reminders/push may not appear.

An account, a course application, and course enrolment are different records. Registering does not automatically grant access to every course.

### 9.2 Find courses and complete admission requirements

1. On the website, use **Browse Courses** or **Course Opportunities**.
2. Read eligibility, closing date and any application instructions.
3. Complete the configured entry assessment/survey if prompted. Where a pass mark is required, an ungraded submission is not the same as a pass.
4. Apply or enrol using the action offered for that course/call.
5. Track the decision in the course/application area or notifications.
6. After enrolment, open **My Learning** on the web or **My learning** in the app.

Course access may depend on approval, published lessons, module release and previous work. Contact the programme team if an expected course is missing; do not create duplicate accounts to bypass the issue.

### 9.3 Study lessons and track progress

1. Select a course, then its available module and lesson.
2. Read the lesson and open its attachments using **View** or the provided download action.
3. Respect view-only files: an absent download/share button is intentional. Some media require an online connection.
4. Complete the lesson action where offered. Reading-time tracking and lesson completion are distinct; leaving a screen open is not a substitute for completing required work.
5. Open **My progress** in the app or the web progress/course area to review completed lessons, time and assignment status.
6. Read instructor feedback and announcements before moving to the next task.

### 9.4 Assignments, quizzes and extensions

1. Open the assessment from your course or **Assignments** in the app.
2. Read instructions, effective due date, attempts remaining and any attached files.
3. Enter your answer and attach the required files; review the complete selection before submitting.
4. Submit and check the confirmation/status. Repeated retries should not be treated as new work unless you intentionally start a new attempt.
5. If offline, supported submissions may queue. Check **Settings → Changes waiting to sync** and reconnect as soon as possible.
6. If a submission is overdue/rejected, review the status and request an extension where offered. Explain the reason clearly; the API requires a reason of 10–1,000 characters.
7. Extensions are available according to server rules, generally for overdue work or work due within 48 hours, while attempts remain and no request is pending.
8. Review the instructor's decision and new due date before submitting again.

**Important:** queued does not mean received or graded. The server may accept a pre-deadline queued submission within a limited 72-hour grace window, subject to validation; this is not permission to submit late intentionally. Do not sign out, uninstall, clear app data, or remove submission files before pending work is synchronised.

### 9.5 Mentorship, goals and attendance

1. On the website, open **Mentorship** and browse/request an eligible mentor when available. Admission assessment requirements may apply.
2. Open the dashboard/session list to see your assigned mentor, meeting time, location/link and agreed actions.
3. Use the **Goals** area/tab to create a goal with a target, date and progress value.
4. Update progress honestly and review your mentor's comments.
5. Attend sessions, then confirm attendance where the action is available. Mobile confirmation is normally available after the session starts and within 14 days, unless cancelled.
6. Use the website's session/report actions where offered; the mobile app does not imply full mentor-management parity.
7. If **Ask the AI career mentor** is available, treat replies as guidance. Do not rely on them as professional advice or share unnecessary sensitive information.

### 9.6 Book an instructor appointment

1. Open **Appointments**.
2. Select an eligible instructor/course, future date/time, duration, topic and meeting details requested by the form.
3. Submit the request and wait for approval or a proposed alternative time.
4. If a new time is proposed, accept or decline it explicitly.
5. Join using the approved meeting link or attend the stated venue.
6. Cancel early if needed. Current rules allow at most three awaiting-response requests and prevent participant cancellation of an approved appointment within two hours of its start.

Instructors are drawn from eligible course relationships; this is not an unrestricted booking directory. Times may display in the device's local timezone, so check the displayed time carefully.

### 9.7 Prepare a resume and cover letter

1. Open **Resume Builder** on the website or **Resumes & cover letters** in the app.
2. Create a document or upload an existing document using the supported formats offered by the form.
3. For an upload, wait for processing, review extracted content and import it only after checking accuracy.
4. Add education, experience, skills and other supported sections such as projects/referees.
5. Use AI improvement/tailoring only if available. Review the proposed draft before applying and saving it; do not add invented qualifications.
6. Preview the generated document, then download or share using the provided action.
7. Treat share links as disclosures to anyone who receives them until expiry; share only with intended recipients.

### 9.8 Jobs, certificates, events and messages

- **Jobs:** browse and save opportunities. In Flutter, **View and apply on the website** opens the website for application; sign in there if prompted. Check required assessments and application documents first.
- **My Applications:** on the web, follow review/interview/offer decisions and read status notifications.
- **My Certificates:** view certificates actually issued to you; preview/download/share where available. Completion alone does not guarantee immediate issuance.
- **Events:** read dates, details and registration instructions; use the website when a required action is not native in the app.
- **Notifications/Announcements:** open relevant items and mark messages read where available. Notification delivery can be delayed; check the app/website directly for important deadlines.
- **Help & Support:** use the configured contacts or participant support-ticket action. Give a clear description and track your own ticket's status.

### 9.9 Offline preparation and biometric unlock

1. While online, preferably on Wi-Fi, sign in and use **Settings → Sync now**.
2. Open the course/resources you need and verify that eligible reading files have been cached or downloaded.
3. Check **Downloads** and try opening required material before disconnecting. Large media or restricted formats may still need internet access.
4. Enable **Wi-Fi only downloads** if you want to limit manual mobile-data downloads; automatic reading caching uses Wi-Fi/ethernet.
5. After reconnecting, sync and confirm the waiting-change count and server statuses. Resolve rejected submissions separately.
6. To enable biometrics, configure fingerprint/face recognition on the device, then open **My profile → Biometric unlock** and confirm.
7. Keep your password and recovery access; biometrics are device-local and not a replacement for account recovery.

Signing out clears participant local database/downloads and attempts to unregister the device. Ensure all important queued work is acknowledged first. Use shared devices only under the organisation's data-protection rules.

---

## 10. Staff user manual

### 10.1 Sign in and understand permissions

1. Use `/admin/login` with your staff account. Staff self-registration is not assumed.
2. Follow the onboarding/verification email and password instructions provided by the organisation.
3. Use the sidebar groups and dashboard relevant to your assigned role.
4. Expand collapsed groups to find available workspaces. Missing modules can reflect permissions rather than a defect.
5. Do not use a colleague's account or request broad administrator rights just to make a menu appear.

Ordinary staff self-service, instructor course access, HR decisions, procurement decisions and IT queue management have different permissions. Staff status alone does not authorise all administration.

### 10.2 Daily tasks, contract KPIs and performance

1. Open **My Tasks** and choose the daily, weekly or past view.
2. Create a task with a date, priority and appropriate KPI link where available.
3. Update progress and record the outcome when complete.
4. Move unfinished work to the next working day using the offered rollover action rather than creating unnecessary duplicates.
5. In the KPI area, prepare contract objectives and submit them for supervisor review.
6. Start/use the applicable quarterly appraisal and import approved KPIs where offered.
7. Complete self-assessment, evidence and behavioural-competency sections, save drafts, and submit when ready.
8. If returned for revision, address the feedback and resubmit. Attend the review meeting and use confirmation actions when required.
9. After HR locks an appraisal, request authorised reopening rather than trying to edit closed records.

Supervisors should review only their authorised teams, document decisions and link feedback to evidence. HR finalisation does not substitute for employee/supervisor review responsibilities.

### 10.3 Leave, purchases and help requests

**Leave:** open the available leave self-service page, review eligibility/balance, select type and dates, provide details and submit. Monitor status. If your staff account lacks an employee record, contact HR rather than creating an unrelated participant profile.

**Purchase requests:** open **My Purchase Requests**, create a draft with required justification/items and valid department/funding-source selections, then submit. Review carefully before submitting; owner actions on drafts differ from procurement/finance approval actions.

**Help & Support:** submit your IT/general support request with a clear description. Track your own request. Only authorised IT staff can operate the queue, assign tickets or perform support-team transitions.

### 10.4 Instructor/trainer workflow

1. Open the instructor dashboard/**My Courses** and select an assigned course.
2. Confirm cohort/branch assignments and the correct participant group.
3. Create/update modules and lessons; set publication/release order intentionally.
4. Attach learning files and select the appropriate download/view-only policy. Test the participant view.
5. Publish assessments with clear instructions, attachments, deadlines, attempts and scoring settings.
6. Review submissions, grade/comment, and process extension requests according to programme policy.
7. Post announcements and check that recipients are the intended enrolled participants.
8. Maintain timetables and attendance records; check timezones and avoid conflicting meetings.
9. Review appointment requests, approve/decline with a reason or propose another time. Mark completion only after the appointment starts.
10. Use assigned-course exports/reports for authorised work and protect exported personal data.

Instructors/trainers may also access mentee mentorship functionality where configured. That does not make all staff eligible for mentor-only profile management.

### 10.5 Programme, admissions and MEAL workflow

1. Set up the correct programme/project, branches, cohorts, targets and course relationships.
2. Configure a course/application call with eligibility, dates, assessments/surveys and pass requirements.
3. Use the relevant import centre/call template when importing external application responses.
4. Download the correct template, map fields, preview the input, reconcile duplicates and confirm the destination call before importing.
5. Review ambiguous returning-participant matches instead of silently merging records.
6. Review applications, record decisions and create/confirm enrolments using the authorised workflow.
7. Maintain workplans, milestones, activities, indicators and approvals within your role.
8. Review attendance, learning, mentorship and jobs-tracking reports. Reconcile totals before reporting externally.

A CSV file formatted for one importer is not automatically valid for another. Keep a backup/reconciliation record for bulk changes and obtain authorisation before deleting records.

### 10.6 HR, procurement, assets and IT specialists

- **HR/supervisors:** use leave approvals within your responsibility; follow supervisor/HR stages, authorised edit/cancel rules, appraisal review and lock/reopen controls.
- **Procurement/finance:** review submitted requests, approvals, purchase-order/receiving steps and funding sources. Do not approve outside your assigned stage or policy.
- **Asset/stores officers:** maintain authorised asset records, allocations, returns/disposal and exit-clearance dependencies.
- **IT team:** filter the support queue, assign only eligible IT staff, apply permitted status transitions and verify resolution before closure; use volume reports and daily review tasks.
- **Mentorship/placement teams:** review partner approvals, matches and job-posting/application tracking without granting partners administrative access.

Use the actions shown by your current workflow. Not every historical module document describes the current interface, and not every staff member has each capability.

### 10.7 Administrator-only responsibilities

Authorised administrators manage accounts, roles/permissions, branding, support settings, public content, selected admissions settings and targeted communications. Review recipient lists before sending; notification preferences and account status matter.

For branding, use a supported raster logo and a PNG favicon until ICO compatibility is resolved. Preview the website/PWA after changes. Do not assume the Flutter launcher icon will update from website settings.

Role grants should be minimal and auditable. Use deletion and import previews carefully, and never treat a successful page render as proof that all underlying workflow permissions are correct.

---

## 11. Mentor user manual

### 11.1 Join and sign in

1. Use the website's mentor joining page from the public partner navigation, or accept an account created by authorised staff.
2. Provide truthful contact, experience and mentoring information requested by the form.
3. Complete verification and wait for required approval/activation. A pending application is not an active mentor account.
4. Sign in at `/mentors/login`, not participant or staff login.
5. Open **Mentor Profile** and keep expertise, availability and relevant details current.

The Flutter participant app is not a mentor administration app.

### 11.2 Manage assigned mentees and sessions

1. Open **Mentor Dashboard** and review your actual matches/mentees.
2. Read relevant goals and participant context accessible through the match.
3. Agree a realistic session purpose, date/time, duration and venue/meeting link.
4. Create the session using the provided action and confirm the details with the mentee.
5. Hold the meeting, record agreed actions/notes, and complete the session workflow where authorised.
6. Use the available session report action to document outcomes and follow-up.
7. Follow the programme's match end/renewal rules; current development includes three-month matching windows.

Do not create sessions for unrelated users or interpret access to one mentee as permission to inspect every participant.

### 11.3 Review goals and follow up

1. Open **Mentee Goals** or the related dashboard section.
2. Read the goal, baseline/target, dates and participant updates.
3. Provide constructive feedback through the goal-review action.
4. Agree next steps with the participant; review your last agreed actions at the next session.
5. Use notifications/calendar to track changes, but confirm important arrangements directly when delivery is uncertain.

Protect mentee confidentiality. Record only necessary information and follow organisational safeguarding/escalation procedures for concerns. AI guidance, where available, does not replace human mentoring judgement.

### 11.4 Account and support

Use **Account Settings** for your personal account changes, and **Mentor Profile** for your mentoring details. Explore approved events/library resources. If no mentees appear, ask the coordinator to confirm your approval and match; do not open another user's links or credentials. Sign out on shared devices.

---

## 12. Employer user manual

### 12.1 Join, verify and complete the company profile

1. Use the employer joining page in the website's partner navigation, or an account provided by authorised staff.
2. Complete verification and any employer approval process.
3. Sign in at `/employers/login`.
4. Open **Company Profile** and complete the requested organisation/contact details.
5. Keep the profile accurate. If no employer profile exists, job access redirects you to setup; job submission requires an approved employer.

Employer recruitment is web-based; the Flutter participant app does not provide an employer portal.

### 12.2 Submit an opportunity

1. Open **Job Postings** and the available job-creation form.
2. Enter a clear title and number of positions; supply location/country and the appropriate employment/work-arrangement details.
3. Add responsibilities, requirements, skills and application deadline. Provide salary details when appropriate and ensure maximum salary is not below minimum.
4. Review the form for discriminatory, misleading or incomplete requirements.
5. Submit. New jobs are created as **pending approval**, not immediately published.
6. Check the posting status and respond to the placement/administration team's feedback.

Do not claim that every employer can directly publish, edit, close or delete every job. Use current available actions and contact the placement team for changes not supported by your portal.

### 12.3 Review applicants and update decisions

1. Open **Applicants**. The portal scopes the list to applications for your employer's jobs.
2. Review the applicant/job information and any documents available through approved workflows.
3. Choose the appropriate status: **Under Review**, **Shortlisted**, **Interview**, **Offer**, **Hired**, or **Rejected**.
4. Add relevant notes and select **Update**.
5. Confirm the recorded status. The platform sends application-status notifications through its configured channels.
6. Keep decisions accurate and timely; never mark a candidate hired merely to clear a queue.

Changing status to **Interview** or **Offer** is not proof that a scheduled interview or issued offer document exists. Backend interview/offer endpoints are present, but the reviewed applicants page exposes a status form rather than dedicated scheduling/offer controls. Use the placement team's approved process for those details.

### 12.4 Reports, privacy and account care

Use CSV/PDF export actions where present for your job postings/applicants. Export only for legitimate recruitment work; store files securely, limit access, and apply retention/deletion policy. Do not access other employers' applicant records or redistribute CVs without authority.

Use **Notifications**, **Calendar**, **Company Profile**, and **Account Settings** as needed. If a posting remains pending, contact the placement team rather than creating duplicates. Sign out after using a shared computer.

---

## 13. Troubleshooting and support

| Problem | User action | Support/developer follow-up |
| --- | --- | --- |
| Login fails | Confirm email/password and correct role portal; use approved password recovery. | Check active/pending status and identity without revealing account details to an unauthorised requester. |
| Verification link missing/expired | Check junk folder, resend and use the newest link. | Check mail delivery, canonical application URL and throttling. |
| Course missing or locked | Confirm enrolment and prerequisites; contact instructor/programme team. | Verify publication, release, admissions and cohort assignment. |
| App says offline despite a connection | Retry after confirming internet access, not just Wi-Fi connection. | Check API reachability, certificate/host configuration and error logs. |
| Submission still queued | Reconnect, sync and review waiting changes; do not clear data/sign out. | Check upload failures, operation ID, deadline and local attachment availability. |
| Submission rejected as overdue | Read the status and request an extension where available. | Explain effective due date/grace rules; do not silently override them. |
| File cannot download | Use View if restricted; check whether the file was cached before going offline. | Verify policy, storage existence, MIME, enrolment and reader support. |
| Old offline content | Refresh online; ask whether the resource was revised. | Review same-key cache invalidation and versioning. |
| No notification appears | Check app/browser permission and open notifications directly. | Check registration, credentials, preferences, FCM/APNs and scheduler/termination execution. |
| Biometric unlock unavailable | Configure device biometrics; retain password recovery. | Verify device support and native plugin behavior. |
| Staff menu missing | Expand sidebar groups; ask which permission is required. | Review least-privilege grants; do not issue blanket admin rights. |
| Mentor has no mentees | Confirm approval and assignment with the coordinator. | Check match dates/status and user relationships. |
| Employer cannot post | Complete company profile and confirm approval. | Check employer approval and submission validation. |
| Upload extraction/AI fails | Keep the original; retry where offered or edit manually. | Check private parser jobs, provider configuration and safe error reporting. |
| Website icon differs from app icon | Refresh/check PWA branding; ask support about the app release. | Laravel settings and native compiled assets have different update paths. |

### 13.1 Submit a useful support request

Include your role, approximate time, page/screen, steps, expected outcome and the readable error message. Include a participant ID or ticket reference only where necessary. Redact personal data from screenshots. Never send passwords, bearer tokens, full financial details or signing credentials.

Use the contacts displayed by **Help & Support**; they are managed through Laravel settings. Do not assume a default development email is the organisation's current approved support channel. Safeguarding, legal and HR issues should follow their designated confidential escalation paths, not a general technical ticket containing unnecessary sensitive details.

---

## 14. Acceptance criteria and evidence register

### 14.1 Minimum UAT scenarios before release approval

| Role / area | Required scenario | Acceptance evidence |
| --- | --- | --- |
| Participant account | Register, verify, sign in, reset/change password and edit own profile. | Correct portal, privacy and validation; no access to other participants' records. |
| Participant learning | Admit/enrol, open published/locked content, submit/grade, request extension and receive certificate. | Rules, progress and issue/download results match programme policy. |
| Offline participant | Cache material, queue a submission, reconnect, receive acknowledgement, and simulate rejection. | No duplicate writes; status and recovery are clear; private files remain controlled. |
| Staff/instructor | Assigned-course management, grading, attendance and appointment decisions. | Correct assignment scope, timestamps, notifications and export restrictions. |
| Staff operations | Task/KPI/appraisal, leave and purchase-request lifecycle. | Correct ownership/stages, review controls and audit trail. |
| Mentor | Approved login, own matches, session/report and goal review. | No unrelated-mentee access; accurate follow-up data. |
| Employer | Approved profile, pending job submission, own-applicant decision and export. | No cross-employer access; status/approval and communications correct. |
| IT support | Requester tickets, authorised queue/assignment and resolution. | No requester access to support-team privileges. |
| Administration/import | Role grants, targeted recipients, template/preview/import and reconciliation. | Minimal access, no unintended recipients/duplicates, rollback/recovery evidence. |
| Notifications | Actual mobile and browser delivery where supported, reminders and permission denial. | Device/server evidence; failure does not break the underlying user action. |
| Mobile release | Signed Android and Mac-built iOS on representative physical devices. | Build artifact/version, install, plugin, push, biometric and privacy checks recorded. |
| Operations | Deployment, restore, alert and rollback exercise. | Named owners, recovered data, approved objectives and documented actions. |

### 14.2 Primary sources

| Source | Use in this report |
| --- | --- |
| Git history, 22 September–11 October 2026; baseline `5e1e053` | Development chronology and latest committed scope. |
| `composer.json`, `package.json`, `bootstrap/app.php` | Stack, route/middleware/error configuration and scripts. |
| `routes/web.php`, `routes/api.php`, `routes/workspaces.php`, `routes/console.php` | Implemented entry points, current API and scheduled operation. |
| `app/Support/RoleShell.php`, Blade role/sidebar templates | Role landing and user-navigation labels. |
| Domain services, employer controllers and role/permission seeder | Ownership, business rules, employer approval/status and permissions. |
| `app/Services/Push/PushNotificationService.php` | Current FCM server capability and termination-based delivery limitation. |
| `app/Http/Controllers/BrandAssetController.php`, `public/sw.js`, branding partial | Dynamic PWA branding, fallback and security boundaries. |
| `participant_app/pubspec.yaml`, `lib/core/app_config.dart` | Mobile version, dependencies and API/build configuration. |
| Flutter API/auth/sync/offline/download/biometric/secure-screen services | Offline queue behavior, sign-out cleanup, cache, biometrics and copying limitations. |
| Flutter drawer/screens and tests | Native navigation, implemented participant UI and local test coverage. |
| `docs/DEPLOYMENT_CHECKLIST.md`, `docs/SECURITY_HARDENING.md` | Deployment/security baseline guidance, subject to current-code reconciliation. |
| `docs/participant_api_contract.md`, `docs/SCHEDULER.md`, iOS release checklist | Useful historical guidance with explicitly noted outdated statements. |
| Local command results and independent PWA reviews recorded in the development session | Exact verification counts and disclosed unresolved findings. |

### 14.3 Handover statement

The report documents implemented capabilities, known issues, local verification and suggested next work at the stated baseline. User interface details and policy settings can change; maintain this manual alongside code, release notes and API contract updates. Screenshots, production sign-off, deployment dates, commercial metrics and store-release links should be added only when supported by approved evidence.

**Approval status:** prepared for review; not a go-live approval. **This documentation task does not include a commit, push or deployment.**
