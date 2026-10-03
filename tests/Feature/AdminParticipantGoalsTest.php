<?php

namespace Tests\Feature;

use App\Models\ParticipantGoal;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminParticipantGoalsTest extends TestCase
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

    private function admin(): User
    {
        $user = User::factory()->create(['user_type' => 'staff', 'status' => 'active']);
        $user->roles()->attach(Role::firstOrCreate(['slug' => 'super-administrator'], ['name' => 'Super Administrator']));

        return $user;
    }

    public function test_an_admin_can_see_every_participants_goals_and_progress(): void
    {
        $participant = $this->participant();
        ParticipantGoal::create([
            'user_id' => $participant->id,
            'title' => 'Get a data analyst internship',
            'category' => 'career',
            'target_value' => 10,
            'current_value' => 3,
        ]);

        $this->actingAs($this->admin())
            ->get(route('admin.participant-goals.index'))
            ->assertOk()
            ->assertSee('Get a data analyst internship')
            ->assertSee($participant->name);
    }

    public function test_a_participant_cannot_open_the_admin_goals_page(): void
    {
        $this->actingAs($this->participant())
            ->get(route('admin.participant-goals.index'))
            ->assertForbidden();
    }
}
