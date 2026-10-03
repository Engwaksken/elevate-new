<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SelfEnrolmentAssessmentGateTest extends TestCase
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

    private function courseWithEntryAssessment(): array
    {
        $course = Course::create([
            'title' => 'Digital Marketing',
            'delivery_mode' => 'online',
            'pass_mark' => 50,
            'self_enrolment_enabled' => true,
            'status' => 'published',
        ]);

        $assessment = Assessment::create([
            'course_id' => $course->id,
            'title' => 'Entry assessment',
            'type' => 'quiz',
            'max_attempts' => 1,
            'is_published' => true,
        ]);

        $course->update(['entry_assessment_id' => $assessment->id]);

        return [$course->fresh(), $assessment];
    }

    public function test_self_enrolment_is_blocked_until_the_entry_assessment_is_finished(): void
    {
        [$course] = $this->courseWithEntryAssessment();
        $user = $this->participant();

        $this->actingAs($user)
            ->post(route('learning.enrol', $course))
            ->assertRedirect(route('learning.course.show', $course));

        $this->assertDatabaseMissing('enrolments', [
            'course_id' => $course->id,
            'user_id' => $user->id,
        ]);
    }

    public function test_self_enrolment_succeeds_after_finishing_the_entry_assessment(): void
    {
        [$course, $assessment] = $this->courseWithEntryAssessment();
        $user = $this->participant();

        AssessmentAttempt::create([
            'assessment_id' => $assessment->id,
            'user_id' => $user->id,
            'attempt_number' => 1,
            'status' => 'submitted',
            'started_at' => now(),
            'submitted_at' => now(),
        ]);

        $this->actingAs($user)
            ->post(route('learning.enrol', $course))
            ->assertRedirect(route('learning.my-courses'));

        $this->assertDatabaseHas('enrolments', [
            'course_id' => $course->id,
            'user_id' => $user->id,
        ]);
    }

    public function test_a_course_without_an_entry_assessment_enrols_immediately(): void
    {
        $course = Course::create([
            'title' => 'Open course',
            'delivery_mode' => 'online',
            'pass_mark' => 50,
            'self_enrolment_enabled' => true,
            'status' => 'published',
        ]);
        $user = $this->participant();

        $this->actingAs($user)
            ->post(route('learning.enrol', $course))
            ->assertRedirect(route('learning.my-courses'));

        $this->assertDatabaseHas('enrolments', [
            'course_id' => $course->id,
            'user_id' => $user->id,
        ]);
    }
}
