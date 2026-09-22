<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class StaffExit extends Model
{
    protected $fillable=['employee_id','exit_type','notice_date','last_working_date','reason','destination_organisation','new_role','destination_sector','handover_user_id','status'];
    protected $casts=['notice_date'=>'date','last_working_date'=>'date'];
    public function employee(){ return $this->belongsTo(Employee::class); }
    public function clearances(){ return $this->hasMany(ExitClearance::class); }
    public function handoverItems(){ return $this->hasMany(HandoverItem::class); }
}
