ELEVATEHER360 — ADMIN LOGIN / PROFILE / PLATFORM BRANDING FIX

Fixes:
- /admin/login password eye toggle positioning/display.
- Admin Profile changed to tabs: Profile Details + Change Password.
- Platform Configuration shows current logo/favicon previews.
- Selecting a new logo/favicon previews immediately before upload.
- Branding uploads use unique file names and replace old managed assets.
- Saved logo now renders in existing admin/public/participant brand marks.
- Saved favicon uses the public storage URL and cache-busting path.
- Platform Settings stays on the tab that was just saved.

No npm build or Composer install is required for this patch.

DIRECT GITHUB NOTE
The connected GitHub integration could read Engwaksken/elevate-new but GitHub rejected
the attempted direct repository write with HTTP 403 "Resource not accessible by integration".
This package therefore includes a PowerShell installer that applies the exact inspected
changes, commits them, and runs `git push origin main` using your local Git credentials.

APPLY AND PUSH

Extract this ZIP, then:

powershell -ExecutionPolicy Bypass `
  -File .\APPLY_AND_PUSH_UI_BRANDING_FIX.ps1 `
  -ProjectPath "D:\projects\elevate_her"

The script backs up the five replaced files, validates PHP/Blade/routes, checks the Git
diff, stages only the intended files, commits, and pushes main.

LIVE AFTER PUSH

cd /home/vividfin/site.elevateher360.org
git pull origin main

php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

No composer install.
No npm install/build.
