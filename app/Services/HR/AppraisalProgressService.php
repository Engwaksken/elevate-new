<?php

namespace App\Services\HR;

use App\Models\Appraisal;
use Illuminate\Support\Collection;

class AppraisalProgressService
{
    public function refresh(Appraisal $appraisal): Appraisal
    {
        $appraisal->loadMissing([
            'kpiTemplate.items',
            'kpiScores',
            'kpiWeeklyUpdates',
        ]);

        $self=$this->selfCompletion($appraisal);
        $manager=$this->managerCompletion($appraisal);
        $performance=$this->performancePercent($appraisal);

        $workflow=($self * .45) + ($manager * .45);

        if($appraisal->hr_finalised_at){
            $workflow += 5;
        }

        if($appraisal->employee_acknowledged_at){
            $workflow += 5;
        }

        $appraisal->forceFill([
            'completion_percent'=>round(min(100,$workflow),2),
            'performance_percent'=>$performance,
            'self_score'=>$this->averageRating($appraisal,'employee_rating'),
            'manager_score'=>$this->averageRating($appraisal,'manager_rating'),
            'final_score'=>$this->averageRating($appraisal,'agreed_rating'),
        ])->save();

        return $appraisal->fresh();
    }

    public function selfCompletion(Appraisal $appraisal): float
    {
        $items=$this->scoreableItems($appraisal);

        if($items->isEmpty()){
            return 0;
        }

        $scores=$appraisal->kpiScores->keyBy('hr_kpi_template_item_id');
        $complete=0;

        foreach($items as $item){
            $score=$scores->get($item->id);

            if(!$score){
                continue;
            }

            if($item->item_type==='okr'){
                if($score->okr_percent !== null){
                    $complete++;
                }
                continue;
            }

            if($score->employee_rating !== null){
                $complete++;
            }
        }

        return round(($complete / $items->count()) * 100,2);
    }

    public function managerCompletion(Appraisal $appraisal): float
    {
        $items=$this->scoreableItems($appraisal);

        if($items->isEmpty()){
            return 0;
        }

        $scores=$appraisal->kpiScores->keyBy('hr_kpi_template_item_id');
        $complete=0;

        foreach($items as $item){
            $score=$scores->get($item->id);

            if(!$score){
                continue;
            }

            if($item->item_type==='okr'){
                if(filled($score->manager_comment)){
                    $complete++;
                }
                continue;
            }

            if($score->manager_rating !== null && $score->agreed_rating !== null){
                $complete++;
            }
        }

        return round(($complete / $items->count()) * 100,2);
    }

    public function performancePercent(Appraisal $appraisal): ?float
    {
        $items=$this->scoreableItems($appraisal);

        if($items->isEmpty()){
            return null;
        }

        if($appraisal->kpiTemplate?->template_type==='okr_scorecard'){
            return $this->okrPerformance($appraisal,$items);
        }

        return $this->ratingPerformance($appraisal,$items);
    }

    public function ratingLabel(?float $percent): string
    {
        if($percent===null){
            return 'Not yet rated';
        }

        return match(true){
            $percent >= 90 => 'Outstanding',
            $percent >= 80 => 'Exceeds Expectations',
            $percent >= 60 => 'Meets Expectations',
            $percent >= 40 => 'Partly Met Expectations',
            default => 'Unacceptable',
        };
    }

    private function ratingPerformance(Appraisal $appraisal,Collection $items): ?float
    {
        $scores=$appraisal->kpiScores->keyBy('hr_kpi_template_item_id');

        $kraItems=$appraisal->kpiTemplate?->items
            ?->where('item_type','kra') ?? collect();

        if($kraItems->isNotEmpty()){
            $weighted=0;
            $usedWeight=0;

            foreach($kraItems as $kra){
                $children=$items->where('section',$kra->title);

                $values=$children->map(function($item) use($scores){
                    $score=$scores->get($item->id);
                    if(!$score) return null;

                    $value=$score->agreed_rating
                        ?? $score->manager_rating
                        ?? $score->employee_rating;

                    if($value===null) return null;

                    $max=$item->item_type==='behavioral' ? 3 : 5;

                    return ((float)$value / $max) * 100;
                })->filter(fn($value)=>$value!==null);

                if($values->isEmpty()){
                    continue;
                }

                $weight=(float)($kra->weight ?: 0);

                if($weight>0){
                    $weighted += $values->avg() * ($weight/100);
                    $usedWeight += $weight;
                }
            }

            if($usedWeight>0){
                return round($weighted / ($usedWeight/100),2);
            }
        }

        $values=$items->map(function($item) use($scores){
            $score=$scores->get($item->id);
            if(!$score) return null;

            $value=$score->agreed_rating
                ?? $score->manager_rating
                ?? $score->employee_rating;

            if($value===null) return null;

            $max=$item->item_type==='behavioral' ? 3 : 5;

            return ((float)$value / $max) * 100;
        })->filter(fn($value)=>$value!==null);

        return $values->isEmpty() ? null : round($values->avg(),2);
    }

    private function okrPerformance(Appraisal $appraisal,Collection $items): ?float
    {
        $scores=$appraisal->kpiScores->keyBy('hr_kpi_template_item_id');

        $weighted=0;
        $weightUsed=0;
        $unweighted=[];

        foreach($items as $item){
            $percent=$scores->get($item->id)?->okr_percent;

            if($percent===null){
                continue;
            }

            $weight=(float)($item->weight ?: 0);

            if($weight>0){
                $weighted += ((float)$percent) * ($weight/100);
                $weightUsed += $weight;
            } else {
                $unweighted[]=(float)$percent;
            }
        }

        if($weightUsed>0){
            return round($weighted / ($weightUsed/100),2);
        }

        return $unweighted ? round(array_sum($unweighted)/count($unweighted),2) : null;
    }

    private function averageRating(Appraisal $appraisal,string $field): ?float
    {
        $values=$appraisal->kpiScores
            ->pluck($field)
            ->filter(fn($value)=>$value!==null)
            ->map(fn($value)=>(float)$value);

        return $values->isEmpty() ? null : round($values->avg(),2);
    }

    private function scoreableItems(Appraisal $appraisal): Collection
    {
        return $appraisal->kpiTemplate?->items
            ?->whereIn('item_type',['kpi','okr','behavioral'])
            ->reject(fn($item)=>$item->item_type==='behavioral' && data_get($item->meta,'group'))
            ->values()
            ?? collect();
    }
}
