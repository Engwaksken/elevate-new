<?php
namespace App\Services\HR; use App\Models\Appraisal; use App\Models\AppraisalStatusHistory; use Illuminate\Support\Facades\DB;
class AppraisalWorkflowService {
    /** Statuses used by the KRA/KPI workspace workflow (staff.performance.*). */
    public const WORKSPACE_STATUSES=['draft','in_progress','returned_for_revision','submitted','supervisor_review','meeting_pending','meeting_completed','employee_confirmation','supervisor_confirmation'];
    public function transition(Appraisal $a,string $to,?string $comment=null):Appraisal{return DB::transaction(function()use($a,$to,$comment){$from=$a->status;$a->update(['status'=>$to]);$this->record($a,$from,$to,$comment);return $a->fresh();});}
    public function note(Appraisal $a,string $comment):void{$this->record($a,$a->status,$a->status,$comment);}
    private function record(Appraisal $a,?string $from,string $to,?string $comment):void{AppraisalStatusHistory::create(['appraisal_id'=>$a->id,'from_status'=>$from,'to_status'=>$to,'comment'=>$comment,'changed_by'=>auth()->id(),'ip_address'=>request()->ip()]);}
}
