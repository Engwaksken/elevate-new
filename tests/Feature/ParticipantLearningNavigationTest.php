<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ParticipantLearningNavigationTest extends TestCase
{
    use RefreshDatabase;

    private function participant(): User
    {
        return User::factory()->create([
            'user_type' => 'participant',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);
    }

    public function test_participant_sidebar_links_to_her_own_learning(): void
    {
        $this->actingAs($this->participant());

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSee('My Learning')
            ->assertSee(route('learning.my-courses'))
            ->assertSee('Browse Courses');
    }

    public function test_home_learning_card_links_participants_to_my_learning(): void
    {
        $this->actingAs($this->participant());

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Go to My Learning')
            ->assertSee(route('learning.my-courses'));
    }

    public function test_guest_home_learning_card_points_to_the_catalogue(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Explore Learning')
            ->assertDontSee('Go to My Learning');
    }
}
