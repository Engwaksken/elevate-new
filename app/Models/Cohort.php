<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
class Cohort extends Model {
    protected $fillable=['programme_id','project_id','branch_id','name','code','start_date','end_date','status'];
    protected $casts=['start_date'=>'date','end_date'=>'date'];
    public function programme(): BelongsTo { return $this->belongsTo(Programme::class); }
    public function project(): BelongsTo { return $this->belongsTo(Project::class); }
    public function branch(): BelongsTo { return $this->belongsTo(Branch::class); }
    public function branches(): BelongsToMany { return $this->belongsToMany(Branch::class); }

    /** Cohorts that run at the given branch, via the pivot or the legacy branch_id column. */
    public function scopeAtBranch($query, $branchId)
    {
        return $query->where(fn ($q) => $q->where('branch_id', $branchId)
            ->orWhereHas('branches', fn ($b) => $b->whereKey($branchId)));
    }

    /** Branches this cohort runs at, from the pivot and the legacy branch_id column. */
    public function branchIds(): array
    {
        return $this->branches->pluck('id')->push($this->branch_id)->filter()->map(fn ($id) => (int) $id)->unique()->values()->all();
    }
}
