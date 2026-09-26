param(
    [string]$ProjectPath="D:\\projects\\elevate_her",
    [switch]$NoPush
)
$ErrorActionPreference="Stop"
Set-Location $ProjectPath
$scriptRoot=Split-Path -Parent $MyInvocation.MyCommand.Path
$patchRoot=Join-Path $scriptRoot "patch"
$targets=@(
"resources/views/auth/staff-login.blade.php",
"resources/views/admin/profile/edit.blade.php",
"app/Http/Controllers/Admin/PlatformSettingsController.php",
"resources/views/admin/settings/platform.blade.php",
"resources/views/partials/dynamic-branding.blade.php"
)
Write-Host "Checking only patch files..." -ForegroundColor Cyan
& git diff --check -- @targets
if($LASTEXITCODE -ne 0){ throw "Whitespace errors found in patch files." }
git status --short
if($NoPush){ exit 0 }
& git add -- @targets
if($LASTEXITCODE -ne 0){ throw "git add failed." }
& git diff --cached --check -- @targets
if($LASTEXITCODE -ne 0){ throw "Whitespace errors found in staged patch." }
$pending=git diff --cached --name-only -- @targets
if($pending){
    & git commit -m "Fix admin login profile tabs and platform branding"
    if($LASTEXITCODE -ne 0){ throw "git commit failed." }
}
& git push origin main
if($LASTEXITCODE -ne 0){ throw "git push failed." }
Write-Host "DONE. Changes pushed to origin/main." -ForegroundColor Green
