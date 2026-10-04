<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\Course;
use App\Models\CourseApplication;
use App\Models\CourseCall;
use App\Models\Enrolment;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EntryAssessmentPipelineTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $user = User::factory()->create(['user_type' => 'staff', 'status' => 'active']);
        $user->roles()->attach(Role::firstOrCreate(['slug' => 'administrator'], ['name' => 'Administrator']));

        return $user;
    }

    private function participant(): User
    {
        return User::factory()->create([
            'user_type' => 'participant',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);
    }

    public function test_self_enrolment_also_enrols_compulsory_courses(): void
    {
        $primary = Course::create(['title' => 'Primary', 'delivery_mode' => 'online', 'status' => 'published', 'pass_mark' => 50, 'self_enrolment_enabled' => true]);
        $compulsory = Course::create(['title' => 'Compulsory', 'delivery_mode' => 'online', 'status' => 'published', 'pass_mark' => 50]);
        $primary->compulsoryCourses()->attach($compulsory->id);

        $participant = $this->participant();

        $this->actingAs($participant)
            ->post(route('learning.enrol', $primary))
            ->assertRedirect(route('learning.my-courses'));

        $this->assertDatabaseHas('enrolments', ['course_id' => $primary->id, 'user_id' => $participant->id]);
        $this->assertDatabaseHas('enrolments', ['course_id' => $compulsory->id, 'user_id' => $participant->id]);
    }

    public function test_admin_can_set_compulsory_courses_on_a_course(): void
    {
        $primary = Course::create(['title' => 'Primary', 'delivery_mode' => 'online', 'status' => 'published', 'pass_mark' => 50]);
        $compulsory = Course::create(['title' => 'Compulsory', 'delivery_mode' => 'online', 'status' => 'published', 'pass_mark' => 50]);

        $this->actingAs($this->admin())
            ->put(route('admin.elearning.courses.update', $primary), [
                'title' => 'Primary',
                'delivery_mode' => 'online',
                'pass_mark' => 50,
                'status' => 'published',
                'sync_compulsory' => 1,
                'compulsory_course_ids' => [$compulsory->id, $primary->id],
            ])->assertSessionHas('success');

        $this->assertEqualsCanonicalizing(
            [$compulsory->id],
            $primary->fresh()->compulsoryCourses->pluck('id')->all()
        );
    }

    public function test_course_call_approval_requires_the_course_pass_mark(): void
    {
        $course = Course::create(['title' => 'Data Skills', 'status' => 'published', 'pass_mark' => 60]);
        $assessment = Assessment::create([
            'course_id' => $course->id,
            'title' => 'Entry assessment',
            'type' => 'quiz',
            'max_attempts' => 5,
            'is_published' => true,
        ]);
        $call = CourseCall::create(['title' => 'Intake', 'status' => 'published', 'entry_assessment_id' => $assessment->id]);
        $call->courses()->attach($course->id);

        $applicant = $this->participant();
        $application = CourseApplication::create([
            'course_call_id' => $call->id,
            'user_id' => $applicant->id,
            'status' => 'submitted',
        ]);

        AssessmentAttempt::create([
            'assessment_id' => $assessment->id,
            'user_id' => $applicant->id,
            'attempt_number' => 1,
            'status' => 'graded',
            'percentage' => 40,
        ]);

        $this->actingAs($this->admin())
            ->put(route('admin.course-applications.review', $application), [
                'application_id' => $application->id,
                'status' => 'approved',
                'approved_course_id' => $course->id,
            ])->assertSessionHasErrors('status');

        $this->assertFalse(Enrolment::where('course_id', $course->id)->where('user_id', $applicant->id)->exists());

        AssessmentAttempt::create([
            'assessment_id' => $assessment->id,
            'user_id' => $applicant->id,
            'attempt_number' => 2,
            'status' => 'graded',
            'percentage' => 80,
        ]);

        $this->actingAs($this->admin())
            ->put(route('admin.course-applications.review', $application), [
                'application_id' => $application->id,
                'status' => 'approved',
                'approved_course_id' => $course->id,
            ])->assertSessionHas('success');

        $this->assertTrue(Enrolment::where('course_id', $course->id)->where('user_id', $applicant->id)->exists());
    }

    public function test_course_call_approval_enrols_compulsory_courses(): void
    {
        $course = Course::create(['title' => 'Data Skills', 'status' => 'published', 'pass_mark' => 50]);
        $compulsory = Course::create(['title' => 'Digital Basics', 'status' => 'published', 'pass_mark' => 50]);
        $course->compulsoryCourses()->attach($compulsory->id);

        $applicant = $this->participant();
        $call = CourseCall::create(['title' => 'Intake', 'status' => 'published']);
        $call->courses()->attach($course->id);
        $application = CourseApplication::create([
            'course_call_id' => $call->id,
            'user_id' => $applicant->id,
            'status' => 'submitted',
        ]);

        $this->actingAs($this->admin())
            ->put(route('admin.course-applications.review', $application), [
                'application_id' => $application->id,
                'status' => 'approved',
                'approved_course_id' => $course->id,
            ])->assertSessionHas('success');

        $this->assertDatabaseHas('enrolments', ['course_id' => $course->id, 'user_id' => $applicant->id]);
        $this->assertDatabaseHas('enrolments', ['course_id' => $compulsory->id, 'user_id' => $applicant->id]);
    }
}
