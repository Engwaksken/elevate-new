param(
    [string]$ProjectPath = "D:\projects\elevate_her",
    [string]$OutputRoot = "D:\deploy",
    [string]$PackageName = "elevateher360_production"
)

$ErrorActionPreference = "Stop"

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

$ProjectPath = (Resolve-Path $ProjectPath).Path
$PackageDir = Join-Path $OutputRoot $PackageName
$ZipPath = Join-Path $OutputRoot ($PackageName + ".zip")
$utf8 = New-Object System.Text.UTF8Encoding($false)

Write-Host "ElevateHer360 - Production cPanel Packager" -ForegroundColor Cyan
Write-Host "Source:  $ProjectPath"
Write-Host "Output:  $ZipPath"

# ------------------------------------------------------------------
# Preflight: verify the consolidated route structure.
# ------------------------------------------------------------------
Write-Host ""
Write-Host "Checking consolidated Laravel routes..." -ForegroundColor Cyan

$requiredRouteFiles = @(
    ".\routes\web.php",
    ".\routes\api.php",
    ".\routes\console.php"
)

foreach($routeFile in $requiredRouteFiles){
    $fullPath = Join-Path $ProjectPath ($routeFile.TrimStart(".\"))
    if(-not (Test-Path $fullPath)){
        throw "Missing required route file: $routeFile"
    }
}

$phaseRouteFiles = Get-ChildItem (Join-Path $ProjectPath "routes") -File -ErrorAction SilentlyContinue |
    Where-Object {
        $_.Name -like "web.phase*.php" -or
        $_.Name -like "api.phase*.php" -or
        $_.Name -like "web.coursecalls*.php"
    }

if($phaseRouteFiles){
    Write-Host "WARNING: phased route files still exist:" -ForegroundColor Yellow
    $phaseRouteFiles | ForEach-Object { Write-Host "  $($_.Name)" -ForegroundColor Yellow }
    Write-Host "They will NOT be included in the production package." -ForegroundColor Yellow
}

# ------------------------------------------------------------------
# Preflight: production dependency/build requirements.
# ------------------------------------------------------------------
Write-Host ""
Write-Host "Checking production dependencies..." -ForegroundColor Cyan

$required = @(
    "artisan",
    "app",
    "bootstrap",
    "config",
    "database",
    "public",
    "resources",
    "routes",
    "storage",
    "vendor",
    "public\build"
)

$missing = @()

foreach($relative in $required){
    $path = Join-Path $ProjectPath $relative

    if(Test-Path $path){
        Write-Host "OK: $relative" -ForegroundColor DarkGreen
    }else{
        Write-Host "MISSING: $relative" -ForegroundColor Red
        $missing += $relative
    }
}

if($missing.Count -gt 0){
    Write-Host ""
    Write-Host "Cannot package because required production files are missing." -ForegroundColor Red
    Write-Host "Missing: $($missing -join ', ')" -ForegroundColor Red
    Write-Host ""
    Write-Host "Run locally first:" -ForegroundColor Yellow
    Write-Host "  composer install --no-dev --optimize-autoloader"
    Write-Host "  npm install"
    Write-Host "  npm run build"
    exit 1
}

# ------------------------------------------------------------------
# Validate PHP routes before packaging.
# ------------------------------------------------------------------
Write-Host ""
Write-Host "Validating route syntax..." -ForegroundColor Cyan
Push-Location $ProjectPath
try {
    Invoke-Native php "-l" ".\routes\web.php"
    Invoke-Native php "-l" ".\routes\api.php"
    Invoke-Native php "-l" ".\routes\console.php"

    Write-Host ""
    Write-Host "Validating route loading/cache..." -ForegroundColor Cyan
    Invoke-Native php "artisan" "optimize:clear"
    Invoke-Native php "artisan" "route:list"
    Invoke-Native php "artisan" "route:cache"
    Invoke-Native php "artisan" "route:clear"
    Invoke-Native php "artisan" "view:cache"
} finally {
    Pop-Location
}

# ------------------------------------------------------------------
# Recreate package directory.
# ------------------------------------------------------------------
if(Test-Path $PackageDir){
    Remove-Item $PackageDir -Recurse -Force
}

if(Test-Path $ZipPath){
    Remove-Item $ZipPath -Force
}

New-Item -ItemType Directory -Force -Path $PackageDir | Out-Null

# ------------------------------------------------------------------
# Exclusions for production shared-host package.
# ------------------------------------------------------------------
$excludeDirs = @(
    ".git",
    ".github",
    ".idea",
    ".vscode",
    ".fleet",
    ".cache",
    ".phpunit.cache",
    "node_modules",
    "tests",
    "coverage",
    "docs",
    "documentation",
    "screenshots",
    "backups",
    "backup",
    "tmp",
    "temp"
)

$excludeFiles = @(
    ".env",
    ".env.local",
    ".env.testing",
    ".env.development",
    ".DS_Store",
    "Thumbs.db",
    "*.log",
    "*.zip",
    "*.rar",
    "*.7z",
    "*.bak",
    "*.tmp",
    "*.temp",
    "*.sqlite",
    "*.sqlite3",
    "phpunit.xml",
    "phpunit.xml.bak",
    "phpstan.neon",
    "pint.json",
    "APPLY_*.ps1",
    "RUN_PHASE*.ps1",
    "VALIDATE_*.ps1",
    "BUILD_CPANEL_PACKAGE.ps1"
)

$xdArgs = @()
foreach($dir in $excludeDirs){
    $xdArgs += "/XD"
    $xdArgs += (Join-Path $ProjectPath $dir)
}

$xfArgs = @()
foreach($file in $excludeFiles){
    $xfArgs += "/XF"
    $xfArgs += $file
}

$robocopyArgs = @(
    $ProjectPath,
    $PackageDir,
    "/E",
    "/COPY:DAT",
    "/DCOPY:DAT",
    "/R:1",
    "/W:1",
    "/NFL",
    "/NDL",
    "/NJH",
    "/NJS",
    "/NP"
) + $xdArgs + $xfArgs

Write-Host ""
Write-Host "Copying clean production tree..." -ForegroundColor Cyan

& robocopy @robocopyArgs | Out-Null
$rc = $LASTEXITCODE

if($rc -gt 7){
    throw "Robocopy failed with exit code $rc"
}

# ------------------------------------------------------------------
# Remove route backups and phased route leftovers from copied package.
# ------------------------------------------------------------------
$routeDir = Join-Path $PackageDir "routes"

if(Test-Path $routeDir){
    Get-ChildItem $routeDir -File -ErrorAction SilentlyContinue |
        Where-Object {
            $_.Name -like "web.phase*.php" -or
            $_.Name -like "api.phase*.php" -or
            $_.Name -like "web.coursecalls*.php" -or
            $_.Name -like "*.bak" -or
            $_.Name -eq "web.foundation.example.php"
        } |
        Remove-Item -Force -ErrorAction SilentlyContinue
}

$routeBackupDir = Join-Path $PackageDir "storage\app\route-backups"
if(Test-Path $routeBackupDir){
    Remove-Item $routeBackupDir -Recurse -Force
}

# ------------------------------------------------------------------
# Remove prior audits, generated patches and local deployment artifacts.
# ------------------------------------------------------------------
$storageApp = Join-Path $PackageDir "storage\app"

if(Test-Path $storageApp){
    Get-ChildItem $storageApp -Recurse -File -ErrorAction SilentlyContinue |
        Where-Object {
            $_.Name -like "*audit*.zip" -or
            $_.Name -like "*audit*.txt" -or
            $_.Name -like "phase*.zip" -or
            $_.Name -like "phase*.txt" -or
            $_.Name -like "elevate_*.zip"
        } |
        Remove-Item -Force -ErrorAction SilentlyContinue
}

# ------------------------------------------------------------------
# Clean runtime state while preserving directories.
# ------------------------------------------------------------------
$runtimeDirs = @(
    "storage\framework\cache\data",
    "storage\framework\sessions",
    "storage\framework\views",
    "storage\logs",
    "bootstrap\cache"
)

foreach($relative in $runtimeDirs){
    $dir = Join-Path $PackageDir $relative

    if(-not (Test-Path $dir)){
        New-Item -ItemType Directory -Force -Path $dir | Out-Null
    }

    Get-ChildItem $dir -Force -ErrorAction SilentlyContinue |
        Where-Object { $_.Name -ne ".gitignore" } |
        Remove-Item -Recurse -Force -ErrorAction SilentlyContinue
}

# ------------------------------------------------------------------
# Verify final route directory contains only the consolidated route files
# plus any legitimate non-phase Laravel route files.
# ------------------------------------------------------------------
Write-Host ""
Write-Host "Final route files:" -ForegroundColor Cyan

Get-ChildItem $routeDir -File |
    Sort-Object Name |
    ForEach-Object {
        Write-Host "  $($_.Name)"
    }

foreach($routeName in @("web.php","api.php","console.php")){
    if(-not (Test-Path (Join-Path $routeDir $routeName))){
        throw "Final package is missing routes/$routeName"
    }
}

# ------------------------------------------------------------------
# Deployment documentation.
# ------------------------------------------------------------------
$deploymentReadme = @"
ElevateHer360 - cPanel Production Deployment

PACKAGE CONTENTS
================
This package includes:
- Laravel application source
- vendor/ production PHP dependencies
- public/build/ compiled frontend assets
- consolidated routes/web.php
- consolidated routes/api.php
- routes/console.php

This package excludes:
- .git / GitHub metadata
- node_modules
- tests
- local .env
- logs
- runtime caches
- route backups
- phased route files
- audit ZIPs
- generated patch ZIPs
- local PowerShell installer scripts

PRODUCTION ENVIRONMENT
======================

Create the .env manually on the server.

Minimum:

APP_NAME="ElevateHer360"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://site.elevateher360.org

Configure:
- MySQL database
- SMTP mail
- queue connection
- session/cache settings

DOCUMENT ROOT
=============

Best configuration:

/path/to/project/public

Do NOT point the web domain at the Laravel project root when cPanel allows the
document root to target /public.

AFTER EXTRACTION
================

If cPanel Terminal / PHP CLI is available:

php artisan optimize:clear
php artisan migrate --force
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache

If public/storage already exists, do not recreate it.

WRITABLE DIRECTORIES
====================

storage/
bootstrap/cache/

Typical shared-host permissions:

chmod -R 775 storage bootstrap/cache

CRON
====

Laravel scheduler:

* * * * * cd /path/to/project && /usr/local/bin/php artisan schedule:run >> /dev/null 2>&1

Adapt the PHP path to the PHP binary exposed by cPanel.

QUEUE
=====

If persistent workers are not available on the shared host, use cPanel cron for
short-lived queue processing or use sync/database queue settings appropriate for the
hosting environment.

IMPORTANT
=========

Do not upload the local development .env.

Do not run composer install or npm install on the shared host unless the host supports
them. vendor/ and public/build/ are already included in this package.
"@

[IO.File]::WriteAllText(
    (Join-Path $PackageDir "CPANEL_DEPLOYMENT_README.txt"),
    $deploymentReadme,
    $utf8
)

# ------------------------------------------------------------------
# Create deployment manifest.
# ------------------------------------------------------------------
$manifest = @()
$manifest += "ElevateHer360 Production Package"
$manifest += "Built: $(Get-Date -Format 'yyyy-MM-dd HH:mm:ss')"
$manifest += "Source: $ProjectPath"
$manifest += ""
$manifest += "Routes:"

Get-ChildItem $routeDir -File |
    Sort-Object Name |
    ForEach-Object {
        $manifest += "  - routes/$($_.Name)"
    }

$manifest += ""
$manifest += "public/build: present"
$manifest += "vendor: present"
$manifest += ".env: excluded"
$manifest += "node_modules: excluded"
$manifest += "tests: excluded"
$manifest += "route-backups: excluded"

[IO.File]::WriteAllText(
    (Join-Path $PackageDir "DEPLOYMENT_MANIFEST.txt"),
    ($manifest -join "`r`n"),
    $utf8
)

# ------------------------------------------------------------------
# ZIP package.
# ------------------------------------------------------------------
Write-Host ""
Write-Host "Creating production ZIP..." -ForegroundColor Cyan

Compress-Archive `
    -Path (Join-Path $PackageDir "*") `
    -DestinationPath $ZipPath `
    -CompressionLevel Optimal `
    -Force

$zip = Get-Item $ZipPath

Write-Host ""
Write-Host "DONE." -ForegroundColor Green
Write-Host "Upload this file using cPanel File Manager:" -ForegroundColor Yellow
Write-Host $ZipPath -ForegroundColor Yellow
Write-Host ""
Write-Host ("ZIP size: {0:N2} MB" -f ($zip.Length / 1MB))
Write-Host ""
Write-Host "Original project was not deleted or modified by the packaging step." -ForegroundColor Green
