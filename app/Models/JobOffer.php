<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JobOffer extends Model
{
    protected $fillable = [
        'job_application_id','salary_amount','salary_currency','start_date',
        'offer_notes','status','responded_at'
    ];
    protected $casts = ['start_date'=>'date','responded_at'=>'datetime','salary_amount'=>'decimal:2'];
    public function application(){ return $this->belongsTo(JobApplication::class,'job_application_id'); }
}
