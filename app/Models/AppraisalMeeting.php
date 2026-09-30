<?php
namespace App\Models; use Illuminate\Database\Eloquent\Model;
class AppraisalMeeting extends Model {protected $fillable=['appraisal_id','meeting_at','venue','participants','discussion_notes','disagreements','agreed_actions','development_commitments','employee_comments','supervisor_comments','recorded_by']; protected $casts=['meeting_at'=>'datetime']; public function appraisal(){return $this->belongsTo(Appraisal::class);}}
