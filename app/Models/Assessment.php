<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Assessment extends Model
{
    protected $fillable = [
        'course_id','course_module_id','title','type','instructions','pass_mark',
        'max_attempts','opens_at','due_at','is_published'
    ];
    protected $casts = ['opens_at'=>'datetime','due_at'=>'datetime','is_published'=>'boolean'];

    public function questions(){ return $this->hasMany(AssessmentQuestion::class)->orderBy('position'); }
}
