<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MentorProfile extends Model
{
    protected $fillable = [
        'user_id','organisation','job_title','industry','years_experience','professional_bio',
        'skills','languages','mentoring_areas','linkedin_url','country','availability',
        'status','approved_at','approved_by'
    ];
    protected $casts = [
        'skills'=>'array','languages'=>'array','mentoring_areas'=>'array','availability'=>'array',
        'approved_at'=>'datetime'
    ];
    public function user(){ return $this->belongsTo(User::class); }
}
