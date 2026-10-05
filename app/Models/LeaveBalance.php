<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class LeaveBalance extends Model
{
    protected $fillable=['employee_id','leave_type_id','year','opening_balance','accrued','used','adjustments','remaining'];
    protected $casts=['opening_balance'=>'decimal:2','accrued'=>'decimal:2','used'=>'decimal:2','adjustments'=>'decimal:2','remaining'=>'decimal:2'];
    public function employee(): \Illuminate\Database\Eloquent\Relations\BelongsTo { return $this->belongsTo(\App\Models\Employee::class); }
    public function leaveType(): \Illuminate\Database\Eloquent\Relations\BelongsTo { return $this->belongsTo(\App\Models\LeaveType::class); }
}
