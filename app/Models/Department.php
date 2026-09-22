<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Department extends Model
{
    protected $fillable=['name','code','head_user_id','is_active'];
    protected $casts=['is_active'=>'boolean'];
}
