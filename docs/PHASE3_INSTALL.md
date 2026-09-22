# ElevateHer360 Phase 3 Integration

## Prerequisites
Phase 1 and Phase 2 should already be integrated.

## Installation

1. Copy files into the Laravel application.
2. Run:
   php artisan migrate
3. Merge `routes/web.phase3.php` into `routes/web.php`.
4. Merge `docs/UserModel_Phase3_Patch.php` into `App\Models\User`.
5. Ensure `AuditLog`, `Role`, `Permission`, `Profile`, `Branch`, `Programme`, `Project`, and `Cohort` models from previous phases exist.
6. Run:
   php artisan optimize:clear
   php artisan route:list

## MFA
This phase adds encrypted MFA storage fields only. Do not mark staff MFA complete until:
- TOTP enrolment QR setup is implemented
- confirmation challenge is implemented
- recovery codes are implemented
- login challenge middleware is implemented
- privileged roles can be forced to enable MFA

## Migration staging
The CSV staging flow is intentionally non-destructive:
- it imports into staging tables
- performs conservative identity matching
- does not merge or overwrite production users
- ambiguous records remain for review

## eLearning
This phase creates the core schema and course/module/lesson administration.
Next phase should add:
- course-cohort assignment UI
- instructor assignment
- learner enrolment
- participant course catalogue
- lesson player
- progress calculation
- assessments UI
- attendance UI
- certificate generation
