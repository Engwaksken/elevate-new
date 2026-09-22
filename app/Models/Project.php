<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class Project extends Model {
    protected $fillable=['programme_id','name','code','description','start_date','end_date','status'];
    protected $casts=['start_date'=>'date','end_date'=>'date'];
    public function programme(): BelongsTo { return $this->belongsTo(Programme::class); }
}
