<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ParticipantOutcome extends Model
{
    protected $fillable = [
        'user_id','programme_id','cohort_id','outcome_type','organisation_name','job_title',
        'outcome_date','income_amount','income_currency','notes','verification_status',
        'verified_by','verified_at'
    ];
    protected $casts = [
        'outcome_date'=>'date','income_amount'=>'decimal:2','verified_at'=>'datetime'
    ];
    public function user(){ return $this->belongsTo(User::class); }
}
