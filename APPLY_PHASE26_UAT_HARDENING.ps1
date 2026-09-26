param([string]$ProjectPath="D:\projects\elevate_her")

$ErrorActionPreference="Stop"
Set-Location $ProjectPath

$utf8=New-Object System.Text.UTF8Encoding($false)
$stamp=Get-Date -Format "yyyyMMdd_HHmmss"

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

Write-Host "Installing Phase 26 UAT hardening..." -ForegroundColor Cyan

foreach($file in @(
    ".\routes\web.phase25.php"
)){
    if(Test-Path $file){
        Copy-Item $file ($file+".before_phase26_"+$stamp+".bak") -Force
    }
}

# ------------------------------------------------------------------
# Harden the two new participant areas so staff/admin sessions cannot
# enter participant Course Opportunities / Surveys merely because they
# are authenticated.
# ------------------------------------------------------------------
$phase25RoutePath=".\routes\web.phase25.php"

if(Test-Path $phase25RoutePath){
    $routes=[IO.File]::ReadAllText($phase25RoutePath)

    $old="Route::middleware(['auth'])->prefix('participant')->name('participant.')->group(function () {"
    $new="Route::middleware(['auth', \App\Http\Middleware\EnsureParticipantUser::class])->prefix('participant')->name('participant.')->group(function () {"

    if($routes.Contains($old)){
        $routes=$routes.Replace($old,$new)
        [IO.File]::WriteAllText($phase25RoutePath,$routes,$utf8)
        Write-Host "Participant Phase 25 routes hardened." -ForegroundColor DarkGreen
    }elseif($routes.Contains("EnsureParticipantUser::class")){
        Write-Host "Participant Phase 25 routes already hardened." -ForegroundColor DarkGray
    }else{
        Write-Host "WARNING: Expected participant route group was not found." -ForegroundColor Yellow
    }
}else{
    Write-Host "WARNING: routes/web.phase25.php not found." -ForegroundColor Yellow
}

Write-Host ""
Write-Host "Regenerating Composer autoload..." -ForegroundColor Cyan
Invoke-Native composer "dump-autoload" "--optimize"

Write-Host ""
Write-Host "Clearing caches..." -ForegroundColor Cyan
Invoke-Native php "artisan" "optimize:clear"

Write-Host ""
Write-Host "Checking PHP syntax..." -ForegroundColor Cyan
Invoke-Native php "-l" ".\app\Http\Middleware\EnsureParticipantUser.php"
Invoke-Native php "-l" ".\app\Console\Commands\Phase26ReconcilePermissions.php"
Invoke-Native php "-l" ".\app\Console\Commands\Phase26ProductionReadiness.php"

Write-Host ""
Write-Host "Phase 26 permission reconciliation DRY RUN..." -ForegroundColor Cyan
Invoke-Native php "artisan" "phase26:reconcile-permissions"

Write-Host ""
Write-Host "Applying Phase 26 recommended role permissions..." -ForegroundColor Cyan
Invoke-Native php "artisan" "phase26:reconcile-permissions" "--apply"

Write-Host ""
Write-Host "Clearing route cache after middleware update..." -ForegroundColor Cyan
Invoke-Native php "artisan" "route:clear"

Write-Host ""
Write-Host "Checking participant route middleware..." -ForegroundColor Cyan
Invoke-Native php "artisan" "route:list" "--path=participant/course-opportunities" "-v"
Invoke-Native php "artisan" "route:list" "--path=participant/surveys" "-v"

Write-Host ""
Write-Host "Compiling Blade views..." -ForegroundColor Cyan
Invoke-Native php "artisan" "view:cache"

Write-Host ""
Write-Host "Building frontend..." -ForegroundColor Cyan
Invoke-Native npm "run" "build"

Write-Host ""
Write-Host "Running full tests..." -ForegroundColor Cyan
Invoke-Native php "artisan" "test"

Write-Host ""
Write-Host "Running production-readiness check..." -ForegroundColor Cyan
Write-Host "NOTE: This check is EXPECTED to report blockers while your .env is still local." -ForegroundColor Yellow

& php artisan phase26:production-check

Write-Host ""
Write-Host "DONE. Phase 26 UAT hardening installed." -ForegroundColor Green
Write-Host "The production check above may remain red until deployment .env values are changed." -ForegroundColor Yellow
