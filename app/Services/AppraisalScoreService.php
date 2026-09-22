<?php
namespace App\Services;

use App\Models\Appraisal;

class AppraisalScoreService
{
    public function recalculate(Appraisal $appraisal): Appraisal
    {
        $objectives = $appraisal->objectives()->get();
        $weightSum = $objectives->sum(fn($o)=>(float)$o->weight);

        $self = null;
        $manager = null;

        if ($weightSum > 0 && $objectives->whereNotNull('self_rating')->isNotEmpty()) {
            $self = round($objectives->sum(fn($o)=>(float)$o->self_rating*(float)$o->weight)/$weightSum,2);
        }
        if ($weightSum > 0 && $objectives->whereNotNull('manager_rating')->isNotEmpty()) {
            $manager = round($objectives->sum(fn($o)=>(float)$o->manager_rating*(float)$o->weight)/$weightSum,2);
        }

        $appraisal->update([
            'self_score'=>$self,
            'manager_score'=>$manager,
            'final_score'=>$manager,
        ]);

        return $appraisal->fresh();
    }
}
