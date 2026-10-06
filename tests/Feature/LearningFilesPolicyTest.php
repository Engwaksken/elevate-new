<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\AssessmentAttemptFile;
use App\Models\Course;
use App\Models\CourseModule;
use App\Models\Enrolment;
use App\Models\LearningFile;
use App\Models\Lesson;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Download policy (only xlsx/xls/csv/zip downloadable for participants, everything
 * else view-only) and multiple files per lesson / assignment / submission.
 */
class LearningFilesPolicyTest extends TestCase
{
    use RefreshDatabase;

    private Course $course;
    private CourseModule $module;
    private Lesson $lesson;
    private User $participant;
    private User $instructor;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Storage::fake('public');

        $this->course = Course::create(['title' => 'Bookkeeping', 'status' => 'published']);
        $this->module = CourseModule::create([
            'course_id' => $this->course->id, 'title' => 'Module 1', 'position' => 1, 'is_published' => true,
        ]);
        $this->lesson = Lesson::create([
            'course_module_id' => $this->module->id, 'title' => 'Ledgers', 'content' => 'Body',
            'content_type' => 'file', 'position' => 1, 'is_published' => true,
        ]);

        $this->participant = User::factory()->create(['user_type' => 'participant', 'status' => 'active']);
        Enrolment::create(['course_id' => $this->course->id, 'user_id' => $this->participant->id, 'status' => 'enrolled']);

        $this->instructor = User::factory()->create(['user_type' => 'staff', 'status' => 'active']);
        $this->instructor->roles()->attach(Role::firstOrCreate(['slug' => 'instructor'], ['name' => 'Instructor']));
        $this->course->instructors()->attach($this->instructor->id);
    }

    private function lessonFile(string $name, string $contents = 'bytes', ?Lesson $lesson = null): LearningFile
    {
        $path = 'learning/'.$this->course->id.'/'.uniqid().'-'.$name;
        Storage::disk('local')->put($path, $contents);

        return LearningFile::create([
            'course_id' => $this->course->id,
            'lesson_id' => ($lesson ?? $this->lesson)->id,
            'original_name' => $name,
            'stored_name' => basename($path),
            'disk' => 'local',
            'path' => $path,
            'size_bytes' => strlen($contents),
        ]);
    }

    /* ---------------- Feature 1: download policy (web) ---------------- */

    public function test_view_only_pdf_is_served_inline_and_forced_download_is_refused(): void
    {
        $file = $this->lessonFile('notes.pdf', '%PDF-1.4 test');

        $response = $this->actingAs($this->participant)->get(route('learning.files.download', $file));
        $response->assertOk();
        $this->assertStringStartsWith('inline', $response->headers->get('Content-Disposition'));
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        $response->assertHeader('X-Content-Type-Options', 'nosniff');

        $this->actingAs($this->participant)
            ->get(route('learning.files.download', [$file, 'download' => 1]))
            ->assertForbidden();
    }

    public function test_spreadsheet_and_zip_are_downloadable_by_participants(): void
    {
        foreach (['budget.xlsx', 'old.xls', 'list.csv', 'pack.zip'] as $name) {
            $file = $this->lessonFile($name);

            $response = $this->actingAs($this->participant)
                ->get(route('learning.files.download', [$file, 'download' => 1]));

            $response->assertOk();
            $this->assertStringStartsWith('attachment', $response->headers->get('Content-Disposition'), $name);
        }
    }

    public function test_view_only_preview_hides_download_and_office_files_show_view_only_notice(): void
    {
        $pdf = $this->lessonFile('notes.pdf', '%PDF-1.4 test');

        $html = $this->actingAs($this->participant)
            ->get(route('learning.files.download', [$pdf, 'preview' => 1]))
            ->assertOk()
            ->assertSee('View only')
            ->getContent();
        $this->assertStringNotContainsString('>Download<', $html);

        $this->actingAs($this->participant)
            ->get(route('learning.files.download', [$pdf, 'preview' => 'raw']))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        // PowerPoint cannot be rendered by the browser: a view-only notice instead of a download.
        $pptx = $this->lessonFile('slides.pptx');
        $response = $this->actingAs($this->participant)->get(route('learning.files.download', $pptx));
        $response->assertOk()->assertSee('Preview not available in the browser — this file is view-only.');
        $this->assertStringNotContainsString('attachment', (string) $response->headers->get('Content-Disposition'));
    }

    public function test_course_staff_keep_full_download_access(): void
    {
        $file = $this->lessonFile('notes.pdf', '%PDF-1.4 test');

        $response = $this->actingAs($this->instructor)->get(route('learning.files.download', [$file, 'download' => 1]));
        $response->assertOk();
        $this->assertStringStartsWith('attachment', $response->headers->get('Content-Disposition'));

        // Staff who do not manage the course get the participant policy.
        $otherStaff = User::factory()->create(['user_type' => 'staff', 'status' => 'active']);
        $this->actingAs($otherStaff)
            ->get(route('learning.files.download', [$file, 'download' => 1]))
            ->assertForbidden();
    }

    public function test_participant_lesson_page_lists_all_files_with_download_only_for_downloadable_types(): void
    {
        $pdf = $this->lessonFile('notes.pdf', '%PDF-1.4 test');
        $sheet = $this->lessonFile('budget.xlsx');

        $html = $this->actingAs($this->participant)
            ->get(route('learning.lesson.show', $this->lesson))
            ->assertOk()
            ->assertSee('notes.pdf')
            ->assertSee('budget.xlsx')
            ->getContent();

        $this->assertStringContainsString(route('learning.files.download', [$sheet, 'download' => 1]), html_entity_decode($html));
        $this->assertStringNotContainsString(route('learning.files.download', [$pdf, 'download' => 1]), html_entity_decode($html));
        $this->assertStringContainsString('data-file-preview-no-download', $html);
    }

    public function test_locked_or_unpublished_lesson_files_are_not_served_to_participants(): void
    {
        $file = $this->lessonFile('notes.pdf');
        $this->lesson->update(['is_published' => false]);

        $this->actingAs($this->participant)->get(route('learning.files.download', $file))->assertNotFound();

        $outsider = User::factory()->create(['user_type' => 'participant', 'status' => 'active']);
        $this->actingAs($outsider)->get(route('learning.files.download', $file))->assertForbidden();
    }

    /* ---------------- Feature 2: multiple files ---------------- */

    public function test_instructor_uploads_several_lesson_files_and_removes_one(): void
    {
        $this->actingAs($this->instructor)
            ->post(route('instructor.courses.lessons.store', [$this->course, $this->module]), [
                'title' => 'Cash book',
                'content_type' => 'file',
                'is_published' => 1,
                'resource_files' => [
                    UploadedFile::fake()->create('one.pdf', 10, 'application/pdf'),
                    UploadedFile::fake()->create('two.xlsx', 10),
                    UploadedFile::fake()->create('three.docx', 10),
                ],
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $lesson = Lesson::where('title', 'Cash book')->firstOrFail();
        $this->assertSame(['one.pdf', 'two.xlsx', 'three.docx'], $lesson->files()->pluck('original_name')->all());
        $lesson->files->each(fn ($file) => Storage::disk('local')->assertExists($file->path));

        // Editing adds files instead of replacing.
        $this->actingAs($this->instructor)
            ->put(route('instructor.courses.lessons.update', [$this->course, $this->module, $lesson]), [
                'title' => 'Cash book', 'content_type' => 'file', 'is_published' => 1,
                'resource_files' => [UploadedFile::fake()->create('four.zip', 10)],
            ])
            ->assertSessionHasNoErrors();
        $this->assertSame(4, $lesson->files()->count());

        $remove = $lesson->files()->where('original_name', 'two.xlsx')->first();
        $this->actingAs($this->instructor)
            ->delete(route('instructor.courses.files.destroy', [$this->course, $remove]))
            ->assertRedirect();

        $this->assertDatabaseMissing('learning_files', ['id' => $remove->id]);
        Storage::disk('local')->assertMissing($remove->path);
        $this->assertSame(['one.pdf', 'three.docx', 'four.zip'], $lesson->files()->pluck('original_name')->all());

        $this->actingAs($this->instructor)
            ->get(route('instructor.courses.manage', [$this->course, 'tab' => 'lessons']))
            ->assertOk()
            ->assertSee('one.pdf')
            ->assertSee('four.zip')
            ->assertDontSee('data-learning-file="'.$remove->id.'"', false);
    }

    public function test_upload_limit_is_ten_files(): void
    {
        $files = array_map(fn ($i) => UploadedFile::fake()->create("f{$i}.pdf", 1, 'application/pdf'), range(1, 11));

        $this->actingAs($this->instructor)
            ->post(route('instructor.courses.lessons.store', [$this->course, $this->module]), [
                'title' => 'Too many', 'content_type' => 'file', 'resource_files' => $files,
            ])
            ->assertSessionHasErrors('resource_files');
    }

    public function test_instructor_attaches_several_files_to_an_assignment(): void
    {
        $this->actingAs($this->instructor)
            ->post(route('instructor.courses.assessments.store', $this->course), [
                'title' => 'Trial balance', 'type' => 'assignment', 'pass_mark' => 50, 'max_attempts' => 2,
                'is_published' => 1,
                'assessment_files' => [
                    UploadedFile::fake()->create('brief.pdf', 10, 'application/pdf'),
                    UploadedFile::fake()->create('template.xlsx', 10),
                ],
            ])
            ->assertSessionHasNoErrors();

        $assessment = Assessment::where('title', 'Trial balance')->firstOrFail();
        $this->assertSame(['brief.pdf', 'template.xlsx'], $assessment->files()->pluck('original_name')->all());

        // Participant API exposes every attachment with its download policy.
        Sanctum::actingAs($this->participant, ['participant']);
        $payload = $this->getJson('/api/v1/participant/courses/'.$this->course->id)->assertOk()->json('course.assessments.0');

        $this->assertCount(2, $payload['attachments']);
        $this->assertFalse($payload['attachments'][0]['downloadable']);
        $this->assertTrue($payload['attachments'][1]['downloadable']);
        $this->assertSame('brief.pdf', $payload['attachment_name']);

        $template = $assessment->files()->where('original_name', 'template.xlsx')->first();
        $this->get("/api/v1/participant/assignments/{$assessment->id}/attachments/{$template->id}")
            ->assertOk()
            ->assertHeader('Content-Disposition', 'attachment; filename=template.xlsx');

        $brief = $assessment->files()->where('original_name', 'brief.pdf')->first();
        $this->get("/api/v1/participant/assignments/{$assessment->id}/attachments/{$brief->id}?download=1")
            ->assertForbidden();
    }

    public function test_participant_submits_several_files_on_the_web_and_can_download_their_own(): void
    {
        $assessment = Assessment::create([
            'course_id' => $this->course->id, 'title' => 'Essay', 'type' => 'assignment',
            'max_attempts' => 1, 'pass_mark' => 50, 'is_published' => true,
        ]);

        $this->actingAs($this->participant)
            ->get(route('learning.assessment.show', $assessment))
            ->assertOk()
            ->assertSee('name="submission_files[]"', false);

        $this->actingAs($this->participant)
            ->post(route('learning.assessment.submit', $assessment), [
                'submission_text' => 'My answer',
                'submission_files' => [
                    UploadedFile::fake()->create('essay.pdf', 10, 'application/pdf'),
                    UploadedFile::fake()->create('workings.docx', 10),
                ],
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $attempt = AssessmentAttempt::where('assessment_id', $assessment->id)->firstOrFail();
        $this->assertSame('My answer', $attempt->submission_text);
        $this->assertSame(['essay.pdf', 'workings.docx'], $attempt->files()->pluck('original_name')->all());

        // The owner downloads their own (even view-only types).
        $own = $attempt->files()->first();
        $response = $this->actingAs($this->participant)->get(route('learning.submissions.files.show', [$own, 'download' => 1]));
        $response->assertOk();
        $this->assertStringStartsWith('attachment', $response->headers->get('Content-Disposition'));

        // Another participant cannot reach it; the course instructor can.
        $other = User::factory()->create(['user_type' => 'participant', 'status' => 'active']);
        $this->actingAs($other)->get(route('learning.submissions.files.show', $own))->assertForbidden();
        $this->actingAs($this->instructor)->get(route('learning.submissions.files.show', $own))->assertOk();

        $this->actingAs($this->instructor)
            ->get(route('instructor.courses.manage', [$this->course, 'tab' => 'submissions']))
            ->assertOk()
            ->assertSee('essay.pdf')
            ->assertSee('workings.docx');
    }

    public function test_participant_api_submission_accepts_several_files(): void
    {
        $assessment = Assessment::create([
            'course_id' => $this->course->id, 'title' => 'Report', 'type' => 'assignment',
            'max_attempts' => 1, 'pass_mark' => 50, 'is_published' => true,
        ]);
        Sanctum::actingAs($this->participant, ['participant']);

        $response = $this->post("/api/v1/participant/assignments/{$assessment->id}/submit", [
            'submission_text' => 'Done',
            'submission_files' => [
                UploadedFile::fake()->create('report.pdf', 10, 'application/pdf'),
                UploadedFile::fake()->create('data.csv', 1),
            ],
        ], ['Accept' => 'application/json']);

        $response->assertCreated()->assertJsonCount(2, 'attempt.files');
        $file = $response->json('attempt.files.0');
        $this->assertSame('report.pdf', $file['name']);
        $this->assertTrue($file['downloadable']);
        $this->assertArrayNotHasKey('path', $file);

        $this->get($file['download_url'])
            ->assertOk()
            ->assertHeader('Content-Disposition', 'attachment; filename=report.pdf');
    }

    /* ---------------- Legacy single-file data ---------------- */

    public function test_existing_single_file_lesson_and_submission_still_show(): void
    {
        $lessonPath = UploadedFile::fake()->create('old.pdf', 5, 'application/pdf')->store('courses/'.$this->course->id.'/lessons', 'public');
        $this->lesson->update(['file_path' => $lessonPath]);

        $assessment = Assessment::create([
            'course_id' => $this->course->id, 'title' => 'Legacy', 'type' => 'assignment',
            'max_attempts' => 1, 'pass_mark' => 50, 'is_published' => true,
        ]);
        $submissionPath = UploadedFile::fake()->create('answer.docx', 5)->store('participant-submissions/x', 'local');
        $attempt = AssessmentAttempt::create([
            'assessment_id' => $assessment->id, 'user_id' => $this->participant->id, 'attempt_number' => 1,
            'submission_file_path' => $submissionPath, 'status' => 'submitted', 'submitted_at' => now(),
        ]);

        $this->actingAs($this->participant)
            ->get(route('learning.lesson.show', $this->lesson))
            ->assertOk()
            ->assertSee('ledgers.pdf');

        $legacy = LearningFile::where('lesson_id', $this->lesson->id)->firstOrFail();
        $this->assertSame('public', $legacy->disk);
        $this->assertSame('lessons.file_path', $legacy->migrated_from);

        $this->actingAs($this->instructor)
            ->get(route('instructor.courses.manage', [$this->course, 'tab' => 'submissions']))
            ->assertOk()
            ->assertSee(basename($submissionPath));
        $this->assertSame(1, AssessmentAttemptFile::where('assessment_attempt_id', $attempt->id)->count());

        // Removing the migrated file clears the legacy column so it does not come back.
        $this->actingAs($this->instructor)
            ->delete(route('instructor.courses.files.destroy', [$this->course, $legacy]))
            ->assertRedirect();
        $this->assertNull($this->lesson->fresh()->file_path);
        $this->assertSame(0, LearningFile::where('lesson_id', $this->lesson->id)->count());
    }
}
