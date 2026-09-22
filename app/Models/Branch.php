<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Branch extends Model {
    protected $fillable=['name','code','district','country','is_active'];
    protected $casts=['is_active'=>'boolean'];
}
