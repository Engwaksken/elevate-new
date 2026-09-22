<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
class Programme extends Model {
    protected $fillable=['name','code','description','start_date','end_date','status'];
    protected $casts=['start_date'=>'date','end_date'=>'date'];
    public function projects(): HasMany { return $this->hasMany(Project::class); }
    public function cohorts(): HasMany { return $this->hasMany(Cohort::class); }
}
