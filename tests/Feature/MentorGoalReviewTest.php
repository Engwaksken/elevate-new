<?php

namespace Tests\Feature;

use App\Models\MentorMatch;
use App\Models\ParticipantGoal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class MentorGoalReviewTest extends TestCase
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

    public function test_a_mentor_can_review_a_mentees_personal_goal(): void
    {
        Mail::fake();

        $mentor = $this->participant();
        $mentee = $this->participant();

        MentorMatch::create([
            'mentor_user_id' => $mentor->id,
            'mentee_user_id' => $mentee->id,
            'status' => 'active',
        ]);

        $goal = ParticipantGoal::create([
            'user_id' => $mentee->id,
            'title' => 'Find an internship',
            'category' => 'career',
        ]);

        $this->actingAs($mentor)
            ->post(route('mentorship.participant-goals.review', $goal), [
                'mentor_comment' => 'Great goal. Break it into weekly applications.',
            ])
            ->assertRedirect();

        $goal->refresh();
        $this->assertSame('Great goal. Break it into weekly applications.', $goal->mentor_comment);
        $this->assertSame($mentor->id, $goal->mentor_reviewed_by);
        $this->assertNotNull($goal->mentor_reviewed_at);

        $this->assertDatabaseHas('user_notifications', ['user_id' => $mentee->id, 'type' => 'goal_review']);
    }

    public function test_an_unrelated_user_cannot_review_the_goal(): void
    {
        $mentee = $this->participant();
        $stranger = $this->participant();

        $goal = ParticipantGoal::create([
            'user_id' => $mentee->id,
            'title' => 'Save money',
            'category' => 'personal',
        ]);

        $this->actingAs($stranger)
            ->post(route('mentorship.participant-goals.review', $goal), [
                'mentor_comment' => 'Not my mentee.',
            ])
            ->assertForbidden();

        $this->assertNull($goal->fresh()->mentor_comment);
    }
}
