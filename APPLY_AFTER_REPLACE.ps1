param([string]$ProjectPath="D:\projects\elevate_her")
$ErrorActionPreference="Stop"
Set-Location $ProjectPath

Write-Host "Applying ElevateHer360 career-document replacement files already extracted into the project..." -ForegroundColor Cyan

php artisan optimize:clear
php artisan migrate
npm run build

Write-Host ""
Write-Host "Career routes:" -ForegroundColor Cyan
php artisan route:list --name=career.resume
php artisan route:list --name=career.cover-letter

Write-Host ""
Write-Host "Profile routes:" -ForegroundColor Cyan
php artisan route:list --name=profile

Write-Host ""
Write-Host "Running tests..." -ForegroundColor Cyan
php artisan test

php artisan queue:restart

Write-Host ""
Write-Host "DONE." -ForegroundColor Green
Write-Host "Start the queue worker in a separate terminal:" -ForegroundColor Yellow
Write-Host "  php artisan queue:work database --queue=default -vvv --sleep=1 --tries=3 --timeout=120"
