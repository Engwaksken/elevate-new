<?php

namespace Database\Factories;

use App\Models\DailyItQueueReview;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DailyItQueueReview>
 */
class DailyItQueueReviewFactory extends Factory
{
    protected $model = DailyItQueueReview::class;

    public function definition(): array
    {
        return [
            'run_date' => fake()->unique()->date(),
        ];
    }
}
