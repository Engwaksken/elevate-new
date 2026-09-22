# Production Security Hardening

Before go-live:

- Force HTTPS and secure cookies.
- `APP_ENV=production`
- `APP_DEBUG=false`
- protect `.env`, storage and backup files
- set correct trusted proxies
- use CSRF on all state-changing web routes
- enable staff MFA
- rate-limit login, password reset, verification and sensitive APIs
- apply `SecurityHeaders` globally
- enforce role/permission policies server-side
- store uploads outside public web root unless explicitly public
- validate MIME type and file size
- encrypt sensitive settings and supplier banking details
- rotate API and SMTP credentials during cutover
- maintain audit logs and security events
- configure automatic database/file backups
- test restore process before launch
- lock down CORS to approved origins
- use Sanctum or approved token authentication for mobile/API
- avoid returning raw exception messages in production
- review data retention and safeguarding rules
