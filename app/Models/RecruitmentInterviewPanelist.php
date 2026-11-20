<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** A staff member invited to sit on an interview panel and score the candidate. */
class RecruitmentInterviewPanelist extends Model
{
    public const RECOMMENDATIONS = [
        'hire' => 'Recommend to hire',
        'maybe' => 'Possible / second interview',
        'no_hire' => 'Do not hire',
    ];

    protected $fillable = ['interview_id', 'user_id', 'is_chair', 'recommendation', 'overall_comment', 'total_score', 'submitted_at'];

    protected $casts = ['is_chair' => 'boolean', 'submitted_at' => 'datetime', 'total_score' => 'decimal:2'];

    public function interview() { return $this->belongsTo(RecruitmentInterview::class, 'interview_id'); }
    public function user() { return $this->belongsTo(User::class); }
    public function scores() { return $this->hasMany(RecruitmentInterviewScore::class, 'panelist_id'); }
}
