<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Result extends Model
{
    protected $fillable=['results_framework_id','parent_id','result_level','title','description'];
    public function framework(){ return $this->belongsTo(ResultsFramework::class,'results_framework_id'); }
}
