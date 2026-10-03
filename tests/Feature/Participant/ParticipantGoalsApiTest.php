<?php

namespace Tests\Feature\Participant;

use App\Models\ParticipantGoal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ParticipantGoalsApiTest extends TestCase
{
    use RefreshDatabase;

    private const BASE = '/api/v1/participant';

    private function participant(): User
    {
        $user = User::factory()->create(['user_type' => 'participant', 'status' => 'active']);
        Sanctum::actingAs($user, ['participant']);

        return $user;
    }

    public function test_participant_can_create_and_list_goals(): void
    {
        $this->participant();

        $response = $this->postJson(self::BASE.'/goals', [
            'title' => 'Get an internship',
            'category' => 'career',
            'target_value' => 10,
            'current_value' => 3,
            'priority' => 'high',
        ]);

        $response->assertCreated()
            ->assertJsonPath('goal.title', 'Get an internship')
            ->assertJsonPath('goal.progress_percent', 30)
            ->assertJsonPath('goal.status', 'in_progress');

        $this->getJson(self::BASE.'/goals')
            ->assertOk()
            ->assertJsonCount(1, 'goals')
            ->assertJsonPath('summary.total', 1)
            ->assertJsonPath('summary.in_progress', 1)
            ->assertJsonPath('summary.average_progress', 30);
    }

    public function test_progress_endpoint_updates_and_completes_goal(): void
    {
        $user = $this->participant();

        $goal = $user->goals()->create([
            'title' => 'Read 4 books',
            'category' => 'learning',
            'target_value' => 4,
            'current_value' => 1,
        ]);

        $this->putJson(self::BASE."/goals/{$goal->id}/progress", ['current_value' => 4])
            ->assertOk()
            ->assertJsonPath('goal.progress_percent', 100)
            ->assertJsonPath('goal.status', 'completed');

        $this->assertNotNull($goal->fresh()->completed_at);
    }

    public function test_goal_requires_a_title(): void
    {
        $this->participant();

        $this->postJson(self::BASE.'/goals', ['category' => 'career'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('title');
    }

    public function test_a_participant_cannot_touch_another_participants_goal(): void
    {
        $owner = User::factory()->create(['user_type' => 'participant', 'status' => 'active']);
        $goal = $owner->goals()->create(['title' => 'Private goal', 'category' => 'personal']);

        $this->participant();

        $this->putJson(self::BASE."/goals/{$goal->id}", ['title' => 'Hijacked'])
            ->assertStatus(403);
        $this->deleteJson(self::BASE."/goals/{$goal->id}")->assertStatus(403);

        $this->assertSame('Private goal', $goal->fresh()->title);
    }

    public function test_participant_can_delete_own_goal(): void
    {
        $user = $this->participant();
        $goal = $user->goals()->create(['title' => 'Temporary goal', 'category' => 'other']);

        $this->deleteJson(self::BASE."/goals/{$goal->id}")->assertOk();

        $this->assertDatabaseMissing('participant_goals', ['id' => $goal->id]);
    }

    public function test_model_derives_progress_from_baseline_target_and_current(): void
    {
        $goal = new ParticipantGoal([
            'title' => 'Save money',
            'baseline_value' => 100,
            'target_value' => 1100,
            'current_value' => 350,
        ]);
        $goal->user_id = User::factory()->create(['user_type' => 'participant'])->id;
        $goal->save();

        $this->assertSame(25.0, (float) $goal->fresh()->progress_percent);
    }
}
