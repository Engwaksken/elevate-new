<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RecruitmentInterviewScore extends Model
{
    protected $fillable = ['panelist_id', 'question_id', 'score', 'comment'];

    public function panelist() { return $this->belongsTo(RecruitmentInterviewPanelist::class, 'panelist_id'); }
    public function question() { return $this->belongsTo(RecruitmentInterviewQuestion::class, 'question_id'); }
}
