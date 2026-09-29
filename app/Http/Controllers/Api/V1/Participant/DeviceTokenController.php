<?php
namespace App\Http\Controllers\Api\V1\Participant;
use App\Http\Controllers\Controller;
use App\Models\ParticipantDevice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
final class DeviceTokenController extends Controller {
 public function store(Request $request): JsonResponse {
  $d=$request->validate(['device_id'=>['required','string','max:191'],'fcm_token'=>['required','string'],'platform'=>['required','in:android,ios'],'app_version'=>['nullable','string','max:50']]);
  $device=ParticipantDevice::updateOrCreate(['user_id'=>$request->user()->getKey(),'device_id'=>$d['device_id']],['fcm_token'=>$d['fcm_token'],'platform'=>$d['platform'],'app_version'=>$d['app_version']??null,'notifications_enabled'=>true,'last_active_at'=>now()]);
  return response()->json(['data'=>$device]);
 }
 public function destroy(Request $request): JsonResponse {
  $d=$request->validate(['device_id'=>['required','string','max:191']]);
  ParticipantDevice::where('user_id',$request->user()->getKey())->where('device_id',$d['device_id'])->delete(); return response()->json(['message'=>'Device token removed.']);
 }
}
