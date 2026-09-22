<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Job extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'employer_id','title','category','industry','location','country','employment_type',
        'work_arrangement','experience_level','education_level','salary_min','salary_max',
        'salary_currency','description','responsibilities','requirements','skills',
        'application_deadline','positions','status','published_at'
    ];
    protected $casts = [
        'skills'=>'array','application_deadline'=>'date','published_at'=>'datetime',
        'salary_min'=>'decimal:2','salary_max'=>'decimal:2'
    ];
    public function employer(){ return $this->belongsTo(Employer::class); }
    public function applications(){ return $this->hasMany(JobApplication::class); }
}
