<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MentorshipSessionReport extends Model
{
    protected $fillable = [
        'mentorship_session_id', 'submitted_by', 'role', 'summary', 'challenges',
        'achievements', 'mentor_attended', 'mentee_attended', 'submitted_at',
    ];

    protected $casts = [
        'mentor_attended' => 'boolean',
        'mentee_attended' => 'boolean',
        'submitted_at' => 'datetime',
    ];

    public function session()
    {
        return $this->belongsTo(MentorshipSession::class, 'mentorship_session_id');
    }

    public function submitter()
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }
}
