<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class ResultsFramework extends Model
{
    protected $fillable=['programme_id','project_id','title','description'];
    public function results(){ return $this->hasMany(Result::class); }
}
