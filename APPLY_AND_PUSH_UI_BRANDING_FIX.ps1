param(
    [string]$ProjectPath="D:\projects\elevate_her",
    [switch]$NoPush
)

$ErrorActionPreference="Stop"
Set-Location $ProjectPath

$utf8=New-Object System.Text.UTF8Encoding($false)
$stamp=Get-Date -Format "yyyyMMdd_HHmmss"
$scriptRoot=Split-Path -Parent $MyInvocation.MyCommand.Path
$patchRoot=Join-Path $scriptRoot "patch"
$backupRoot=Join-Path $ProjectPath "storage\app\patch-backups\ui_branding_$stamp"

function Invoke-Native {
    param(
        [Parameter(Mandatory=$true)][string]$Command,
        [Parameter(ValueFromRemainingArguments=$true)][string[]]$Arguments
    )
    & $Command @Arguments
    if($LASTEXITCODE -ne 0){
        throw "Command failed ($LASTEXITCODE): $Command $($Arguments -join ' ')"
    }
}

$targets=@(
    "resources\views\auth\staff-login.blade.php",
    "resources\views\admin\profile\edit.blade.php",
    "app\Http\Controllers\Admin\PlatformSettingsController.php",
    "resources\views\admin\settings\platform.blade.php",
    "resources\views\partials\dynamic-branding.blade.php"
)

Write-Host "Applying admin login, profile and platform branding fixes..." -ForegroundColor Cyan
New-Item -ItemType Directory -Force -Path $backupRoot | Out-Null

foreach($relative in $targets){
    $source=Join-Path $patchRoot $relative
    $destination=Join-Path $ProjectPath $relative

    if(-not (Test-Path $source)){ throw "Missing patch file: $source" }

    if(Test-Path $destination){
        $backup=Join-Path $backupRoot $relative
        New-Item -ItemType Directory -Force -Path (Split-Path -Parent $backup) | Out-Null
        Copy-Item $destination $backup -Force
    }

    New-Item -ItemType Directory -Force -Path (Split-Path -Parent $destination) | Out-Null
    $content=[IO.File]::ReadAllText($source)
    [IO.File]::WriteAllText($destination,$content,$utf8)
    Write-Host "Updated: $relative" -ForegroundColor DarkGreen
}

Write-Host ""
Write-Host "Validating PHP..." -ForegroundColor Cyan
Invoke-Native php "-l" ".\app\Http\Controllers\Admin\PlatformSettingsController.php"

Write-Host ""
Write-Host "Clearing caches and compiling Blade..." -ForegroundColor Cyan
Invoke-Native php "artisan" "optimize:clear"
Invoke-Native php "artisan" "view:cache"

Write-Host ""
Write-Host "Checking admin routes..." -ForegroundColor Cyan
Invoke-Native php "artisan" "route:list" "--path=admin"

Write-Host ""
Write-Host "Checking Git diff..." -ForegroundColor Cyan
Invoke-Native git "diff" "--check"
git status --short

if($NoPush){
    Write-Host ""
    Write-Host "NoPush selected. Patch applied but not committed/pushed." -ForegroundColor Yellow
    exit 0
}

Write-Host ""
Write-Host "Staging only the intended files..." -ForegroundColor Cyan
Invoke-Native git "add" `
    "resources/views/auth/staff-login.blade.php" `
    "resources/views/admin/profile/edit.blade.php" `
    "app/Http/Controllers/Admin/PlatformSettingsController.php" `
    "resources/views/admin/settings/platform.blade.php" `
    "resources/views/partials/dynamic-branding.blade.php"

$pending=git diff --cached --name-only

if($pending){
    Invoke-Native git "commit" "-m" "Fix admin login profile tabs and platform branding"
}else{
    Write-Host "No staged changes to commit." -ForegroundColor Yellow
}

Write-Host ""
Write-Host "Pushing origin/main..." -ForegroundColor Cyan
Invoke-Native git "push" "origin" "main"

Write-Host ""
Write-Host "DONE. Changes pushed to origin/main." -ForegroundColor Green
Write-Host "Backup: $backupRoot" -ForegroundColor DarkGray
