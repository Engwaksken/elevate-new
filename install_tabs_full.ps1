param([string]$ProjectPath="D:\projects\elevate_her")
$ErrorActionPreference="Stop"
$PackageRoot=Split-Path -Parent $MyInvocation.MyCommand.Path
Set-Location $ProjectPath
$utf8=New-Object System.Text.UTF8Encoding($false)
$backup=Join-Path $ProjectPath ("storage\app\tabs-backup-"+(Get-Date -Format "yyyyMMdd-HHmmss"))
New-Item -ItemType Directory -Path $backup -Force|Out-Null

$targets=@(
"resources\views\dashboard.blade.php",
"resources\views\learning\my-courses.blade.php",
"resources\views\mentorship\dashboard.blade.php",
"resources\views\jobs\index.blade.php",
"resources\views\jobs\applications.blade.php",
"resources\views\library\index.blade.php",
"resources\views\calendar\index.blade.php",
"resources\views\notifications\index.blade.php",
"resources\views\profile\edit.blade.php"
)

foreach($r in $targets){$dst=Join-Path $ProjectPath $r;if(Test-Path $dst){$bn=($r-replace '[\\/:*?"<>|]','_');Copy-Item $dst (Join-Path $backup $bn) -Force};$src=Join-Path $PackageRoot ("files\"+$r);if(Test-Path $src){$dir=Split-Path $dst -Parent;if(!(Test-Path $dir)){New-Item -ItemType Directory -Path $dir -Force|Out-Null};$c=[IO.File]::ReadAllText($src);[IO.File]::WriteAllText($dst,$c,$utf8);Write-Host "UPDATED $r" -ForegroundColor Green}}

# CSS
$tabsCssSrc=Join-Path $PackageRoot "files\resources\css\participant-tabs.css"
$tabsCssDst=Join-Path $ProjectPath "resources\css\participant-tabs.css"
[IO.File]::WriteAllText($tabsCssDst,[IO.File]::ReadAllText($tabsCssSrc),$utf8)
$appCss=Join-Path $ProjectPath "resources\css\app.css"
$css=[IO.File]::ReadAllText($appCss)
if($css -notmatch "participant-tabs\.css"){$css="@import './participant-tabs.css';`r`n"+$css;[IO.File]::WriteAllText($appCss,$css,$utf8);Write-Host "IMPORTED participant-tabs.css" -ForegroundColor Green}

# JS append marker, preserving sidebar logic
$appJs=Join-Path $ProjectPath "resources\js\app.js"
$js=[IO.File]::ReadAllText($appJs)
if($js -notmatch "EH360 PARTICIPANT TABS"){$tabJs=[IO.File]::ReadAllText((Join-Path $PackageRoot "files\resources\js\participant-tabs.js"));$js=$js.TrimEnd()+"`r`n`r`n// EH360 PARTICIPANT TABS`r`n"+$tabJs+"`r`n";[IO.File]::WriteAllText($appJs,$js,$utf8);Write-Host "UPDATED resources\js\app.js" -ForegroundColor Green}

# BOM audit
$bad=@();Get-ChildItem $ProjectPath -Recurse -File|Where-Object{$_.Extension -eq '.php' -or $_.Name -like '*.blade.php'}|ForEach-Object{$b=[IO.File]::ReadAllBytes($_.FullName);if($b.Length -ge 3 -and $b[0]-eq239 -and $b[1]-eq187 -and $b[2]-eq191){$bad+=$_.FullName}}
if($bad.Count){Write-Host "WARNING: BOM detected in:" -ForegroundColor Yellow;$bad|ForEach-Object{Write-Host $_ -ForegroundColor Yellow}}else{Write-Host "No UTF-8 BOM detected." -ForegroundColor Green}

php artisan optimize:clear
npm run build
php artisan route:list --name=dashboard
php artisan route:list --name=learning
php artisan route:list --name=mentorship
php artisan route:list --name=jobs
php artisan route:list --name=library
php artisan route:list --name=calendar
php artisan route:list --name=notifications
php artisan route:list --name=profile
Write-Host "DONE. Backups saved in $backup" -ForegroundColor Cyan
