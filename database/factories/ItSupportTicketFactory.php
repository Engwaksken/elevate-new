<?php

namespace Database\Factories;

use App\Models\ItSupportTicket;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ItSupportTicket>
 */
class ItSupportTicketFactory extends Factory
{
    protected $model = ItSupportTicket::class;

    public function definition(): array
    {
        return [
            'subject' => fake()->sentence(6),
            'description' => fake()->paragraph(),
            'category' => fake()->randomElement(['general', 'access', 'learning', 'procurement', 'other']),
            'priority' => fake()->randomElement(['low', 'normal', 'high', 'urgent']),
            'status' => 'open',
            'requester_id' => User::factory(),
            'assignee_id' => null,
            'status_updated_at' => null,
        ];
    }
}
