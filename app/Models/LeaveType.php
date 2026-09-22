<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class LeaveType extends Model
{
    protected $fillable=['name','code','default_days','requires_attachment','is_paid','is_active'];
    protected $casts=['default_days'=>'decimal:2','requires_attachment'=>'boolean','is_paid'=>'boolean','is_active'=>'boolean'];
}
