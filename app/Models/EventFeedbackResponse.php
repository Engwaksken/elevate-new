<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventFeedbackResponse extends Model
{
    protected $fillable=[
        'event_id','event_registration_id','user_id','overall_rating','relevance_rating',
        'facilitation_rating','organisation_rating','recommend_rating','key_learning',
        'what_worked','what_to_improve','additional_comments','submitted_at'
    ];

    protected $casts=['submitted_at'=>'datetime'];

    public function event(){ return $this->belongsTo(Event::class); }
    public function user(){ return $this->belongsTo(User::class); }
    public function registration(){ return $this->belongsTo(EventRegistration::class,'event_registration_id'); }
}
