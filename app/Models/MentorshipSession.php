<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MentorshipSession extends Model
{
    protected $fillable = [
        'mentor_match_id','title','agenda','scheduled_at','duration_minutes','meeting_link',
        'venue','status','mentor_attended','mentee_attended','session_notes',
        'agreed_actions','next_session_at'
    ];
    protected $casts = [
        'scheduled_at'=>'datetime','next_session_at'=>'datetime',
        'mentor_attended'=>'boolean','mentee_attended'=>'boolean'
    ];
    public function match(){ return $this->belongsTo(MentorMatch::class,'mentor_match_id'); }
}
