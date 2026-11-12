<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\Course;
use App\Models\CourseModule;
use App\Models\Enrolment;
use App\Models\Lesson;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The participant course page only offers assessments she can open: one
 * whose attempts are all used is shown as completed, not as a link that
 * would be refused with "Access restricted".
 */
class CourseAssessmentListTest extends TestCase
{
    use RefreshDatabase;

    public function test_exhausted_assessment_is_shown_completed_not_linked(): void
    {
        $user = User::factory()->create(['user_type' => 'participant', 'status' => 'active', 'email_verified_at' => now()]);
        $course = Course::create(['title' => 'Web Development', 'status' => 'published', 'pass_mark' => 60]);
        Enrolment::create(['user_id' => $user->id, 'course_id' => $course->id, 'status' => 'enrolled']);

        $quiz = Assessment::create(['course_id' => $course->id, 'title' => 'Week 1 Quiz', 'type' => 'quiz', 'max_attempts' => 2, 'is_published' => true, 'pass_mark' => 60]);
        $exam = Assessment::create(['course_id' => $course->id, 'title' => 'Final exam', 'type' => 'exam', 'max_attempts' => 2, 'is_published' => true, 'pass_mark' => 60]);
        foreach ([1, 2] as $n) {
            AssessmentAttempt::create(['assessment_id' => $quiz->id, 'user_id' => $user->id, 'attempt_number' => $n, 'percentage' => 50 + $n * 10, 'status' => 'graded', 'submitted_at' => now()]);
        }

        $module = CourseModule::create(['course_id' => $course->id, 'title' => 'Getting started', 'position' => 1, 'is_published' => true]);
        Lesson::create(['course_module_id' => $module->id, 'title' => 'Welcome lesson', 'content' => 'Intro', 'position' => 1, 'is_published' => true]);
        $page = $this->actingAs($user)->get(route('learning.course.dashboard', $course))->assertOk()
            ->assertSee('data-eh-tab="course-timetable"', false)
            ->assertSee('data-eh-tab="course-assessments"', false)
            ->assertSee('<details class="participant-module', false)
            ->assertSee('participant-lesson-group', false)
            ->assertSee('Welcome lesson');

        $page->assertDontSee(route('learning.assessment.show', $quiz), false)
            ->assertSee('All 2 attempts used')
            ->assertSee('Best score 70%')
            ->assertSee(route('learning.assessment.show', $exam), false)
            ->assertSee('2 of 2 attempts left');

        // Every link the page still offers opens.
        $this->actingAs($user)->get(route('learning.assessment.show', $exam))->assertOk();
    }
}
