<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
class Branch extends Model {
    protected $fillable=['name','code','district','country','is_active'];
    protected $casts=['is_active'=>'boolean'];
    public function cohorts(): BelongsToMany { return $this->belongsToMany(Cohort::class); }
}
