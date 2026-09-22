# Phase 5 Integration

Prerequisites:
- Phases 1–4 already integrated.

Run:
1. Copy files.
2. `php artisan migrate`
3. Merge `routes/web.phase5.php`.
4. Add relationships:
   - User: mentorProfile(), menteeProfile(), notifications()
   - AssessmentAttempt: assessment(), user()
   - Assessment: questions(), course()
   - Certificate: user(), course()
5. Configure private storage.
6. Optional PDF:
   `composer require barryvdh/laravel-dompdf`
7. Optional QR:
   `composer require simplesoftwareio/simple-qrcode`
8. Run:
   `php artisan optimize:clear`
   `php artisan route:list`

Important:
- Learning files use the `local` disk, not public storage.
- Certificate PDF generation throws a clear error if DOMPDF is not installed.
- Mentor applications should also capture safeguarding consent using the central consents table.
- Add email/in-app notifications to mentorship matching and session scheduling in the next slice.
