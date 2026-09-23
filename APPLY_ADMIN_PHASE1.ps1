param([string]$ProjectPath="D:\projects\elevate_her")
$ErrorActionPreference="Stop"
Set-Location $ProjectPath
$utf8=New-Object System.Text.UTF8Encoding($false)

Write-Host "Applying ElevateHer360 Admin Phase 1..." -ForegroundColor Cyan

# Import new CSS exactly once.
$cssPath=Join-Path $ProjectPath "resources\css\app.css"
$css=[IO.File]::ReadAllText($cssPath)
$imports=""
if($css -notmatch "auth-feedback.css"){$imports+="@import './auth-feedback.css';`r`n"}
if($css -notmatch "admin.css"){$imports+="@import './admin.css';`r`n"}
if($imports -ne ""){[IO.File]::WriteAllText($cssPath,$imports+$css,$utf8)}

# Remove the duplicate phase2 admin dashboard view route so phase3 DashboardController is authoritative.
$phase2=Join-Path $ProjectPath "routes\web.phase2.php"
if(Test-Path $phase2){
    $r=[IO.File]::ReadAllText($phase2)
    $r=$r.Replace("        Route::view('/dashboard', 'admin.dashboard')->name('dashboard');`r`n","")
    $r=$r.Replace("        Route::view('/dashboard', 'admin.dashboard')->name('dashboard');`n","")
    [IO.File]::WriteAllText($phase2,$r,$utf8)
}

# Ensure registration renders validation/session feedback inside the form panel.
$register=Join-Path $ProjectPath "resources\views\auth\register.blade.php"
if(Test-Path $register){
    $r=[IO.File]::ReadAllText($register)
    if($r -notmatch "partials.form-feedback"){
        $needle="        <form`r`n            method=`"POST`"`r`n            action=`"{{ route('register.store') }}`"`r`n            id=`"registrationForm`"`r`n        >"
        if($r.Contains($needle)){
            $r=$r.Replace($needle,"        @include('partials.form-feedback')`r`n`r`n"+$needle)
        } else {
            $needle2="        <form`n            method=`"POST`"`n            action=`"{{ route('register.store') }}`"`n            id=`"registrationForm`"`n        >"
            if($r.Contains($needle2)){$r=$r.Replace($needle2,"        @include('partials.form-feedback')`n`n"+$needle2)}
        }
        [IO.File]::WriteAllText($register,$r,$utf8)
    }
}

php artisan optimize:clear
npm run build
php artisan route:list --name=admin.dashboard
php artisan route:list --name=admin.users
php artisan test

Write-Host ""
Write-Host "DONE: Admin Phase 1 applied." -ForegroundColor Green
Write-Host "Check /login, /register, /admin/login, /admin/dashboard and /admin/users" -ForegroundColor Yellow
