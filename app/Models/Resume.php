<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Resume extends Model
{
    protected $fillable = [
        'user_id','title','template','professional_summary','source',
        'ai_enhanced','completion_percent','is_default',
    ];

    protected $casts = [
        'is_default'=>'boolean',
        'ai_enhanced'=>'boolean',
        'completion_percent'=>'integer',
    ];

    public function user(){ return $this->belongsTo(User::class); }
    public function experiences(){ return $this->hasMany(ResumeExperience::class)->orderBy('position'); }
    public function education(){ return $this->hasMany(ResumeEducation::class)->orderBy('position'); }
    public function skills(){ return $this->hasMany(ResumeSkill::class)->orderBy('position'); }
    public function certifications(){ return $this->hasMany(ResumeCertification::class); }
    public function languages(){ return $this->hasMany(ResumeLanguage::class); }
    public function projects(){ return $this->hasMany(ResumeProject::class); }
    public function uploads(){ return $this->hasMany(ResumeUpload::class); }
    public function versions(){ return $this->hasMany(ResumeVersion::class); }
    public function coverLetters(){ return $this->hasMany(CoverLetter::class); }
}
