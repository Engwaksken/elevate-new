<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\CourseModule;
use App\Models\Enrolment;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LessonReadingTimeTest extends TestCase
{
    use RefreshDatabase;

    private User $participant;

    private Lesson $lesson;

    protected function setUp(): void
    {
        parent::setUp();

        $course = Course::create(['title' => 'Reading course', 'status' => 'published']);
        $module = CourseModule::create([
            'course_id' => $course->id,
            'title' => 'Module 1',
            'position' => 1,
            'is_published' => true,
        ]);
        $this->lesson = Lesson::create([
            'course_module_id' => $module->id,
            'title' => 'Lesson 1',
            'content' => 'Read this lesson',
            'content_type' => 'text',
            'position' => 1,
            'is_published' => true,
        ]);
        $this->participant = User::factory()->create(['user_type' => 'participant', 'status' => 'active']);
        Enrolment::create([
            'course_id' => $course->id,
            'user_id' => $this->participant->id,
            'status' => 'enrolled',
        ]);
    }

    public function test_web_lesson_records_active_reading_time_without_marking_complete(): void
    {
        $response = $this->actingAs($this->participant)->postJson(
            route('learning.lesson.reading-time', $this->lesson),
            ['seconds' => 30]
        );

        $response->assertOk()->assertJsonPath('time_spent_seconds_added', 30);
        $progress = LessonProgress::where('user_id', $this->participant->id)
            ->where('lesson_id', $this->lesson->id)
            ->firstOrFail();

        $this->assertSame(30, (int) $progress->time_spent_seconds);
        $this->assertNull($progress->completed_at);
    }

    public function test_web_reading_time_batches_are_bounded_and_require_enrolment(): void
    {
        $this->actingAs($this->participant)
            ->postJson(route('learning.lesson.reading-time', $this->lesson), ['seconds' => 301])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('seconds');

        Enrolment::where('user_id', $this->participant->id)->delete();

        $this->actingAs($this->participant)
            ->postJson(route('learning.lesson.reading-time', $this->lesson), ['seconds' => 15])
            ->assertForbidden();
    }
}
