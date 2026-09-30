<?php
namespace App\Services\HR; use App\Models\Appraisal; use App\Models\AppraisalStatusHistory; use Illuminate\Support\Facades\DB;
class AppraisalWorkflowService {public function transition(Appraisal $a,string $to,?string $comment=null):Appraisal{return DB::transaction(function()use($a,$to,$comment){$from=$a->status;$a->update(['status'=>$to]);AppraisalStatusHistory::create(['appraisal_id'=>$a->id,'from_status'=>$from,'to_status'=>$to,'comment'=>$comment,'changed_by'=>auth()->id(),'ip_address'=>request()->ip()]);return $a->fresh();});}}
