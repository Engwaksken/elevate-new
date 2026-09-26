param([string]$ProjectPath="D:\projects\elevate_her")

$ErrorActionPreference="Stop"
Set-Location $ProjectPath

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

Write-Host "Validating consolidated routes V3..." -ForegroundColor Cyan

Write-Host ""
Write-Host "Checking route PHP syntax..." -ForegroundColor Cyan
Invoke-Native php "-l" ".\routes\web.php"
Invoke-Native php "-l" ".\routes\api.php"
Invoke-Native php "-l" ".\routes\console.php"

Write-Host ""
Write-Host "Clearing caches..." -ForegroundColor Cyan
Invoke-Native php "artisan" "optimize:clear"
Invoke-Native php "artisan" "route:clear"

Write-Host ""
Write-Host "Loading routes..." -ForegroundColor Cyan
Invoke-Native php "artisan" "route:list"

Write-Host ""
Write-Host "Testing route cache..." -ForegroundColor Cyan
Invoke-Native php "artisan" "route:cache"
Invoke-Native php "artisan" "route:clear"

Write-Host ""
Write-Host "Compiling Blade views..." -ForegroundColor Cyan
Invoke-Native php "artisan" "view:cache"

Write-Host ""
Write-Host "Checking whether the Laravel test command is available..." -ForegroundColor Cyan

$artisanCommands = php artisan list --raw 2>$null
$hasArtisanTest = $false

if($LASTEXITCODE -eq 0){
    $hasArtisanTest = ($artisanCommands -split "`r?`n") -contains "test"
}

if($hasArtisanTest){
    Write-Host "Running php artisan test..." -ForegroundColor Cyan
    Invoke-Native php "artisan" "test"
}
elseif(Test-Path ".\vendor\bin\phpunit.bat"){
    Write-Host "php artisan test is unavailable; running vendor\bin\phpunit.bat..." -ForegroundColor Yellow
    Invoke-Native ".\vendor\bin\phpunit.bat"
}
elseif(Test-Path ".\vendor\bin\phpunit"){
    Write-Host "php artisan test is unavailable; running vendor\bin\phpunit..." -ForegroundColor Yellow
    Invoke-Native ".\vendor\bin\phpunit"
}
else{
    Write-Host "Test tooling is not installed in this production/no-dev vendor tree." -ForegroundColor Yellow
    Write-Host "Skipping tests. Route syntax, route cache and Blade compilation already passed." -ForegroundColor Yellow
}

Write-Host ""
Write-Host "DONE." -ForegroundColor Green
Write-Host "The consolidated route files are valid and no phased route files are required." -ForegroundColor Green
