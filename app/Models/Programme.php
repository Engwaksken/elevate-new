<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
class Programme extends Model {
    protected $fillable=['name','code','description','start_date','end_date','status','progress_percent'];
    protected $casts=['start_date'=>'date','end_date'=>'date','progress_percent'=>'decimal:2'];
    public function projects(): HasMany { return $this->hasMany(Project::class); }
    public function cohorts(): HasMany { return $this->hasMany(Cohort::class); }
    public function targets(): HasMany { return $this->hasMany(ProgrammeTarget::class); }

    public function recalculateProgress(): float
    {
        $targets = $this->targets()->get();

        if ($targets->isEmpty()) {
            $this->forceFill(['progress_percent' => 0])->saveQuietly();
            return 0.0;
        }

        $weighted = 0.0;
        $weightSum = 0.0;
        foreach ($targets as $target) {
            $weight = max(0.01, (float) $target->weight);
            $weighted += (float) $target->progress_percent * $weight;
            $weightSum += $weight;
        }

        $progress = $weightSum > 0 ? round($weighted / $weightSum, 2) : 0.0;
        $this->forceFill(['progress_percent' => $progress])->saveQuietly();

        return $progress;
    }
}
