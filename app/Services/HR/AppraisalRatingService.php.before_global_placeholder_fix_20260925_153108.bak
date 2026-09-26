<?php

namespace App\Services\HR;

class AppraisalRatingService
{
    public function label(?float $percent): string
    {
        if ($percent === null) {
            return 'Not yet rated';
        }

        $bands = collect(config('appraisal.rating_bands', [
            ['min' => 90, 'label' => 'Outstanding'],
            ['min' => 80, 'label' => 'Exceeds Expectations'],
            ['min' => 60, 'label' => 'Meets Expectations'],
            ['min' => 40, 'label' => 'Partly Met Expectations'],
            ['min' => 0, 'label' => 'Unacceptable'],
        ]))
            ->sortByDesc(fn (array $band) => (float) ($band['min'] ?? 0))
            ->values();

        foreach ($bands as $band) {
            if ($percent >= (float) ($band['min'] ?? 0)) {
                return (string) ($band['label'] ?? 'Unrated');
            }
        }

        return 'Unacceptable';
    }
}
