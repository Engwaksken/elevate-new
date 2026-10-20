<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ParticipantRegistrationEnrolmentTest extends TestCase
{
    use RefreshDatabase;

    private Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();

        $this->branch = Branch::create(['name' => 'Kampala Branch', 'code' => 'KLA', 'is_active' => true]);
    }

    private function register(array $overrides = []): User
    {
        $this->post(route('register.store'), array_merge([
            'surname' => 'Nansubuga',
            'given_name' => 'Sarah',
            'email' => 'sarah.enrol@gmail.com',
            'phone' => '+256700000000',
            'password' => 'StrongPass1',
            'password_confirmation' => 'StrongPass1',
            'terms' => '1',
            'interests' => ['learning'],
            'branch_id' => $this->branch->id,
        ], $overrides))->assertSessionHasNoErrors();

        return User::where('email', 'sarah.enrol@gmail.com')->firstOrFail();
    }

    private function course(string $title, array $attributes = []): Course
    {
        $course = Course::create(array_merge(['title' => $title, 'delivery_mode' => 'online', 'status' => 'published', 'pass_mark' => 50], $attributes));
        $course->branches()->attach($this->branch->id);

        return $course;
    }

    public function test_the_chosen_course_and_its_compulsory_courses_are_enrolled(): void
    {
        $primary = $this->course('Digital Skills');
        $compulsory = $this->course('Orientation');
        $primary->compulsoryCourses()->attach($compulsory->id);

        $user = $this->register(['course_id' => $primary->id]);

        $this->assertDatabaseHas('enrolments', ['user_id' => $user->id, 'course_id' => $primary->id, 'status' => 'enrolled']);
        $this->assertDatabaseHas('enrolments', ['user_id' => $user->id, 'course_id' => $compulsory->id, 'status' => 'enrolled']);
    }

    public function test_enrolment_does_not_need_self_enrolment_or_an_entry_assessment_pass(): void
    {
        $course = $this->course('Leadership', ['self_enrolment_enabled' => false]);

        $user = $this->register(['course_id' => $course->id]);

        $this->assertDatabaseHas('enrolments', ['user_id' => $user->id, 'course_id' => $course->id]);
    }

    public function test_no_enrolment_without_the_learning_interest(): void
    {
        $course = $this->course('Digital Skills');

        $user = $this->register(['interests' => ['jobs'], 'course_id' => $course->id]);

        $this->assertDatabaseMissing('enrolments', ['user_id' => $user->id]);
    }

    public function test_an_unpublished_course_cannot_be_chosen(): void
    {
        $course = $this->course('Draft course', ['status' => 'draft']);

        $this->post(route('register.store'), [
            'surname' => 'Nansubuga',
            'given_name' => 'Sarah',
            'email' => 'sarah.enrol@gmail.com',
            'phone' => '+256700000000',
            'password' => 'StrongPass1',
            'password_confirmation' => 'StrongPass1',
            'terms' => '1',
            'interests' => ['learning'],
            'course_id' => $course->id,
        ])->assertSessionHasErrors('course_id');

        $this->assertDatabaseCount('enrolments', 0);
    }
}
