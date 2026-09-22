<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CourseModule extends Model
{
    protected $fillable = ['course_id','title','description','position','is_published'];
    protected $casts = ['is_published'=>'boolean'];

    public function course(){ return $this->belongsTo(Course::class); }
    public function lessons(){ return $this->hasMany(Lesson::class)->orderBy('position'); }
}
