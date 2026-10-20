<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Cohort;
use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ParticipantRegistrationLearningChoicesTest extends TestCase
{
    use RefreshDatabase;

    private Branch $branch;
    private Cohort $cohort;
    private Course $course;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();

        $this->branch = Branch::create(['name' => 'Kampala Branch', 'code' => 'KLA', 'is_active' => true]);
        $this->cohort = Cohort::create(['name' => 'October Cohort', 'code' => 'OCT', 'status' => 'active', 'branch_id' => $this->branch->id]);
        $this->course = Course::create(['title' => 'Digital Skills', 'delivery_mode' => 'online', 'status' => 'published', 'pass_mark' => 50]);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'surname' => 'Nansubuga',
            'given_name' => 'Sarah',
            'email' => 'sarah.choices@gmail.com',
            'phone' => '+256700000000',
            'password' => 'StrongPass1',
            'password_confirmation' => 'StrongPass1',
            'terms' => '1',
            'interests' => ['learning'],
            'branch_id' => $this->branch->id,
            'cohort_id' => $this->cohort->id,
            'course_id' => $this->course->id,
        ], $overrides);
    }

    public function test_learning_requires_branch_cohort_and_course(): void
    {
        $this->post(route('register.store'), $this->payload([
            'branch_id' => null,
            'cohort_id' => null,
            'course_id' => null,
        ]))->assertSessionHasErrors(['branch_id', 'cohort_id', 'course_id']);

        $this->assertDatabaseMissing('users', ['email' => 'sarah.choices@gmail.com']);
    }

    public function test_choosing_all_interests_also_requires_the_learning_choices(): void
    {
        $this->post(route('register.store'), $this->payload([
            'interests' => ['learning', 'mentorship', 'jobs'],
            'course_id' => null,
        ]))->assertSessionHasErrors('course_id');
    }

    public function test_other_interests_do_not_need_learning_choices_and_drop_them(): void
    {
        $this->post(route('register.store'), $this->payload([
            'interests' => ['mentorship', 'jobs'],
            'cohort_id' => null,
            'course_id' => null,
        ]))->assertSessionHasNoErrors();

        $user = User::where('email', 'sarah.choices@gmail.com')->firstOrFail();
        $this->assertNull($user->profile->branch_id);
        $this->assertDatabaseMissing('enrolments', ['user_id' => $user->id]);
    }

    public function test_the_cohort_must_run_at_the_chosen_branch(): void
    {
        $other = Branch::create(['name' => 'Gulu Branch', 'code' => 'GUL', 'is_active' => true]);

        $this->post(route('register.store'), $this->payload(['branch_id' => $other->id]))
            ->assertSessionHasErrors('cohort_id');
    }

    public function test_an_inactive_cohort_cannot_be_chosen(): void
    {
        $closed = Cohort::create(['name' => 'Past Cohort', 'code' => 'PST', 'status' => 'completed', 'branch_id' => $this->branch->id]);

        $this->post(route('register.store'), $this->payload(['cohort_id' => $closed->id]))
            ->assertSessionHasErrors('cohort_id');
    }

    public function test_a_field_with_nothing_to_choose_stays_optional(): void
    {
        $this->cohort->update(['status' => 'completed']);

        $this->post(route('register.store'), $this->payload(['cohort_id' => null]))
            ->assertSessionHasNoErrors();
    }
}
