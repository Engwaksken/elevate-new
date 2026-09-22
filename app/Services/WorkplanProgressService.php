<?php
namespace App\Services;

use App\Models\Workplan;

class WorkplanProgressService
{
    public function recalculate(Workplan $workplan): Workplan
    {
        $milestones = $workplan->milestones()->get();

        if ($milestones->isEmpty()) {
            $workplan->update(['progress_percent'=>0]);
            return $workplan->fresh();
        }

        $weightedTotal = 0;
        $weightSum = 0;

        foreach ($milestones as $milestone) {
            $weight = max(0.01,(float)$milestone->weight);
            $weightedTotal += ((float)$milestone->progress_percent) * $weight;
            $weightSum += $weight;
        }

        $progress = $weightSum > 0 ? round($weightedTotal/$weightSum,2) : 0;
        $workplan->update(['progress_percent'=>$progress]);

        return $workplan->fresh();
    }
}
