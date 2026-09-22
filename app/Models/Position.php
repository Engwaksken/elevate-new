<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Position extends Model
{
    protected $fillable=['department_id','title','grade','description','is_active'];
    protected $casts=['is_active'=>'boolean'];
}
