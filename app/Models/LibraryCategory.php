<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LibraryCategory extends Model
{
    protected $fillable=['name','slug','description','is_active'];
    protected $casts=['is_active'=>'boolean'];
    public function resources(){ return $this->hasMany(LibraryResource::class); }
}
