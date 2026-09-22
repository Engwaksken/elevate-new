<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Employee extends Model
{
    protected $fillable=['user_id','employee_number','department_id','position_id','supervisor_user_id','employment_type','work_location','start_date','probation_end_date','status','emergency_contact_name','emergency_contact_phone'];
    protected $casts=['start_date'=>'date','probation_end_date'=>'date'];
    public function user(){ return $this->belongsTo(User::class); }
    public function contracts(){ return $this->hasMany(EmploymentContract::class); }
    public function leaveRequests(){ return $this->hasMany(LeaveRequest::class); }
    public function appraisals(){ return $this->hasMany(Appraisal::class); }
}
