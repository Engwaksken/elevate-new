<?php
namespace App\Http\Controllers\Api\V1\Participant;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
final class SyncController extends Controller {
 public function pull(Request $request): JsonResponse {
  $request->validate(['since'=>['nullable','date']]); $user=$request->user(); abort_unless($user,401); $participant=$user->participant??null; abort_unless($participant,403,'Participant profile not found.');
  $since=$request->filled('since')?Carbon::parse($request->query('since')):Carbon::createFromTimestampUTC(0);
  return response()->json(['data'=>['server_time'=>now()->toIso8601String(),'courses'=>$this->rows('courses',$since),'assignments'=>$this->rows('assignments',$since),'events'=>$this->rows('events',$since),'announcements'=>$this->rows('announcements',$since)]]);
 }
 private function rows(string $table,Carbon $since): array { if(!Schema::hasTable($table)||!Schema::hasColumn($table,'updated_at')) return []; return DB::table($table)->where('updated_at','>',$since)->orderBy('updated_at')->limit(500)->get()->map(fn($r)=>(array)$r)->all(); }
}
