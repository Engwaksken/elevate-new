<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class ExitClearance extends Model
{
    protected $fillable=['staff_exit_id','clearance_area','responsible_user_id','status','remarks','cleared_at','cleared_by'];
    protected $casts=['cleared_at'=>'datetime'];
}
