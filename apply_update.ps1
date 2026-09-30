param([string]$ProjectPath="D:\projects\elevate_her")
$ErrorActionPreference="Stop"
$PackageRoot=Split-Path -Parent $MyInvocation.MyCommand.Path
$project=(Resolve-Path $ProjectPath).Path
$package=(Resolve-Path $PackageRoot).Path
if($project.TrimEnd('\') -eq $package.TrimEnd('\')){throw "Extract this update OUTSIDE the Laravel project."}
$utf8=New-Object System.Text.UTF8Encoding($false)
Get-ChildItem $package -Recurse -File | Where-Object {$_.Name -notin @('apply_update.ps1','README_UPDATE.txt')} | ForEach-Object { $rel=$_.FullName.Substring($package.Length+1);$target=Join-Path $project $rel;New-Item -ItemType Directory -Path (Split-Path $target) -Force|Out-Null;Copy-Item $_.FullName $target -Force;Write-Host "Updated $rel" }
$appraisalPath=Join-Path $project 'app\Models\Appraisal.php'
$appraisal=[IO.File]::ReadAllText($appraisalPath)
if($appraisal -notmatch "'achievements'"){$appraisal=$appraisal -replace "'hr_finalised_by',","'hr_finalised_by',`r`n        'achievements','challenges','support_required','learning_completed','development_needs','employee_final_comment','supervisor_final_comment','meeting_completed_at','employee_confirmed_at','supervisor_confirmed_at','locked_at','reopened_at','reopened_reason',"}
if($appraisal -notmatch 'function kras\('){$needle="    public function finalisedBy(){ return `$this->belongsTo(User::class,'hr_finalised_by'); }";$add="`r`n    public function kras(){ return `$this->hasMany(AppraisalKra::class)->orderBy('position'); }`r`n    public function competencies(){ return `$this->hasMany(AppraisalCompetency::class); }`r`n    public function meeting(){ return `$this->hasOne(AppraisalMeeting::class); }`r`n    public function statusHistory(){ return `$this->hasMany(AppraisalStatusHistory::class); }";$appraisal=$appraisal.Replace($needle,$needle+$add)}
[IO.File]::WriteAllText($appraisalPath,$appraisal,$utf8)
$webPath=Join-Path $project 'routes\web.php';$web=[IO.File]::ReadAllText($webPath);$line="require __DIR__.'/workspaces.php';";if($web -notmatch [regex]::Escape($line)){$web=$web.TrimEnd()+"`r`n`r`n"+$line+"`r`n";[IO.File]::WriteAllText($webPath,$web,$utf8)}
Write-Host 'Update files applied. Migration was NOT run automatically.'
Write-Host 'Next: php artisan migrate; php artisan optimize:clear'
