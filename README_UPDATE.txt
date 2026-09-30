ElevateHer360 Appraisal + Reporting + Workspace Update

Implemented: end-to-end appraisal workspace with KRA/KPI self, supervisor and agreed scores; appraisal meeting and confirmations; status history; separate completion/performance calculations; KRA/KPI weight validation; CSV template + CSV/XLS/XLSX preview foundation; mentorship/jobs tracking reports; grouped Learning, Planning & MEAL, Mentorship, Jobs and Reports workspaces.

Apply OUTSIDE the Laravel project:
Set-ExecutionPolicy -Scope Process -ExecutionPolicy Bypass -Force
.\apply_update.ps1 -ProjectPath "D:\projects\elevate_her"

Then:
cd D:\projects\elevate_her
php artisan migrate
php artisan optimize:clear
php artisan route:list --path=performance
php artisan route:list --path=workspace
php artisan route:list --path=import-centre

The import centre intentionally previews before insertion. Legacy files should not be inserted silently without validation/mapping.
