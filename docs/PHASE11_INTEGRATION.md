# Phase 11 Integration

1. Copy files.
2. Run system completion migration.
3. Merge web and API routes.
4. Register `SecurityHeaders` globally in `bootstrap/app.php`.
5. Install Laravel Sanctum if API authentication is required.
6. Add scheduler from `SCHEDULER.md`.
7. Configure queues.
8. Apply `SECURITY_HARDENING.md`.
9. Run all tests.
10. Follow `DEPLOYMENT_CHECKLIST.md`.

Recommended middleware alias/global registration for Laravel 11–13:

```php
->withMiddleware(function (\Illuminate\Foundation\Configuration\Middleware $middleware): void {
    $middleware->append(\App\Http\Middleware\SecurityHeaders::class);
})
```
