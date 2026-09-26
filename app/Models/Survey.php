<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Survey extends Model {
    protected $fillable=['title','description','slug','access_type','course_id','cohort_id','programme_id','project_id','event_id','allow_draft','anonymous_allowed','response_limit','opens_at','closes_at','status','created_by'];
    protected $casts=['allow_draft'=>'boolean','anonymous_allowed'=>'boolean','opens_at'=>'datetime','closes_at'=>'datetime'];
    public function sections(){ return $this->hasMany(SurveySection::class)->orderBy('position'); }
    public function questions(){ return $this->hasMany(SurveyQuestion::class)->orderBy('position'); }
    public function responses(){ return $this->hasMany(SurveyResponse::class); }
    public function assignments(){ return $this->hasMany(SurveyAssignment::class); }
    public function course(){ return $this->belongsTo(Course::class); }
    public function cohort(){ return $this->belongsTo(Cohort::class); }
    public function isOpen(): bool { return $this->status==='published' && (!$this->opens_at || $this->opens_at->lte(now())) && (!$this->closes_at || $this->closes_at->gte(now())); }
}