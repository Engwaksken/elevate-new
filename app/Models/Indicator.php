<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Indicator extends Model
{
    protected $fillable=['programme_id','project_id','result_id','name','code','definition','result_level','indicator_type','unit_of_measure','baseline_numeric','baseline_text','frequency','data_source','means_of_verification','responsible_user_id','disaggregation','start_date','end_date','status','calculation_key'];
    protected $casts=['baseline_numeric'=>'decimal:4','disaggregation'=>'array','start_date'=>'date','end_date'=>'date'];
    public function targets(){ return $this->hasMany(IndicatorTarget::class); }
    public function results(){ return $this->hasMany(IndicatorResult::class); }
}
