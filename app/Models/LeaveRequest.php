<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class LeaveRequest extends Model
{
    protected $fillable=['employee_id','leave_type_id','start_date','end_date','days_requested','reason','handover_user_id','attachment_path','status','supervisor_approved_by','supervisor_approved_at','hr_approved_by','hr_approved_at','decision_notes'];
    protected $casts=['start_date'=>'date','end_date'=>'date','days_requested'=>'decimal:2','supervisor_approved_at'=>'datetime','hr_approved_at'=>'datetime'];
    public function employee(){ return $this->belongsTo(Employee::class); }
    public function leaveType(){ return $this->belongsTo(LeaveType::class); }
}
