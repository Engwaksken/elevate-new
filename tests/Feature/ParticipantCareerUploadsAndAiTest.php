<?php

namespace Tests\Feature;

use App\Jobs\ParseCoverLetterUpload;
use App\Jobs\ParseResumeUpload;
use App\Models\CoverLetter;
use App\Models\CoverLetterUpload;
use App\Models\Resume;
use App\Models\ResumeUpload;
use App\Models\User;
use App\Services\CareerAiService;
use App\Services\DocumentTextExtractor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ParticipantCareerUploadsAndAiTest extends TestCase
{
    use RefreshDatabase;

    private const BASE = '/api/v1/participant/career';

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Bus::fake([ParseResumeUpload::class, ParseCoverLetterUpload::class]);
    }

    private function participant(): User
    {
        $user = User::factory()->create(['user_type' => 'participant', 'status' => 'active']);
        Sanctum::actingAs($user, ['participant']);
        return $user;
    }

    private function upload(User $user, bool $resume, string $status = 'ready'): ResumeUpload|CoverLetterUpload
    {
        $class = $resume ? ResumeUpload::class : CoverLetterUpload::class;
        $path = 'private/career-uploads/'.$user->id.'/'.($resume ? 'resume' : 'letter').'.pdf';
        Storage::disk('local')->put($path, '%PDF-1.4 Original document');
        return $class::create([
            'user_id' => $user->id, 'original_name' => $resume ? 'Existing Resume.pdf' : 'Existing Letter.pdf',
            'stored_name' => basename($path), 'mime_type' => 'application/pdf', 'file_size' => 26,
            'path' => $path, 'status' => $status, 'extracted_text' => 'Original extracted text',
            'parsed_data' => $resume ? ['professional_summary' => 'Original summary', 'skills' => ['Dart']]
                : ['body' => 'Original cover letter'],
        ]);
    }

    public function test_uploads_are_private_and_dispatch_the_existing_parser_jobs(): void
    {
        $user = $this->participant();
        foreach (['resume', 'cover-letter'] as $type) {
            $response = $this->postJson(self::BASE.'/uploads/'.$type, ['file' => UploadedFile::fake()->create('existing.pdf', 50, 'application/pdf')])
                ->assertCreated()->assertJsonPath('upload.status', 'uploaded')->assertJsonMissingPath('upload.path');
            $id = $response->json('upload.id');
            $class = $type === 'resume' ? ResumeUpload::class : CoverLetterUpload::class;
            $upload = $class::findOrFail($id);
            $this->assertSame($user->id, $upload->user_id);
            Storage::disk('local')->assertExists($upload->path);
        }
        Bus::assertDispatched(ParseResumeUpload::class);
        Bus::assertDispatched(ParseCoverLetterUpload::class);
        $this->getJson(self::BASE.'/documents')->assertJsonCount(1, 'resume_uploads')->assertJsonCount(1, 'cover_letter_uploads')->assertJsonMissingPath('resume_uploads.0.path');
    }

    public function test_invalid_formats_and_oversize_files_are_rejected(): void
    {
        $this->participant();
        $this->postJson(self::BASE.'/uploads/resume', ['file' => UploadedFile::fake()->create('script.txt', 5, 'text/plain')])->assertUnprocessable();
        $this->postJson(self::BASE.'/uploads/resume', ['file' => UploadedFile::fake()->create('large.pdf', 10241, 'application/pdf')])->assertUnprocessable();
        $this->assertDatabaseCount('resume_uploads', 0);
        Bus::assertNothingDispatched();
    }

    public function test_reviewed_resume_and_letter_imports_are_editable_and_idempotent(): void
    {
        $user = $this->participant();
        foreach ([true, false] as $isResume) {
            $type = $isResume ? 'resume' : 'cover-letter';
            $upload = $this->upload($user, $isResume);
            $this->getJson(self::BASE.'/uploads/'.$type.'/'.$upload->id)->assertOk()
                ->assertJsonPath('upload.extracted_text', 'Original extracted text');
            $fields = $isResume ? ['title' => 'Reviewed Resume', 'template' => 'modern', 'professional_summary' => 'Corrected summary', 'skills' => [['skill' => 'Dart', 'resume_id' => 999]]]
                : ['title' => 'Reviewed Letter', 'body' => 'Corrected body'];
            $response = $this->postJson(self::BASE.'/uploads/'.$type.'/'.$upload->id.'/import', ['data' => $fields + ['user_id' => 999, 'source' => 'ai']])
                ->assertCreated()->assertJsonPath('document.user_id', $user->id)->assertJsonPath('document.source', 'uploaded');
            $documentId = $response->json('document.id');
            $this->postJson(self::BASE.'/uploads/'.$type.'/'.$upload->id.'/import', ['data' => $fields])->assertCreated()->assertJsonPath('document.id', $documentId);
            $this->get(self::BASE.'/uploads/'.$type.'/'.$upload->id.'/original')->assertOk()->assertDownload($upload->original_name);
            $this->deleteJson(self::BASE.'/uploads/'.$type.'/'.$upload->id)->assertNoContent();
            Storage::disk('local')->assertMissing($upload->path);
        }
        $this->assertDatabaseCount('resumes', 1);
        $this->assertDatabaseCount('cover_letters', 1);
        $this->assertDatabaseHas('resume_skills', ['skill' => 'Dart', 'resume_id' => Resume::sole()->id]);
    }

    public function test_pending_uploads_cannot_be_imported_or_deleted_and_failed_uploads_can_retry(): void
    {
        $user = $this->participant();
        $upload = $this->upload($user, true, 'processing');
        $this->postJson(self::BASE.'/uploads/resume/'.$upload->id.'/import', ['data' => ['title' => 'CV', 'template' => 'classic']])->assertUnprocessable();
        $this->deleteJson(self::BASE.'/uploads/resume/'.$upload->id)->assertUnprocessable();
        $this->postJson(self::BASE.'/uploads/resume/'.$upload->id.'/retry')->assertUnprocessable();
        $upload->update(['status' => 'failed']);
        $this->postJson(self::BASE.'/uploads/resume/'.$upload->id.'/retry')->assertOk()->assertJsonPath('upload.status', 'uploaded');
        Bus::assertDispatched(ParseResumeUpload::class);
    }

    public function test_another_participant_cannot_read_import_retry_or_delete_uploaded_files(): void
    {
        $owner = $this->participant();
        $uploads = ['resume' => $this->upload($owner, true), 'cover-letter' => $this->upload($owner, false)];
        $this->participant();
        foreach ($uploads as $type => $upload) {
            $path = self::BASE.'/uploads/'.$type.'/'.$upload->id;
            $this->getJson($path)->assertForbidden();
            $this->get($path.'/original')->assertForbidden();
            $this->postJson($path.'/import', ['data' => ['title' => 'Bad']])->assertForbidden();
            $this->postJson($path.'/retry')->assertForbidden();
            $this->deleteJson($path)->assertForbidden();
        }
        $this->getJson(self::BASE.'/documents')->assertJsonCount(0, 'resume_uploads')->assertJsonCount(0, 'cover_letter_uploads');
    }

    public function test_ai_returns_a_reviewable_draft_without_saving_it_and_uses_current_editor_content(): void
    {
        $user = $this->participant();
        $resume = Resume::create(['user_id' => $user->id, 'title' => 'CV', 'template' => 'classic', 'professional_summary' => 'Original summary']);
        $this->mock(CareerAiService::class, function ($mock) use ($user) {
            $mock->shouldReceive('generate')->once()->withArgs(fn ($feature, $system, $payload, $id) =>
                $feature === 'resume_improvement' && $id === $user->id && str_contains($payload, 'Unsaved summary'))
                ->andReturn(['text' => '{"professional_summary":"Improved summary","user_id":999}']);
        });
        $this->postJson(self::BASE.'/documents/resume/'.$resume->id.'/ai', [
            'action' => 'improve', 'data' => ['title' => 'CV', 'template' => 'classic', 'professional_summary' => 'Unsaved summary'],
        ])->assertOk()->assertJsonPath('draft.professional_summary', 'Improved summary')->assertJsonMissingPath('draft.user_id');
        $this->assertSame('Original summary', $resume->fresh()->professional_summary);
    }

    public function test_cover_letter_ai_tailoring_keeps_existing_employer_details(): void
    {
        $user = $this->participant();
        $letter = CoverLetter::create(['user_id' => $user->id, 'title' => 'Letter', 'employer_name' => 'Example', 'body' => 'Original body']);
        $this->mock(CareerAiService::class, fn ($mock) => $mock->shouldReceive('generate')->once()->andReturn(['text' => '{"body":"Revised truthful letter","employer_name":"Invented"}']));
        $this->postJson(self::BASE.'/documents/cover-letter/'.$letter->id.'/ai', ['action' => 'tailor', 'job_description' => 'A software role'])
            ->assertOk()->assertJsonPath('draft.body', 'Revised truthful letter')->assertJsonPath('draft.employer_name', 'Example');
        $this->assertSame('Original body', $letter->fresh()->body);
    }

    public function test_ai_failures_and_invalid_drafts_leave_documents_unchanged(): void
    {
        $user = $this->participant();
        $letter = CoverLetter::create(['user_id' => $user->id, 'title' => 'Letter', 'body' => 'Original body']);
        $this->mock(CareerAiService::class, function ($mock) {
            $mock->shouldReceive('generate')->once()->andThrow(new \RuntimeException('AI_UNAVAILABLE'));
            $mock->shouldReceive('generate')->once()->andReturn(['text' => '{"body":[]}']);
        });
        foreach ([1, 2] as $attempt) $this->postJson(self::BASE.'/documents/cover-letter/'.$letter->id.'/ai', ['action' => 'improve'])->assertStatus(503);
        $this->assertSame('Original body', $letter->fresh()->body);
    }

    public function test_ai_requires_ownership_and_tailoring_requires_a_job_description(): void
    {
        $user = $this->participant();
        $resume = Resume::create(['user_id' => $user->id, 'title' => 'CV', 'template' => 'classic']);
        $this->postJson(self::BASE.'/documents/resume/'.$resume->id.'/ai', ['action' => 'tailor'])->assertUnprocessable();
        $this->participant();
        $this->postJson(self::BASE.'/documents/resume/'.$resume->id.'/ai', ['action' => 'improve'])->assertForbidden();
    }

    public function test_plain_text_is_preserved_when_ai_parsing_is_unavailable(): void
    {
        $user = $this->participant();
        $extractor = \Mockery::mock(DocumentTextExtractor::class);
        $extractor->shouldReceive('extract')->twice()->andReturn('Existing document facts');
        $ai = \Mockery::mock(CareerAiService::class);
        $ai->shouldReceive('generate')->twice()->andThrow(new \RuntimeException('AI_UNAVAILABLE'));
        $resume = $this->upload($user, true, 'uploaded');
        $letter = $this->upload($user, false, 'uploaded');
        (new ParseResumeUpload($resume->id))->handle($extractor, $ai);
        (new ParseCoverLetterUpload($letter->id))->handle($extractor, $ai);
        $this->assertSame('ready', $resume->fresh()->status);
        $this->assertSame('Existing document facts', $resume->fresh()->parsed_data['professional_summary']);
        $this->assertSame('Existing document facts', $letter->fresh()->parsed_data['body']);
    }

    public function test_word_extraction_includes_text_runs_and_table_cells(): void
    {
        $word = new \PhpOffice\PhpWord\PhpWord();
        $section = $word->addSection();
        $section->addTextRun()->addText('Professional summary');
        $section->addTable()->addRow()->addCell()->addText('Experience in a table');
        Storage::disk('local')->put('fixture.docx', '');
        $path = Storage::disk('local')->path('fixture.docx');
        \PhpOffice\PhpWord\IOFactory::createWriter($word, 'Word2007')->save($path);
        $text = app(DocumentTextExtractor::class)->extract($path, 'docx');
        $this->assertStringContainsString('Professional summary', $text);
        $this->assertStringContainsString('Experience in a table', $text);
    }
}
