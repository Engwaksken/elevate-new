ELEVATEHER360 — PHASE 26 UAT HARDENING

BUILT FROM THE UPLOADED PHASE 26 AUDIT

VERIFIED HEALTHY

- 9 tests passed / 15 assertions
- npm production build succeeded
- no PHP syntax errors in app/ or routes/
- Blade templates cached successfully
- no duplicate route names
- no duplicate METHOD + URI signatures
- no failed queue jobs
- public/storage exists
- storage and bootstrap/cache are writable
- all checked database relationships reported 0 orphan records
- all migrations through the multi-course Course Call migration are applied

IMPORTANT FINDING 1 — NEW ROLE PERMISSIONS

The new Course Call / Survey permissions existed, but the audit showed they were
assigned mainly to the two Super Administrator roles.

For example:

Administrator did NOT yet have:
course_calls.view
course_calls.manage
applications.review
entry_assessments.review
surveys.view
surveys.manage
survey_responses.view
survey_responses.export
settings.branding
settings.backups
settings.maintenance

M&E / MEAL Lead had meal permissions but NOT the new Survey Builder permissions.

Instructor had course/student permissions but NOT Course Call application review /
entry-assessment review.

This hardening assigns the intended Phase 25 permissions to:

Administrator
Program Manager
Programs Lead
Program Officer
Instructor / Trainer
M&E / MEAL Lead
M&E Officer

The command can be reviewed independently:

php artisan phase26:reconcile-permissions

Apply:

php artisan phase26:reconcile-permissions --apply

The installer runs the dry run first and then applies these recommended mappings.

IMPORTANT FINDING 2 — PARTICIPANT ROUTE ISOLATION

The audit showed:

/participant/course-opportunities
/participant/surveys

used only:

web, auth

That means a logged-in staff account could technically hit those participant URLs.

Phase 26 adds EnsureParticipantUser and changes these new Phase 25 participant route
groups to require:

auth
AND active participant account

Staff/admin sessions are rejected.

IMPORTANT FINDING 3 — PRODUCTION CONFIGURATION BLOCKERS

The audit environment is still development/local:

APP_NAME=Laravel
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost
MAIL_MAILER=log

These values are correct for local development but NOT for production.

A new command reports deployment blockers:

php artisan phase26:production-check

Before production deployment, configure at minimum:

APP_NAME="ElevateHer360"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://<production-domain>

and configure a real production mailer instead of:

MAIL_MAILER=log

The audit also showed:
FILESYSTEM_DISK=local

That is acceptable only if local server storage plus backup policy is intentional.

SCHEDULER

The audit currently shows only:

events:send-reminders every 5 minutes

So automated platform backup scheduling is not yet present. This is a remaining
production task if scheduled backups are required.

DATABASE INTEGRITY

The audit found zero orphans for all checked relationships including:

role_user
permission_role
enrolments
course_call_course
course_applications
survey questions/responses/answers
certificates
event attendance

INSTALL

cd D:\projects\elevate_her

Unblock-File .\APPLY_PHASE26_UAT_HARDENING.ps1

powershell -ExecutionPolicy Bypass `
    -File .\APPLY_PHASE26_UAT_HARDENING.ps1

IMPORTANT

The installer ends by running:

php artisan phase26:production-check

That check is expected to report local-environment blockers until you prepare the
actual production .env.

It is an audit command, not an installer failure.

NEXT PHASE

After this package passes tests, the remaining production cutover work is:

- configure production .env
- configure SMTP/production mail delivery
- configure queue worker/supervisor
- configure cron for schedule:run
- decide/implement scheduled backup cadence and retention
- configure Google backup adapter if Google storage is required
- production smoke test
- final deployment/cutover checklist
