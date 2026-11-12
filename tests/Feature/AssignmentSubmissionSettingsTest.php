<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\Course;
use App\Models\Enrolment;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Instructor-chosen submission formats and file limits, required attachments
 * and the "Publish now" / "Save draft" actions.
 */
class AssignmentSubmissionSettingsTest extends TestCase
{
    use RefreshDatabase;

    private Course $course;
    private User $participant;
    private User $instructor;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');

        $this->course = Course::create(['title' => 'Media Skills', 'status' => 'published']);
        $this->participant = User::factory()->create(['user_type' => 'participant', 'status' => 'active']);
        Enrolment::create(['course_id' => $this->course->id, 'user_id' => $this->participant->id, 'status' => 'enrolled']);

        $this->instructor = User::factory()->create(['user_type' => 'staff', 'status' => 'active']);
        $this->instructor->roles()->attach(Role::firstOrCreate(['slug' => 'instructor'], ['name' => 'Instructor']));
        $this->course->instructors()->attach($this->instructor->id);
    }

    private function assignment(array $attributes = []): Assessment
    {
        return Assessment::create($attributes + [
            'course_id' => $this->course->id, 'title' => 'Pitch', 'type' => 'assignment',
            'max_attempts' => 5, 'pass_mark' => 50, 'is_published' => true,
        ]);
    }

    private function form(array $overrides = []): array
    {
        return $overrides + [
            'type' => 'assignment', 'title' => 'Record your pitch', 'pass_mark' => 50, 'max_attempts' => 1,
            'submission_formats' => ['video', 'audio'], 'max_submission_files' => 2,
        ];
    }

    public function test_save_draft_keeps_the_assignment_hidden_with_the_chosen_formats(): void
    {
        $this->actingAs($this->instructor)
            ->post(route('instructor.courses.assessments.store', $this->course), $this->form(['publish' => '0', 'allow_link_submissions' => '1']))
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success', fn ($message) => str_contains($message, 'draft'));

        $assessment = Assessment::firstOrFail();
        $this->assertFalse($assessment->is_published);
        $this->assertSame(['video', 'audio'], $assessment->submission_formats);
        $this->assertSame(2, $assessment->maxSubmissionFiles());
        $this->assertTrue((bool) $assessment->allow_link_submissions);
        $this->assertContains('mp4', $assessment->submissionExtensions());
        $this->assertNotContains('pdf', $assessment->submissionExtensions());

        $this->post(route('instructor.courses.assessments.publish', [$this->course, $assessment]))->assertSessionHasNoErrors();
        $this->assertTrue($assessment->fresh()->is_published);
    }

    public function test_publish_now_publishes_and_unpublish_moves_back_to_drafts(): void
    {
        $this->actingAs($this->instructor)
            ->post(route('instructor.courses.assessments.store', $this->course), $this->form(['publish' => '1']))
            ->assertSessionHasNoErrors();
        $assessment = Assessment::firstOrFail();
        $this->assertTrue($assessment->is_published);

        $this->put(route('instructor.courses.assessments.update', [$this->course, $assessment]), $this->form(['publish' => '0', 'max_submission_files' => 3]))
            ->assertSessionHasNoErrors();
        $this->assertFalse($assessment->fresh()->is_published);
        $this->assertSame(3, $assessment->fresh()->maxSubmissionFiles());
    }

    public function test_instructor_page_shows_publish_and_draft_buttons_and_format_choices(): void
    {
        $this->actingAs($this->instructor)
            ->get(route('instructor.courses.manage', [$this->course, 'tab' => 'assignments']))
            ->assertOk()
            ->assertSee('Publish now')
            ->assertSee('Save draft')
            ->assertSee('name="submission_formats[]"', false)
            ->assertSee('name="max_submission_files"', false);
    }

    public function test_web_submission_requires_a_file(): void
    {
        $assessment = $this->assignment();

        $this->actingAs($this->participant)
            ->post(route('learning.assessment.submit', $assessment), ['submission_text' => 'Only words'])
            ->assertSessionHasErrors('submission_files');

        $this->assertSame(0, AssessmentAttempt::count());
    }

    public function test_assignment_can_accept_and_store_web_links_without_a_file(): void
    {
        $assessment = $this->assignment(['allow_link_submissions' => true]);

        $this->actingAs($this->participant)
            ->get(route('learning.assessment.show', $assessment))
            ->assertOk()
            ->assertSee('name="submission_links"', false);

        $this->post(route('learning.assessment.submit', $assessment), [
            'submission_links' => "https://example.com/project\nhttps://github.com/example/project",
        ])->assertSessionHasNoErrors();

        $this->assertSame([
            'https://example.com/project',
            'https://github.com/example/project',
        ], AssessmentAttempt::firstOrFail()->submission_links);
    }

    public function test_assignment_rejects_links_when_the_instructor_has_not_enabled_them(): void
    {
        $assessment = $this->assignment();

        $this->actingAs($this->participant)
            ->post(route('learning.assessment.submit', $assessment), ['submission_links' => 'https://example.com'])
            ->assertSessionHasErrors('submission_files');

        $this->assertSame(0, AssessmentAttempt::count());
    }

    public function test_web_submission_enforces_formats_and_file_limit(): void
    {
        $assessment = $this->assignment(['submission_formats' => ['image'], 'max_submission_files' => 1]);

        $this->actingAs($this->participant)
            ->post(route('learning.assessment.submit', $assessment), ['submission_files' => [UploadedFile::fake()->create('essay.pdf', 10, 'application/pdf')]])
            ->assertSessionHasErrors('submission_files.0');

        $this->post(route('learning.assessment.submit', $assessment), ['submission_files' => [
            UploadedFile::fake()->image('one.jpg'), UploadedFile::fake()->image('two.jpg'),
        ]])->assertSessionHasErrors('submission_files');

        $this->post(route('learning.assessment.submit', $assessment), ['submission_files' => [UploadedFile::fake()->image('poster.png')]])
            ->assertSessionHasNoErrors();

        $this->assertSame(1, AssessmentAttempt::count());
        $this->assertSame(1, AssessmentAttempt::first()->files()->count());
    }

    public function test_web_form_limits_the_picker_to_the_chosen_formats(): void
    {
        $assessment = $this->assignment(['submission_formats' => ['audio'], 'max_submission_files' => 1]);

        $this->actingAs($this->participant)
            ->get(route('learning.assessment.show', $assessment))
            ->assertOk()
            ->assertSee('accept=".mp3,.m4a,.wav,.aac,.ogg,.amr"', false)
            ->assertSee('data-max-files="1"', false);
    }

    public function test_api_presents_settings_and_stores_a_single_legacy_file(): void
    {
        $assessment = $this->assignment(['submission_formats' => ['video'], 'max_submission_files' => 2]);
        Sanctum::actingAs($this->participant, ['participant']);

        $this->getJson('/api/v1/participant/assignments')
            ->assertOk()
            ->assertJsonFragment(['requires_attachment' => true, 'max_submission_files' => 2])
            ->assertJsonFragment(['allowed_extensions' => ['mp4', 'mov', 'webm', '3gp', 'm4v']]);

        $this->withHeader('Accept', 'application/json')
            ->post("/api/v1/participant/assignments/{$assessment->id}/submit", ['submission_text' => 'No file'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('submission_files');

        $this->withHeader('Accept', 'application/json')
            ->post("/api/v1/participant/assignments/{$assessment->id}/submit", ['submission_file' => UploadedFile::fake()->create('pitch.mp4', 100, 'video/mp4')])
            ->assertCreated();

        // Regression: a single "submission_file" used to be dropped silently.
        $this->assertSame(1, AssessmentAttempt::firstOrFail()->files()->count());
    }

    public function test_api_accepts_links_without_a_file_when_enabled(): void
    {
        $assessment = $this->assignment(['allow_link_submissions' => true]);
        Sanctum::actingAs($this->participant, ['participant']);

        $this->getJson('/api/v1/participant/assignments')
            ->assertOk()->assertJsonFragment(['allow_link_submissions' => true]);
        $this->withHeader('Accept', 'application/json')
            ->post("/api/v1/participant/assignments/{$assessment->id}/submit", [
                'submission_links' => ['https://example.com/work'],
            ])->assertCreated()->assertJsonPath('attempt.submission_links.0', 'https://example.com/work');
    }

    public function test_instructor_can_download_template_import_questions_and_edit_them(): void
    {
        $quiz = Assessment::create([
            'course_id' => $this->course->id, 'title' => 'Marketing basics', 'type' => 'quiz',
            'max_attempts' => 2, 'pass_mark' => 50, 'is_published' => false,
        ]);

        $template = $this->actingAs($this->instructor)
            ->get(route('instructor.courses.assessments.questions.template', [$this->course, $quiz]))
            ->assertOk();
        $this->assertStringContainsString('option_a', $template->streamedContent());

        $csv = "question_type,question_text,option_a,option_b,option_c,option_d,correct_option,marks\n"
            ."multiple_choice,Which planet is red?,Earth,Mars,Venus,Jupiter,B,2\n"
            ."true_false,The Earth orbits the Sun.,,,,,true,1\n";
        $this->post(route('instructor.courses.assessments.questions.import', [$this->course, $quiz]), [
            'questions_file' => UploadedFile::fake()->createWithContent('questions.csv', $csv),
        ])->assertSessionHasNoErrors()->assertSessionHas('success', '2 question(s) imported.');

        $question = $quiz->questions()->orderBy('position')->firstOrFail();
        $this->assertSame('1', (string) data_get($question->correct_answer, 'value'));
        $this->get(route('instructor.courses.manage', [$this->course, 'tab' => 'quizzes']))
            ->assertOk()->assertSee('Preview &amp; manage questions', false)->assertSee('Which planet is red?');

        $this->put(route('instructor.courses.assessments.questions.update', [$this->course, $quiz, $question]), [
            'question_type' => 'multiple_choice', 'question_text' => 'Which planet is closest to the Sun?',
            'options_text' => "Earth\nMercury\nVenus", 'correct_value' => 'Mercury', 'marks' => 3,
        ])->assertSessionHasNoErrors();
        $this->assertSame('Which planet is closest to the Sun?', $question->fresh()->question_text);
    }

    public function test_admin_builder_saves_drafts_and_can_publish_them(): void
    {
        $admin = User::factory()->create(['user_type' => 'staff', 'status' => 'active']);
        $admin->roles()->attach(Role::firstOrCreate(['slug' => 'super-admin'], ['name' => 'Super Admin']));

        $this->actingAs($admin)
            ->post(route('admin.elearning.assessments.store', $this->course), $this->form(['publish' => '0']))
            ->assertSessionHasNoErrors();
        $assessment = Assessment::firstOrFail();
        $this->assertFalse($assessment->is_published);
        $this->assertSame(['video', 'audio'], $assessment->submission_formats);

        $this->patch(route('admin.elearning.assessments.publish', [$this->course, $assessment]), ['publish' => 1])->assertSessionHasNoErrors();
        $this->assertTrue($assessment->fresh()->is_published);
    }
}
