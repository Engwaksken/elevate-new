param([string]$ProjectPath="D:\projects\elevate_her")

$ErrorActionPreference="Stop"
Set-Location $ProjectPath

$outDir=".\storage\app\phase26-full-system-uat-audit"
New-Item -ItemType Directory -Force -Path $outDir | Out-Null

Write-Host "Phase 26 - Full System UAT & Production Readiness Audit" -ForegroundColor Cyan

function Save-Command {
    param(
        [string]$Title,
        [string]$File,
        [scriptblock]$Command
    )

    "=== $Title ===" | Out-File "$outDir\$File" -Encoding utf8

    try {
        & $Command 2>&1 | Out-File "$outDir\$File" -Append -Encoding utf8
    } catch {
        $_ | Out-String | Out-File "$outDir\$File" -Append -Encoding utf8
    }
}

# ------------------------------------------------------------------
# Platform baseline
# ------------------------------------------------------------------
Save-Command "ARTISAN ABOUT" "artisan-about.txt" {
    php artisan about
}

Save-Command "MIGRATION STATUS" "migration-status.txt" {
    php artisan migrate:status
}

Save-Command "SCHEDULE LIST" "schedule-list.txt" {
    php artisan schedule:list
}

Save-Command "ALL ROUTES" "routes-all.txt" {
    php artisan route:list
}

# ------------------------------------------------------------------
# Feature-route snapshots
# ------------------------------------------------------------------
$featureRoutePatterns=@(
    "admin/course-calls",
    "admin/surveys",
    "admin/platform-settings",
    "participant/course-opportunities",
    "participant/surveys",
    "admin/events",
    "admin/hr",
    "admin/meal",
    "admin/assets",
    "admin/procurement",
    "admin/elearning",
    "admin/users",
    "admin/roles"
)

foreach($pattern in $featureRoutePatterns){
    $safe=($pattern -replace '[\\/:*?"<>|]','_')

    Save-Command "ROUTES: $pattern" ("routes_"+$safe+".txt") {
        php artisan route:list --path=$pattern
    }
}

# ------------------------------------------------------------------
# Duplicate route names / duplicate URI+method detection
# ------------------------------------------------------------------
$routeAudit=@'
<?php

require __DIR__.'/vendor/autoload.php';

$app=require_once __DIR__.'/bootstrap/app.php';
$kernel=$app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$routes=app('router')->getRoutes();

$nameMap=[];
$signatureMap=[];
$rows=[];

foreach($routes as $route){
    $name=$route->getName();
    $methods=implode(',',array_values(array_diff($route->methods(),['HEAD'])));
    $uri=$route->uri();
    $signature=$methods.' '.$uri;

    $rows[]=[
        'name'=>$name,
        'methods'=>$methods,
        'uri'=>$uri,
        'action'=>$route->getActionName(),
        'middleware'=>implode(',',$route->gatherMiddleware()),
    ];

    if($name){
        $nameMap[$name][]=$route;
    }

    $signatureMap[$signature][]=$route;
}

$out=[];
$out[]='DUPLICATE ROUTE NAMES';
$out[]='=====================';

$duplicateNames=0;

foreach($nameMap as $name=>$items){
    if(count($items)>1){
        $duplicateNames++;
        $out[]='';
        $out[]=$name;

        foreach($items as $route){
            $out[]='  '.implode(',',array_values(array_diff($route->methods(),['HEAD'])))
                .' '.$route->uri()
                .' => '.$route->getActionName();
        }
    }
}

if($duplicateNames===0){
    $out[]='None';
}

$out[]='';
$out[]='DUPLICATE METHOD + URI SIGNATURES';
$out[]='=================================';

$duplicateSignatures=0;

foreach($signatureMap as $signature=>$items){
    if(count($items)>1){
        $duplicateSignatures++;
        $out[]='';
        $out[]=$signature;

        foreach($items as $route){
            $out[]='  name='.($route->getName() ?: '(none)')
                .' => '.$route->getActionName();
        }
    }
}

if($duplicateSignatures===0){
    $out[]='None';
}

file_put_contents(
    __DIR__.'/storage/app/phase26-full-system-uat-audit/route-duplicates.txt',
    implode(PHP_EOL,$out)
);

file_put_contents(
    __DIR__.'/storage/app/phase26-full-system-uat-audit/routes.json',
    json_encode($rows,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES)
);
'@

$utf8=New-Object System.Text.UTF8Encoding($false)
[IO.File]::WriteAllText(".\phase26_route_audit.php",$routeAudit,$utf8)
php .\phase26_route_audit.php
Remove-Item ".\phase26_route_audit.php" -Force

# ------------------------------------------------------------------
# Database integrity / orphan audit
# ------------------------------------------------------------------
$dbAudit=@'
<?php

require __DIR__.'/vendor/autoload.php';

$app=require_once __DIR__.'/bootstrap/app.php';
$kernel=$app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

$out=[];
$out[]='PHASE 26 DATABASE INTEGRITY AUDIT';
$out[]='=================================';

$checks=[
    ['role_user','user_id','users','id'],
    ['role_user','role_id','roles','id'],
    ['permission_role','permission_id','permissions','id'],
    ['permission_role','role_id','roles','id'],

    ['enrolments','user_id','users','id'],
    ['enrolments','course_id','courses','id'],
    ['enrolments','cohort_id','cohorts','id'],

    ['course_call_course','course_call_id','course_calls','id'],
    ['course_call_course','course_id','courses','id'],
    ['course_applications','course_call_id','course_calls','id'],
    ['course_applications','user_id','users','id'],

    ['survey_questions','survey_id','surveys','id'],
    ['survey_sections','survey_id','surveys','id'],
    ['survey_responses','survey_id','surveys','id'],
    ['survey_responses','user_id','users','id'],
    ['survey_answers','survey_response_id','survey_responses','id'],
    ['survey_answers','survey_question_id','survey_questions','id'],

    ['certificates','course_id','courses','id'],
    ['certificates','user_id','users','id'],

    ['event_attendance_records','event_id','events','id'],
    ['event_attendance_records','user_id','users','id'],
];

foreach($checks as [$child,$fk,$parent,$pk]){
    if(
        !Schema::hasTable($child)
        || !Schema::hasTable($parent)
        || !Schema::hasColumn($child,$fk)
        || !Schema::hasColumn($parent,$pk)
    ){
        $out[]="$child.$fk -> $parent.$pk : SKIPPED";
        continue;
    }

    $count=DB::table("$child as c")
        ->leftJoin("$parent as p","c.$fk","=","p.$pk")
        ->whereNotNull("c.$fk")
        ->whereNull("p.$pk")
        ->count();

    $out[]="$child.$fk -> $parent.$pk : $count orphan(s)";
}

$out[]='';
$out[]='KEY COUNTS';
$out[]='----------';

foreach([
    'users',
    'courses',
    'enrolments',
    'course_calls',
    'course_call_course',
    'course_applications',
    'surveys',
    'survey_responses',
    'certificates',
    'events',
    'event_attendance_records',
    'platform_backups',
] as $table){
    if(Schema::hasTable($table)){
        $out[]="$table: ".DB::table($table)->count();
    }
}

file_put_contents(
    __DIR__.'/storage/app/phase26-full-system-uat-audit/database-integrity.txt',
    implode(PHP_EOL,$out)
);
'@

[IO.File]::WriteAllText(".\phase26_db_audit.php",$dbAudit,$utf8)
php .\phase26_db_audit.php
Remove-Item ".\phase26_db_audit.php" -Force

# ------------------------------------------------------------------
# Permission / access-control matrix
# ------------------------------------------------------------------
$permissionAudit=@'
<?php

require __DIR__.'/vendor/autoload.php';

$app=require_once __DIR__.'/bootstrap/app.php';
$kernel=$app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

$out=[];
$out[]='ROLE / PERMISSION MATRIX';
$out[]='========================';

if(!Schema::hasTable('roles') || !Schema::hasTable('permissions')){
    $out[]='Role/permission tables not found.';
}else{
    $roles=DB::table('roles')->orderBy('name')->get();

    foreach($roles as $role){
        $out[]='';
        $out[]='ROLE: '.$role->name.' ['.$role->slug.']';

        $permissions=DB::table('permissions as p')
            ->join('permission_role as pr','pr.permission_id','=','p.id')
            ->where('pr.role_id',$role->id)
            ->orderBy('p.module')
            ->orderBy('p.slug')
            ->pluck('p.slug');

        if($permissions->isEmpty()){
            $out[]='  (no permissions)';
        }else{
            foreach($permissions as $permission){
                $out[]='  - '.$permission;
            }
        }
    }
}

file_put_contents(
    __DIR__.'/storage/app/phase26-full-system-uat-audit/role-permission-matrix.txt',
    implode(PHP_EOL,$out)
);
'@

[IO.File]::WriteAllText(".\phase26_permissions.php",$permissionAudit,$utf8)
php .\phase26_permissions.php
Remove-Item ".\phase26_permissions.php" -Force

# ------------------------------------------------------------------
# Search for risky / unfinished implementation markers
# ------------------------------------------------------------------
Get-ChildItem .\app,.\routes,.\resources\views,.\config -Recurse -File -Include *.php,*.blade.php |
    Select-String -SimpleMatch -Pattern @(
        "TODO",
        "FIXME",
        "dd(",
        "dump(",
        "die(",
        "var_dump(",
        "Route::view('/admin/platform-settings'",
        "GET /logout",
        "Route::get('/logout'",
        "admin.login",
        "participant.login",
        "maintenance.enabled",
        "source_type",
        "course_call_course",
        "survey_responses",
        "platform_backups"
    ) |
    Select-Object Path,LineNumber,Line |
    Format-Table -Wrap -AutoSize |
    Out-String -Width 700 |
    Out-File "$outDir\risk-symbols.txt" -Encoding utf8

# ------------------------------------------------------------------
# Filesystem / storage / public link status
# ------------------------------------------------------------------
$storageReport=@()

$storageReport+="PUBLIC STORAGE LINK"
$storageReport+="==================="

if(Test-Path ".\public\storage"){
    $storageReport+="public/storage exists"
}else{
    $storageReport+="public/storage MISSING"
}

$storageReport+=""
$storageReport+="WRITABLE PATHS"
$storageReport+="=============="

foreach($path in @(
    ".\storage",
    ".\storage\app",
    ".\storage\framework",
    ".\storage\logs",
    ".\bootstrap\cache"
)){
    if(Test-Path $path){
        try{
            $probe=Join-Path $path ".phase26-write-test.tmp"
            [IO.File]::WriteAllText($probe,"ok",$utf8)
            Remove-Item $probe -Force
            $storageReport+="$path : writable"
        }catch{
            $storageReport+="$path : NOT writable"
        }
    }else{
        $storageReport+="$path : MISSING"
    }
}

$storageReport | Out-File "$outDir\storage-health.txt" -Encoding utf8

# ------------------------------------------------------------------
# Environment summary (secrets redacted)
# ------------------------------------------------------------------
if(Test-Path ".\.env"){
    Get-Content ".\.env" |
        Where-Object {
            $_ -match '^(APP_|DB_|QUEUE_|CACHE_|SESSION_|MAIL_|FILESYSTEM_|LOG_)'
        } |
        ForEach-Object {
            $line=$_

            if($line -match '(PASSWORD|SECRET|KEY|TOKEN)='){
                $name=($line -split "=",2)[0]
                "$name=***REDACTED***"
            }else{
                $line
            }
        } |
        Out-File "$outDir\environment-redacted.txt" -Encoding utf8
}

# ------------------------------------------------------------------
# Blade + PHP syntax
# ------------------------------------------------------------------
Save-Command "VIEW CACHE" "view-cache.txt" {
    php artisan view:clear
    php artisan view:cache
}

Get-ChildItem .\app,.\routes -Recurse -File -Filter *.php |
    ForEach-Object {
        $result=& php -l $_.FullName 2>&1

        if($LASTEXITCODE -ne 0){
            "$($_.FullName)`r`n$result`r`n" |
                Out-File "$outDir\php-syntax-errors.txt" -Append -Encoding utf8
        }
    }

if(-not (Test-Path "$outDir\php-syntax-errors.txt")){
    "No PHP syntax errors found in app/ or routes/." |
        Out-File "$outDir\php-syntax-errors.txt" -Encoding utf8
}

# ------------------------------------------------------------------
# Frontend build
# ------------------------------------------------------------------
Save-Command "NPM BUILD" "npm-build.txt" {
    npm run build
}

# ------------------------------------------------------------------
# Tests
# ------------------------------------------------------------------
Save-Command "FULL TEST SUITE" "tests.txt" {
    php artisan test
}

# ------------------------------------------------------------------
# Queue / failed jobs
# ------------------------------------------------------------------
$queueReport=@()
$queueReport+="QUEUE / FAILED JOBS"
$queueReport+="==================="

try{
    $queueReport+=(php artisan queue:failed 2>&1 | Out-String)
}catch{
    $queueReport+=$_.Exception.Message
}

$queueReport | Out-File "$outDir\queue-health.txt" -Encoding utf8

# ------------------------------------------------------------------
# Package
# ------------------------------------------------------------------
$zip=".\storage\app\phase26-full-system-uat-audit.zip"

if(Test-Path $zip){
    Remove-Item $zip -Force
}

Compress-Archive -Path "$outDir\*" -DestinationPath $zip -Force

Write-Host ""
Write-Host "DONE." -ForegroundColor Green
Write-Host "Upload this generated ZIP:" -ForegroundColor Yellow
Write-Host "$ProjectPath\storage\app\phase26-full-system-uat-audit.zip" -ForegroundColor Yellow
