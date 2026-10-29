<?php

namespace Tests\Feature;

use App\Models\AiIntegration;
use App\Models\CoverLetter;
use App\Models\Resume;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CareerAiSimpleHelpersTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $ai = AiIntegration::forProvider('openai');
        $ai->fill(['provider' => 'openai', 'model' => 'test-model', 'endpoint' => 'https://ai.example.test/v1/chat/completions', 'enabled' => true]);
        $ai->setApiKey('key');
        $ai->save();

        $this->user = User::factory()->create(['name' => 'Kenneth Wakabala', 'user_type' => 'participant', 'status' => 'active', 'email_verified_at' => now()]);
    }

    private function answer(string $text): void
    {
        Http::fake(['ai.example.test/*' => Http::response(['choices' => [['message' => ['content' => $text]]]])]);
    }

    private function resume(): Resume
    {
        return Resume::create(['user_id' => $this->user->id, 'title' => 'IT Resume', 'template' => 'classic', 'professional_summary' => 'Old summary.']);
    }

    public function test_summary_is_drafted_cleanly_and_only_saved_when_she_uses_it(): void
    {
        $resume = $this->resume();
        $this->answer('**Summary:** IT diploma holder — keen on support roles.');

        $this->actingAs($this->user)->from(route('career.resume.edit', $resume))
            ->post(route('career.resume.ai.summary', $resume))
            ->assertRedirect(route('career.resume.edit', $resume).'#details')
            ->assertSessionHas('ai_summary_draft', 'Summary: IT diploma holder, keen on support roles.');
        $this->assertSame('Old summary.', $resume->fresh()->professional_summary);

        $this->actingAs($this->user)->get(route('career.resume.edit', $resume))->assertOk();
        $this->actingAs($this->user)->withSession(['ai_summary_draft' => 'Draft text'])->get(route('career.resume.edit', $resume))
            ->assertSee('Use this summary')->assertSee('Draft text');

        $this->actingAs($this->user)->post(route('career.resume.ai.summary.apply', $resume), ['professional_summary' => 'My edited summary.'])
            ->assertRedirect();
        $this->assertSame('My edited summary.', $resume->fresh()->professional_summary);
        $this->assertTrue($resume->fresh()->ai_enhanced);
    }

    public function test_resume_review_is_plain_simple_advice(): void
    {
        $resume = $this->resume();
        $this->answer("1. **Add a summary** – employers read it first.\n2. Name your *skills* plainly.");

        $this->actingAs($this->user)->from(route('career.resume.edit', $resume))
            ->post(route('career.resume.ai.improve', $resume))
            ->assertSessionHas('ai_suggestion', "1. Add a summary, employers read it first.\n2. Name your skills plainly.");

        Http::assertSent(fn (Request $request) => str_contains($request['messages'][0]['content'], 'new to computers'));
    }

    public function test_cover_letter_needs_only_a_job_title_and_is_saved_without_markdown(): void
    {
        $resume = $this->resume();
        $this->answer("Dear Hiring Manager,\n\nI would like to apply for the **IT Support** role — I enjoy fixing problems.\n\nYours sincerely,\nKenneth");

        $this->actingAs($this->user)->from(route('career.resume.index'))
            ->post(route('career.cover-letter.generate'), ['resume_id' => $resume->id, 'job_title' => 'IT Support', 'tone' => 'warm'])
            ->assertRedirect(route('career.resume.index').'#cover')
            ->assertSessionHasNoErrors();

        $letter = CoverLetter::sole();
        $this->assertSame("Dear Hiring Manager,\n\nI would like to apply for the IT Support role. I enjoy fixing problems.\n\nYours sincerely,\nKenneth", $letter->body);
        Http::assertSent(fn (Request $request) => str_contains($request['messages'][0]['content'], 'warm and friendly')
            && str_contains($request['messages'][1]['content'], 'Kenneth Wakabala'));
    }

    public function test_improving_a_letter_shows_a_draft_and_replaces_it_only_when_accepted(): void
    {
        $letter = CoverLetter::create(['user_id' => $this->user->id, 'title' => 'Bank letter', 'body' => 'i want job at bank. i am hard working.']);
        $this->answer('I would like to apply for a role at your bank — I work hard and learn quickly.');

        $this->actingAs($this->user)->from(route('career.resume.index'))
            ->post(route('career.cover-letter.ai.improve', $letter))
            ->assertRedirect(route('career.resume.index').'#cover')
            ->assertSessionHas('ai_letter_draft', fn ($draft) => $draft['id'] === $letter->id
                && $draft['body'] === 'I would like to apply for a role at your bank. I work hard and learn quickly.');
        $this->assertSame('i want job at bank. i am hard working.', $letter->fresh()->body);

        $this->actingAs($this->user)->withSession(['ai_letter_draft' => ['id' => $letter->id, 'title' => 'Bank letter', 'original' => 'old', 'body' => 'Better letter']])
            ->get(route('career.resume.index'))->assertOk()->assertSee('Use this version')->assertSee('Keep my original');

        $this->actingAs($this->user)->post(route('career.cover-letter.ai.apply', $letter), ['body' => 'Better letter'])->assertRedirect();
        $this->assertSame('Better letter', $letter->fresh()->body);

        $other = User::factory()->create(['user_type' => 'participant', 'status' => 'active', 'email_verified_at' => now()]);
        $this->actingAs($other)->post(route('career.cover-letter.ai.apply', $letter), ['body' => 'Hijack'])->assertForbidden();
        $this->actingAs($other)->post(route('career.cover-letter.ai.improve', $letter))->assertForbidden();
    }
}
