<?php

namespace Tests\Feature\Participant;

use App\Models\Course;
use App\Models\CourseModule;
use App\Models\Enrolment;
use App\Models\LearningFile;
use App\Models\Lesson;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ParticipantLessonApiTest extends TestCase
{
    use RefreshDatabase;

    private const BASE = '/api/v1/participant';

    private Course $course;
    private CourseModule $module;
    private Lesson $fileLesson;
    private Lesson $textLesson;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        Storage::fake('local');

        $this->course = Course::create(['title' => 'Digital Marketing', 'status' => 'published']);
        $this->module = CourseModule::create([
            'course_id' => $this->course->id, 'title' => 'Module 1', 'position' => 1, 'is_published' => true,
        ]);

        $path = UploadedFile::fake()->create('slides.pdf', 12, 'application/pdf')
            ->store("courses/{$this->course->id}/lessons", 'public');

        $this->fileLesson = Lesson::create([
            'course_module_id' => $this->module->id, 'title' => 'Lesson 1', 'content' => 'lesson',
            'content_type' => 'file', 'file_path' => $path, 'estimated_minutes' => 30,
            'position' => 1, 'is_published' => true,
        ]);

        $this->textLesson = Lesson::create([
            'course_module_id' => $this->module->id, 'title' => 'Lesson2', 'content' => 'Lesson 2 body',
            'content_type' => 'text', 'estimated_minutes' => 30, 'position' => 2, 'is_published' => true,
        ]);
    }

    private function participant(bool $enrolled = true): User
    {
        $user = User::factory()->create(['user_type' => 'participant', 'status' => 'active']);

        if ($enrolled) {
            Enrolment::create(['course_id' => $this->course->id, 'user_id' => $user->id, 'status' => 'enrolled']);
        }

        Sanctum::actingAs($user, ['participant']);

        return $user;
    }

    public function test_course_detail_exposes_authenticated_lesson_resource_urls(): void
    {
        $this->participant();

        $response = $this->getJson(self::BASE."/courses/{$this->course->id}")->assertOk();

        $lessons = $response->json('course.modules.0.lessons');
        $this->assertCount(2, $lessons);

        $this->assertSame('file', $lessons[0]['content_type']);
        $this->assertTrue($lessons[0]['has_file']);
        $this->assertSame(route('api.participant.lessons.download', $this->fileLesson), $lessons[0]['resource_url']);
        $this->assertStringNotContainsString('/storage/', $lessons[0]['resource_url']);
        $this->assertSame("/lessons/{$this->fileLesson->id}/download", $lessons[0]['download_path']);

        $this->assertSame('text', $lessons[1]['content_type']);
        $this->assertNull($lessons[1]['resource_url']);
        $this->assertSame('Lesson 2 body', $lessons[1]['content']);
        $this->assertFalse($response->json('course.modules.0.is_locked'));
    }

    public function test_lesson_detail_returns_text_body_and_records_open(): void
    {
        $user = $this->participant();

        $this->getJson(self::BASE."/lessons/{$this->textLesson->id}")
            ->assertOk()
            ->assertJsonPath('lesson.id', $this->textLesson->id)
            ->assertJsonPath('lesson.type', 'text')
            ->assertJsonPath('lesson.content', 'Lesson 2 body')
            ->assertJsonPath('lesson.body', 'Lesson 2 body')
            ->assertJsonPath('lesson.duration_minutes', 30)
            ->assertJsonPath('lesson.course_id', $this->course->id)
            ->assertJsonPath('lesson.progress.completed', false);

        $this->assertDatabaseHas('lesson_progress', ['lesson_id' => $this->textLesson->id, 'user_id' => $user->id]);
    }

    public function test_file_lesson_download_streams_file_with_headers(): void
    {
        $this->participant();

        $response = $this->get(self::BASE."/lessons/{$this->fileLesson->id}/download");

        $response->assertOk();
        $this->assertStringContainsString('application/pdf', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('attachment', $response->headers->get('Content-Disposition'));
        $this->assertStringContainsString('lesson-1.pdf', $response->headers->get('Content-Disposition'));

        $inline = $this->get(self::BASE."/lessons/{$this->fileLesson->id}/download?inline=1");
        $this->assertStringContainsString('inline', $inline->headers->get('Content-Disposition'));
    }

    public function test_attached_learning_file_is_downloadable(): void
    {
        $this->participant();

        Storage::disk('local')->put('learning/1/abc.docx', 'docx-bytes');
        $file = LearningFile::create([
            'course_id' => $this->course->id, 'lesson_id' => $this->textLesson->id,
            'original_name' => 'Handout.docx', 'stored_name' => 'abc.docx', 'disk' => 'local',
            'path' => 'learning/1/abc.docx', 'mime_type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'size_bytes' => 10,
        ]);

        $this->getJson(self::BASE."/lessons/{$this->textLesson->id}")
            ->assertOk()
            ->assertJsonPath('lesson.has_file', true)
            ->assertJsonPath('lesson.files.0.name', 'Handout.docx');

        $this->get(self::BASE."/lessons/{$this->textLesson->id}/files/{$file->id}/download")
            ->assertOk()
            ->assertHeader('Content-Disposition', 'attachment; filename=Handout.docx');

        // A file belonging to another lesson must not be served through this lesson.
        $this->getJson(self::BASE."/lessons/{$this->fileLesson->id}/files/{$file->id}/download")
            ->assertNotFound()
            ->assertJsonPath('message', 'File not found for this lesson.');
    }

    public function test_missing_file_returns_json_404(): void
    {
        $this->participant();
        Storage::disk('public')->delete($this->fileLesson->file_path);

        $this->getJson(self::BASE."/lessons/{$this->fileLesson->id}/download")
            ->assertNotFound()
            ->assertExactJson(['message' => 'This lesson has no downloadable file.']);
    }

    public function test_unknown_lesson_returns_json_404_with_message(): void
    {
        $this->participant();

        $this->get(self::BASE.'/lessons/999999', ['Accept' => '*/*'])
            ->assertNotFound()
            ->assertHeader('Content-Type', 'application/json')
            ->assertExactJson(['message' => 'Lesson not found.']);
    }

    public function test_unpublished_lesson_is_not_found(): void
    {
        $this->participant();
        $this->textLesson->update(['is_published' => false]);

        $this->getJson(self::BASE."/lessons/{$this->textLesson->id}")
            ->assertNotFound()
            ->assertJsonPath('message', 'Lesson not found.');
    }

    public function test_non_enrolled_participant_is_forbidden(): void
    {
        $this->participant(enrolled: false);

        $this->getJson(self::BASE."/lessons/{$this->textLesson->id}")
            ->assertForbidden()
            ->assertJsonPath('message', 'You are not enrolled in the course for this lesson.');

        $this->get(self::BASE."/lessons/{$this->fileLesson->id}/download")->assertForbidden();

        $this->putJson(self::BASE."/lessons/{$this->textLesson->id}/progress", ['completed' => true])
            ->assertForbidden();
    }

    public function test_unauthenticated_requests_get_json_401(): void
    {
        $this->get(self::BASE."/lessons/{$this->textLesson->id}")
            ->assertUnauthorized()
            ->assertExactJson(['message' => 'Unauthenticated.']);
    }

    public function test_progress_marks_lesson_complete_and_updates_course_progress(): void
    {
        $user = $this->participant();

        $this->putJson(self::BASE."/lessons/{$this->fileLesson->id}/progress", [
            'completed' => true, 'time_spent_seconds' => 90,
        ])
            ->assertOk()
            ->assertJsonPath('message', 'Progress saved.')
            ->assertJsonPath('completed', true)
            ->assertJsonPath('course_progress_percent', 50);

        $this->putJson(self::BASE."/lessons/{$this->textLesson->id}/progress", ['completed' => true])
            ->assertOk()
            ->assertJsonPath('course_progress_percent', 100)
            ->assertJsonPath('course_status', 'completed');

        $this->assertDatabaseHas('enrolments', ['user_id' => $user->id, 'status' => 'completed']);
    }

    public function test_progress_validation_error_has_message(): void
    {
        $this->participant();

        $this->putJson(self::BASE."/lessons/{$this->textLesson->id}/progress", [])
            ->assertStatus(422)
            ->assertJsonStructure(['message', 'errors' => ['completed']]);
    }

    public function test_offline_actions_accept_flutter_actions_key(): void
    {
        $this->participant();

        $this->postJson(self::BASE.'/offline-actions', [
            'actions' => [[
                'client_operation_id' => 'op-1',
                'type' => 'lesson_progress',
                'payload' => ['lesson_id' => $this->textLesson->id, 'completed' => true],
            ], [
                'client_operation_id' => 'op-2',
                'type' => 'lesson_progress',
                'payload' => ['lesson_id' => 999999, 'completed' => true],
            ]],
        ])
            ->assertOk()
            ->assertJsonPath('results.0.status', 'processed')
            ->assertJsonPath('results.0.result.course_progress_percent', 50)
            ->assertJsonPath('results.1.status', 'failed')
            ->assertJsonPath('results.1.code', 404);
    }

    public function test_sync_accepts_since_alias_and_assignments_have_api_attachment_url(): void
    {
        $this->participant();

        $path = UploadedFile::fake()->create('brief.pdf', 5, 'application/pdf')
            ->store("courses/{$this->course->id}/assessments", 'public');

        $assessment = \App\Models\Assessment::create([
            'course_id' => $this->course->id, 'title' => 'Brief', 'type' => 'assignment',
            'max_attempts' => 1, 'attachment_path' => $path, 'is_published' => true,
        ]);

        $this->getJson(self::BASE.'/sync?since='.urlencode(now()->subDay()->toIso8601String()))
            ->assertOk()
            ->assertJsonPath('assignments.0.attachment_url', route('api.participant.assignments.attachment', $assessment));

        $this->getJson(self::BASE.'/sync?since=not-a-date')->assertStatus(422);

        $this->get(self::BASE."/assignments/{$assessment->id}/attachment")
            ->assertOk()
            ->assertHeader('Content-Disposition', 'attachment; filename='.basename($path));
    }

    public function test_device_token_accepts_fcm_token_and_can_be_removed(): void
    {
        $user = $this->participant();

        $this->postJson(self::BASE.'/device-token', [
            'device_id' => 'dev-1', 'fcm_token' => 'abc', 'platform' => 'android',
        ])->assertOk()->assertJsonPath('device.token', 'abc');

        $this->deleteJson(self::BASE.'/device-token', ['device_id' => 'dev-1'])
            ->assertOk()
            ->assertJsonPath('removed', true);

        $this->assertDatabaseMissing('participant_device_tokens', ['user_id' => $user->id]);
    }

    public function test_unknown_api_endpoint_returns_json_404(): void
    {
        $this->participant();

        $this->get(self::BASE.'/does-not-exist')
            ->assertNotFound()
            ->assertExactJson(['message' => 'The requested API endpoint does not exist.']);
    }
}
