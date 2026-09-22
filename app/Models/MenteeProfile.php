<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MenteeProfile extends Model
{
    protected $fillable = [
        'user_id','programme_id','cohort_id','career_goals','skills',
        'support_needs','preferred_mentor_areas','availability'
    ];
    protected $casts = [
        'skills'=>'array','support_needs'=>'array','preferred_mentor_areas'=>'array','availability'=>'array'
    ];
    public function user(){ return $this->belongsTo(User::class); }
}
