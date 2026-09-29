<?php
namespace App\Http\Controllers\Api\V1\Participant;
use App\Http\Controllers\Controller;
use App\Models\MobileSyncAction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
final class OfflineActionController extends Controller {
 public function store(Request $request): JsonResponse {
  $v=$request->validate(['actions'=>['required','array','max:100'],'actions.*.client_action_id'=>['required','uuid'],'actions.*.type'=>['required','string','max:100'],'actions.*.payload'=>['required','array'],'actions.*.occurred_at'=>['nullable','date']]);
  $out=[]; foreach($v['actions'] as $a){ $r=MobileSyncAction::firstOrCreate(['user_id'=>$request->user()->getKey(),'client_action_id'=>$a['client_action_id']],['action_type'=>$a['type'],'payload'=>$a['payload'],'occurred_at'=>$a['occurred_at']??now(),'status'=>'received']); $out[]=['client_action_id'=>$r->client_action_id,'status'=>$r->status,'duplicate'=>!$r->wasRecentlyCreated]; }
  return response()->json(['data'=>$out]);
 }
}
