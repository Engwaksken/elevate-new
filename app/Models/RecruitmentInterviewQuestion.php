<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** A question the interview panel scores for a vacancy. */
class RecruitmentInterviewQuestion extends Model
{
    protected $fillable = ['vacancy_id', 'question', 'guidance', 'max_score', 'weight', 'position'];

    public function vacancy() { return $this->belongsTo(RecruitmentVacancy::class, 'vacancy_id'); }
}
