<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MentorMatch extends Model
{
    protected $fillable = [
        'mentor_user_id','mentee_user_id','programme_id','cohort_id',
        'start_date','end_date','status','matched_by'
    ];
    protected $casts = ['start_date'=>'date','end_date'=>'date'];
    public function mentor(){ return $this->belongsTo(User::class,'mentor_user_id'); }
    public function mentee(){ return $this->belongsTo(User::class,'mentee_user_id'); }
    public function sessions(){ return $this->hasMany(MentorshipSession::class); }
    public function goals(){ return $this->hasMany(MentorshipGoal::class); }
}
