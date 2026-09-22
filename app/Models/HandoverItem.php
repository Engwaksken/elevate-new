<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class HandoverItem extends Model
{
    protected $fillable=['staff_exit_id','category','title','details','assigned_to','status'];
}
