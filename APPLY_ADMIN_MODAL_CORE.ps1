param([string]$ProjectPath="D:\projects\elevate_her")
$ErrorActionPreference="Stop"
Set-Location $ProjectPath
$utf8=New-Object System.Text.UTF8Encoding($false)

Write-Host "Applying ElevateHer360 modal CRUD core..." -ForegroundColor Cyan

# CSS import
$cssPath=Join-Path $ProjectPath "resources\css\app.css"
$css=[IO.File]::ReadAllText($cssPath)
if($css -notmatch "admin-modal-crud.css"){
    [IO.File]::WriteAllText($cssPath,"@import './admin-modal-crud.css';`r`n"+$css,$utf8)
}

# JS import
$jsPath=Join-Path $ProjectPath "resources\js\app.js"
$js=[IO.File]::ReadAllText($jsPath)
if($js -notmatch "admin-modal-crud"){
    $js += "`r`nimport './admin-modal-crud';`r`n"
    [IO.File]::WriteAllText($jsPath,$js,$utf8)
}

# Add bulk delete routes before resource routes.
$phase2=Join-Path $ProjectPath "routes\web.phase2.php"
$r=[IO.File]::ReadAllText($phase2)

$replacements=@(
    @{
        Needle="        Route::resource('programmes', ProgrammeController::class)->except('show')"
        Insert="        Route::delete('/programmes/bulk-delete', [ProgrammeController::class, 'bulkDestroy'])->middleware('permission:programmes.manage')->name('programmes.bulk-destroy');`r`n`r`n        Route::resource('programmes', ProgrammeController::class)->except('show')"
    },
    @{
        Needle="        Route::resource('projects', ProjectController::class)->except('show')"
        Insert="        Route::delete('/projects/bulk-delete', [ProjectController::class, 'bulkDestroy'])->middleware('permission:programmes.manage')->name('projects.bulk-destroy');`r`n`r`n        Route::resource('projects', ProjectController::class)->except('show')"
    },
    @{
        Needle="        Route::resource('branches', BranchController::class)->except('show')"
        Insert="        Route::delete('/branches/bulk-delete', [BranchController::class, 'bulkDestroy'])->middleware('permission:programmes.manage')->name('branches.bulk-destroy');`r`n`r`n        Route::resource('branches', BranchController::class)->except('show')"
    },
    @{
        Needle="        Route::resource('cohorts', CohortController::class)->except('show')"
        Insert="        Route::delete('/cohorts/bulk-delete', [CohortController::class, 'bulkDestroy'])->middleware('permission:cohorts.manage')->name('cohorts.bulk-destroy');`r`n`r`n        Route::resource('cohorts', CohortController::class)->except('show')"
    }
)

foreach($item in $replacements){
    $routeName = ($item.Insert -split "name\('")[1] -split "'\)" | Select-Object -First 1
    if($r -notmatch [regex]::Escape($routeName)){
        $r=$r.Replace($item.Needle,$item.Insert)
    }
}

# Remove duplicate simple admin dashboard route if still present.
$r=[regex]::Replace(
    $r,
    "\s*Route::view\('/dashboard',\s*'admin\.dashboard'\)->name\('dashboard'\);\r?\n",
    "`r`n"
)

[IO.File]::WriteAllText($phase2,$r,$utf8)

# Ensure all admin full pages inherit sidebar layout.
Get-ChildItem "$ProjectPath\resources\views\admin" -Recurse -Filter "*.blade.php" | ForEach-Object {
    if($_.FullName -match "\\partials\\|\\components\\"){ return }
    $text=[IO.File]::ReadAllText($_.FullName)
    $updated=$text.Replace("@extends('layouts.app')","@extends('layouts.admin')")
    $updated=$updated.Replace('@extends("layouts.app")','@extends("layouts.admin")')
    if($updated -ne $text){
        [IO.File]::WriteAllText($_.FullName,$updated,$utf8)
    }
}

php artisan view:clear
php artisan optimize:clear

Write-Host "Checking routes..." -ForegroundColor Cyan
php artisan route:list --name=admin.programmes
php artisan route:list --name=admin.projects
php artisan route:list --name=admin.branches
php artisan route:list --name=admin.cohorts

Write-Host "Building assets..." -ForegroundColor Cyan
npm run build

Write-Host "Running tests..." -ForegroundColor Cyan
php artisan test

Write-Host ""
Write-Host "DONE. Programme, Project, Branch and Cohort administration now use modal CRUD." -ForegroundColor Green
Write-Host "Hard refresh with Ctrl+F5." -ForegroundColor Yellow
