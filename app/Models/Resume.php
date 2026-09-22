<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Resume extends Model
{
    protected $fillable = ['user_id','title','template','professional_summary','is_default'];
    protected $casts = ['is_default'=>'boolean'];

    public function user(){ return $this->belongsTo(User::class); }
    public function experiences(){ return $this->hasMany(ResumeExperience::class)->orderBy('position'); }
    public function education(){ return $this->hasMany(ResumeEducation::class)->orderBy('position'); }
    public function skills(){ return $this->hasMany(ResumeSkill::class)->orderBy('position'); }
    public function certifications(){ return $this->hasMany(ResumeCertification::class); }
    public function languages(){ return $this->hasMany(ResumeLanguage::class); }
    public function projects(){ return $this->hasMany(ResumeProject::class); }
}
