# Phase 2 Integration

This package layers on top of the ElevateHer360 Phase 1 Foundation.

## Required steps

1. Confirm Phase 1 migrations and models are already installed.
2. Copy Phase 2 files into the Laravel project.
3. Add `App\Models\AuditLog` from this package.
4. Create `App\Models\Consent` using `docs/ConsentModel.php` if not already present.
5. Update `App\Models\User` to implement `MustVerifyEmail`.
6. Ensure User has Phase 1 relationships:
   - profile()
   - roles()
   - hasRole()
   - hasPermission()
   - isStaff()
7. Register middleware aliases:
   - staff
   - permission
8. Merge `routes/web.phase2.php` into `routes/web.php`.
9. Ensure mail/SMTP is configured before testing verification email.
10. Run:
    php artisan optimize:clear
    php artisan route:list
    php artisan test

## First staff account

Do not create a predictable production password in a seeder.
Create the first staff user manually with Tinker or a one-time deployment command,
set `user_type=staff`, `status=active`, then attach the `super-administrator` role.

## Security

Before production:
- enable HTTPS
- configure trusted mail
- enable staff MFA in a later phase
- verify rate limiting
- confirm private session/cookie settings
- review all role permission mappings
