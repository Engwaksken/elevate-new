# ElevateHer360 Consolidated Deployment Checklist

## 1. Code integration
- Merge Phases 1–11 into one Laravel repository.
- Resolve duplicate route/view/controller names.
- Apply all model patches.
- Refactor repeated helpers into services/policies.
- Run formatter/static analysis.

## 2. Migration ordering
Because development was delivered in modules, create a clean migration order before production.
Recommended:
1. Laravel base users/auth
2. Roles/permissions/profiles
3. programmes/projects/cohorts/branches
4. LMS core
5. mentorship
6. Resume Builder
7. jobs/employers
8. library
9. workplans/M&E/calendar
10. HR/leave/appraisals/exits
11. suppliers/procurement/assets
12. system completion/settings/security

Never apply module ZIP migrations blindly to production. Consolidate and test them on a staging copy first.

## 3. Data migration
- inventory legacy tables and files
- map source fields to target fields
- match identities by verified email/phone only
- stage ambiguous records
- reconcile row counts
- reconcile enrolments/progress/certificates
- reconcile mentors/matches/sessions
- reconcile jobs/applications
- reconcile library files
- retain legacy IDs in migration metadata where useful
- obtain business-owner sign-off

## 4. Configuration
- APP_URL
- timezone Africa/Kampala
- UK English UI conventions
- MySQL production database
- SMTP
- queue connection
- filesystem/private storage
- Google/OAuth if enabled
- SMS/WhatsApp if enabled
- Sanctum/API
- backup destinations

## 5. Seed and access
- run RolePermissionSeeder
- create Super Administrator manually
- configure initial departments/positions
- configure leave types
- configure asset categories
- configure library categories
- verify permissions

## 6. Verification
Run:
- php artisan migrate --pretend
- php artisan migrate
- php artisan db:seed
- php artisan route:list
- php artisan config:cache
- php artisan route:cache
- php artisan view:cache
- php artisan test

## 7. UAT
Test each participant and staff role separately.
Do not approve go-live only because pages render.

## 8. Cutover
- final legacy backup
- maintenance/read-only window
- final migration
- reconcile
- smoke test
- DNS/URL switch
- monitor logs/queues/email
- keep rollback point

## 9. Post go-live
- daily review for first week
- permission review
- failed jobs/log review
- user feedback triage
- backup verification
