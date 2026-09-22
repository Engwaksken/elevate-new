param([string]$ProjectPath="D:\projects\elevate_her")
$ErrorActionPreference="Stop"
$src=Split-Path -Parent $MyInvocation.MyCommand.Path
Set-Location $ProjectPath
$u=New-Object System.Text.UTF8Encoding($false)
$backup=Join-Path $ProjectPath "storage\app\sidebar-update-backup"
New-Item -ItemType Directory -Path $backup -Force | Out-Null

$targets=@(
"resources\views\layouts\app.blade.php",
"resources\views\partials\participant-sidebar.blade.php",
"resources\css\participant-sidebar.css",
"resources\js\app.js"
)
foreach($r in $targets){
  $dst=Join-Path $ProjectPath $r
  if(Test-Path $dst){Copy-Item $dst (Join-Path $backup (($r-replace '[\\/:*?\"<>|]','_'))) -Force}
  $dir=Split-Path $dst -Parent;if(!(Test-Path $dir)){New-Item -ItemType Directory -Path $dir -Force|Out-Null}
  $content=[IO.File]::ReadAllText((Join-Path $src $r))
  [IO.File]::WriteAllText($dst,$content,$u)
  Write-Host "UPDATED $r" -ForegroundColor Green
}

$appCss=Join-Path $ProjectPath "resources\css\app.css"
$css=[IO.File]::ReadAllText($appCss)
$import="@import './participant-sidebar.css';"
if($css -notmatch [regex]::Escape($import)){
  $needle="@import 'tailwindcss';"
  if($css.Contains($needle)){$css=$css.Replace($needle,$needle+"`r`n"+$import)}else{$css=$import+"`r`n"+$css}
  [IO.File]::WriteAllText($appCss,$css,$u)
  Write-Host "UPDATED resources\css\app.css import" -ForegroundColor Green
}

Write-Host "Checking BOM..." -ForegroundColor Cyan
$bad=@()
Get-ChildItem $ProjectPath -Recurse -File | Where-Object {$_.Extension -eq '.php' -or $_.Name -like '*.blade.php'} | ForEach-Object {
  $b=[IO.File]::ReadAllBytes($_.FullName)
  if($b.Length -ge 3 -and $b[0]-eq239 -and $b[1]-eq187 -and $b[2]-eq191){$bad+=$_.FullName}
}
if($bad.Count){$bad|ForEach-Object{Write-Host "BOM: $_" -ForegroundColor Red}}else{Write-Host "No BOM found." -ForegroundColor Green}

php artisan optimize:clear
npm run build
php artisan route:list --name=dashboard
php artisan route:list --name=notifications
php artisan route:list --name=profile
Write-Host "Sidebar update complete. Backups: storage\app\sidebar-update-backup" -ForegroundColor Green
