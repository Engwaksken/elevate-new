<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class IndicatorResult extends Model
{
    protected $fillable=['indicator_id','indicator_target_id','reporting_period','actual_numeric','actual_text','data_source','evidence_note','verification_status','entered_by','verified_by','verified_at'];
    protected $casts=['actual_numeric'=>'decimal:4','verified_at'=>'datetime'];
    public function indicator(){ return $this->belongsTo(Indicator::class); }
    public function target(){ return $this->belongsTo(IndicatorTarget::class,'indicator_target_id'); }
}
