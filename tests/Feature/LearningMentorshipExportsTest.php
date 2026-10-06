<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Enrolment;
use App\Models\MentorMatch;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LearningMentorshipExportsTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $user = User::factory()->create(['user_type' => 'staff', 'status' => 'active']);
        $user->roles()->attach(Role::firstOrCreate(['slug' => 'super-administrator'], ['name' => 'Super Administrator']));

        return $user;
    }

    private function instructor(): User
    {
        $user = User::factory()->create(['user_type' => 'staff', 'status' => 'active']);
        $user->roles()->attach(Role::firstOrCreate(['slug' => 'instructor'], ['name' => 'Instructor']));

        return $user;
    }

    public function test_admin_courses_csv_respects_the_status_filter(): void
    {
        Course::create(['title' => 'Published Basics', 'status' => 'published']);
        Course::create(['title' => 'Draft Advanced', 'status' => 'draft']);

        $response = $this->actingAs($this->admin())
            ->get(route('admin.elearning.courses.index', ['status' => 'published', 'export' => 'csv']));

        $response->assertOk();
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));
        $csv = $response->streamedContent();
        $this->assertStringContainsString('Course,Code,Branches', $csv);
        $this->assertStringContainsString('Published Basics', $csv);
        $this->assertStringNotContainsString('Draft Advanced', $csv);
    }

    public function test_admin_courses_page_shows_export_buttons(): void
    {
        $this->actingAs($this->admin())
            ->get(route('admin.elearning.courses.index', ['status' => 'draft']))
            ->assertOk()
            ->assertSee('export=pdf', false)
            ->assertSee('status=draft', false);
    }

    public function test_admin_enrolments_pdf_export_returns_a_pdf(): void
    {
        $course = Course::create(['title' => 'Digital Skills', 'status' => 'published']);
        $learner = User::factory()->create(['user_type' => 'participant', 'status' => 'active']);
        Enrolment::create(['course_id' => $course->id, 'user_id' => $learner->id, 'status' => 'enrolled', 'enrolled_at' => now()]);

        $response = $this->actingAs($this->admin())
            ->get(route('admin.elearning.enrolments.index', ['export' => 'pdf']));

        $response->assertOk();
        $this->assertSame('application/pdf', $response->headers->get('Content-Type'));
    }

    public function test_participant_my_learning_export_only_contains_own_enrolments(): void
    {
        $mine = Course::create(['title' => 'Mine Course', 'status' => 'published']);
        $theirs = Course::create(['title' => 'Their Course', 'status' => 'published']);
        $me = User::factory()->create(['user_type' => 'participant', 'status' => 'active']);
        $other = User::factory()->create(['user_type' => 'participant', 'status' => 'active']);
        Enrolment::create(['course_id' => $mine->id, 'user_id' => $me->id, 'status' => 'enrolled', 'enrolled_at' => now()]);
        Enrolment::create(['course_id' => $theirs->id, 'user_id' => $other->id, 'status' => 'enrolled', 'enrolled_at' => now()]);

        $response = $this->actingAs($me)->get(route('learning.my-courses', ['export' => 'csv']));

        $response->assertOk();
        $csv = $response->streamedContent();
        $this->assertStringContainsString('Mine Course', $csv);
        $this->assertStringNotContainsString('Their Course', $csv);
    }

    public function test_instructor_my_courses_export_only_contains_assigned_courses(): void
    {
        $instructor = $this->instructor();
        $assigned = Course::create(['title' => 'Assigned Course', 'status' => 'published']);
        Course::create(['title' => 'Someone Elses Course', 'status' => 'published']);
        $instructor->instructedCourses()->attach($assigned);

        $response = $this->actingAs($instructor)->get(route('admin.my-courses', ['export' => 'csv']));

        $response->assertOk();
        $csv = $response->streamedContent();
        $this->assertStringContainsString('Assigned Course', $csv);
        $this->assertStringNotContainsString('Someone Elses Course', $csv);
    }

    public function test_instructor_course_participants_export_is_scoped_and_authorised(): void
    {
        $instructor = $this->instructor();
        $course = Course::create(['title' => 'Assigned Course', 'status' => 'published']);
        $otherCourse = Course::create(['title' => 'Other Course', 'status' => 'published']);
        $instructor->instructedCourses()->attach($course);

        $learner = User::factory()->create(['name' => 'Asha Learner', 'user_type' => 'participant', 'status' => 'active']);
        $stranger = User::factory()->create(['name' => 'Zed Outsider', 'user_type' => 'participant', 'status' => 'active']);
        Enrolment::create(['course_id' => $course->id, 'user_id' => $learner->id, 'status' => 'enrolled', 'enrolled_at' => now()]);
        Enrolment::create(['course_id' => $otherCourse->id, 'user_id' => $stranger->id, 'status' => 'enrolled', 'enrolled_at' => now()]);

        $response = $this->actingAs($instructor)
            ->get(route('instructor.courses.manage', [$course, 'tab' => 'participants', 'list' => 'participants', 'export' => 'csv']));

        $response->assertOk();
        $csv = $response->streamedContent();
        $this->assertStringContainsString('Asha Learner', $csv);
        $this->assertStringNotContainsString('Zed Outsider', $csv);

        $this->actingAs($instructor)
            ->get(route('instructor.courses.manage', [$otherCourse, 'list' => 'participants', 'export' => 'csv']))
            ->assertForbidden();
    }

    public function test_admin_mentor_matches_csv_export(): void
    {
        $mentor = User::factory()->create(['name' => 'Mona Mentor', 'user_type' => 'participant', 'status' => 'active']);
        $mentee = User::factory()->create(['name' => 'Mia Mentee', 'user_type' => 'participant', 'status' => 'active']);
        MentorMatch::create(['mentor_user_id' => $mentor->id, 'mentee_user_id' => $mentee->id, 'status' => 'active']);

        $response = $this->actingAs($this->admin())
            ->get(route('admin.mentorship.matches.index', ['export' => 'csv']));

        $response->assertOk();
        $csv = $response->streamedContent();
        $this->assertStringContainsString('Mona Mentor', $csv);
        $this->assertStringContainsString('Mia Mentee', $csv);
    }

    public function test_participants_cannot_export_admin_lists(): void
    {
        $participant = User::factory()->create(['user_type' => 'participant', 'status' => 'active']);

        $response = $this->actingAs($participant)->get(route('admin.elearning.enrolments.index', ['export' => 'csv']));

        $this->assertNotSame(200, $response->getStatusCode());
    }
}
