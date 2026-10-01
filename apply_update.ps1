param(
    [string]$ProjectPath = "D:\projects\elevate_her"
)

$ErrorActionPreference = "Stop"

if (-not (Test-Path $ProjectPath)) {
    throw "Laravel project not found: $ProjectPath"
}

$artisan = Join-Path $ProjectPath "artisan"
if (-not (Test-Path $artisan)) {
    throw "The target path is not a Laravel project: $ProjectPath"
}

$sourceRoot = Join-Path $PSScriptRoot "files"

$files = @(
    "routes\workspaces.php",
    "app\Http\Controllers\HR\AppraisalWorkspaceController.php",
    "resources\views\hr\appraisals\workflow.blade.php",
    "resources\views\partials\admin-sidebar.blade.php"
)

foreach ($relative in $files) {
    $source = Join-Path $sourceRoot $relative
    $target = Join-Path $ProjectPath $relative

    if (-not (Test-Path $source)) {
        throw "Update file missing: $source"
    }

    $targetDir = Split-Path $target -Parent
    if (-not (Test-Path $targetDir)) {
        New-Item -ItemType Directory -Path $targetDir -Force | Out-Null
    }

    Copy-Item $source $target -Force
    Write-Host "Updated: $relative" -ForegroundColor Green
}

Write-Host ""
Write-Host "Update files copied successfully." -ForegroundColor Green
Write-Host "Now run:" -ForegroundColor Cyan
Write-Host "  php -l routes\workspaces.php"
Write-Host "  php -l app\Http\Controllers\HR\AppraisalWorkspaceController.php"
Write-Host "  php artisan optimize:clear"
Write-Host "  php artisan route:list --path=staff/performance"
