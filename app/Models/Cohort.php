<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class Cohort extends Model {
    protected $fillable=['programme_id','project_id','branch_id','name','code','start_date','end_date','status'];
    protected $casts=['start_date'=>'date','end_date'=>'date'];
    public function programme(): BelongsTo { return $this->belongsTo(Programme::class); }
    public function project(): BelongsTo { return $this->belongsTo(Project::class); }
    public function branch(): BelongsTo { return $this->belongsTo(Branch::class); }
}
