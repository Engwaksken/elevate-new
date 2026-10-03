<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
class Workplan extends Model
{
    protected $fillable=['programme_id','project_id','cohort_id','title','financial_year','period_type','start_date','end_date','responsible_user_id','description','status','progress_percent','created_by','approved_by','approved_at'];
    protected $casts=['start_date'=>'date','end_date'=>'date','approved_at'=>'datetime','progress_percent'=>'decimal:2'];

    public function milestones(): HasMany { return $this->hasMany(Milestone::class); }
    public function activities(): HasMany { return $this->hasMany(Activity::class); }
    public function approvals(): HasMany { return $this->hasMany(WorkplanApproval::class)->latest('acted_at'); }
    public function programme(): BelongsTo { return $this->belongsTo(Programme::class); }
    public function project(): BelongsTo { return $this->belongsTo(Project::class); }
    public function cohort(): BelongsTo { return $this->belongsTo(Cohort::class); }
    public function responsible(): BelongsTo { return $this->belongsTo(User::class, 'responsible_user_id'); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function approver(): BelongsTo { return $this->belongsTo(User::class, 'approved_by'); }

    public function canBeApproved(): bool
    {
        return in_array($this->status, ['submitted', 'under_review'], true);
    }
}
