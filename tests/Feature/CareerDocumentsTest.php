<?php

namespace Tests\Feature;

use App\Models\CoverLetter;
use App\Models\Resume;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CareerDocumentsTest extends TestCase
{
    use RefreshDatabase;

    private const BASE = '/api/v1/participant/career';

    private function participant(): User
    {
        $user = User::factory()->create(['user_type' => 'participant', 'status' => 'active']);
        Sanctum::actingAs($user, ['participant']);

        return $user;
    }

    public function test_mobile_documents_are_available_in_the_web_centre_and_can_be_edited(): void
    {
        $user = $this->participant();
        $response = $this->postJson(self::BASE.'/resumes', [
            'title' => 'Digital CV', 'template' => 'modern', 'professional_summary' => 'Developer',
            'experiences' => [['job_title' => 'Developer', 'organisation' => 'Example', 'user_id' => 999]],
            'education' => [['institution' => 'College', 'qualification' => 'Diploma']],
            'skills' => [['skill' => 'Dart']],
            'user_id' => 999,
        ])->assertCreated()->assertJsonPath('resume.user_id', $user->id);
        $id = $response->json('resume.id');
        $this->actingAs($user)->get(route('career.resume.index'))->assertOk()->assertSee('Digital CV');
        $this->getJson(self::BASE.'/documents')->assertOk()->assertJsonPath('resumes.0.skills.0.skill', 'Dart');
        $this->putJson(self::BASE.'/resumes/'.$id, [
            'title' => 'Updated CV', 'template' => 'classic', 'skills' => [],
        ])->assertOk();
        $resume = Resume::findOrFail($id);
        $this->assertSame(0, $resume->skills()->count());
        $this->assertSame(1, $resume->experiences()->count());
        $this->assertSame(1, $resume->education()->count());

        $letter = $this->postJson(self::BASE.'/cover-letters', [
            'title' => 'Application', 'resume_id' => $id, 'body' => 'Dear Hiring Manager',
        ])->assertCreated()->json('cover_letter.id');
        $this->putJson(self::BASE.'/cover-letters/'.$letter, ['title' => 'New application', 'body' => 'Updated body'])->assertOk();
        $this->actingAs($user)->get(route('career.resume.index'))->assertSee('New application');
        $this->deleteJson(self::BASE.'/cover-letters/'.$letter)->assertNoContent();
        $this->deleteJson(self::BASE.'/resumes/'.$id)->assertNoContent();
    }

    public function test_documents_and_links_cannot_be_accessed_by_another_participant(): void
    {
        $owner = $this->participant();
        $resume = Resume::create(['user_id' => $owner->id, 'title' => 'Private CV', 'template' => 'classic']);
        $letter = CoverLetter::create(['user_id' => $owner->id, 'title' => 'Private letter', 'body' => 'Private']);
        $this->participant();
        $this->getJson(self::BASE.'/documents')->assertJsonCount(0, 'resumes')->assertJsonCount(0, 'cover_letters');
        foreach (['resumes' => $resume, 'cover-letters' => $letter] as $type => $document) {
            $this->getJson(self::BASE."/{$type}/{$document->id}/download")->assertForbidden();
            $this->putJson(self::BASE."/{$type}/{$document->id}", ['title' => 'Hijacked', 'body' => 'x', 'template' => 'classic'])->assertForbidden();
            $this->deleteJson(self::BASE."/{$type}/{$document->id}")->assertForbidden();
        }
        $this->postJson(self::BASE."/documents/resume/{$resume->id}/share")->assertForbidden();
        $this->postJson(self::BASE."/documents/cover-letter/{$letter->id}/share")->assertForbidden();
        $this->postJson(self::BASE.'/cover-letters', ['title' => 'Bad link', 'body' => 'x', 'resume_id' => $resume->id])->assertUnprocessable();
    }

    public function test_pdf_downloads_and_signed_sharing_expire_and_reject_tampering(): void
    {
        Storage::fake('local');
        $owner = $this->participant();
        $resume = Resume::create(['user_id' => $owner->id, 'title' => 'My CV', 'template' => 'classic']);
        $letter = CoverLetter::create(['user_id' => $owner->id, 'title' => 'My Letter', 'body' => "Dear employer,\nThank you."]);
        $this->get(self::BASE."/resumes/{$resume->id}/download")->assertOk()->assertDownload('My_CV.pdf');
        $this->get(self::BASE."/cover-letters/{$letter->id}/download")->assertOk()->assertDownload('My_Letter.pdf');
        foreach (['resume' => $resume, 'cover-letter' => $letter] as $type => $document) {
            $url = $this->postJson(self::BASE."/documents/{$type}/{$document->id}/share")->assertOk()->json('url');
            $this->get($url)->assertOk()->assertHeader('X-Robots-Tag', 'noindex, nofollow');
            $this->get($url.'&tampered=1')->assertForbidden();
            $this->travel(8)->days();
            $this->get($url)->assertForbidden();
            $this->travelBack();
        }
        $url = $this->actingAs($owner)->postJson(route('career.documents.share', ['type' => 'cover-letter', 'id' => $letter->id]))->assertOk()->json('url');
        $letter->delete();
        $this->get($url)->assertNotFound();
    }

    public function test_invalid_resume_sections_are_rejected_without_partial_changes(): void
    {
        $user = $this->participant();
        $resume = Resume::create(['user_id' => $user->id, 'title' => 'CV', 'template' => 'classic']);
        $resume->skills()->create(['skill' => 'Existing']);
        $this->putJson(self::BASE.'/resumes/'.$resume->id, [
            'title' => 'Invalid', 'template' => 'classic', 'skills' => [['skill' => '']],
        ])->assertUnprocessable();
        $this->assertSame('CV', $resume->fresh()->title);
        $this->assertSame('Existing', $resume->skills()->first()->skill);
    }
}
