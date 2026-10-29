<?php

namespace Tests\Feature;

use App\Models\Resume;
use App\Models\ResumeExperience;
use App\Models\User;
use App\Services\ResumePdfService;
use App\Support\ResumeTemplates;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ResumeTemplatePreviewTest extends TestCase
{
    use RefreshDatabase;

    private function participant(): User
    {
        return User::factory()->create(['name' => 'Kenneth Wakabala', 'user_type' => 'participant', 'status' => 'active', 'email_verified_at' => now()]);
    }

    public function test_every_template_previews_with_sample_data_and_looks_different(): void
    {
        $this->actingAs($this->participant());
        $pages = [];

        foreach (ResumeTemplates::keys() as $template) {
            $html = $this->get(route('career.resume.template.preview', $template))->assertOk()
                ->assertSee('Amina Nakato')->assertSee('IT Support Intern')->assertSee('class="t-'.$template.'"', false)
                ->assertSee('Jun 2025 to Present')
                ->getContent();
            $pages[$template] = $html;
        }

        $this->assertCount(count(ResumeTemplates::keys()), array_unique($pages), 'Each template renders differently.');
        $this->assertDatabaseCount('resumes', 0);

        $this->get(route('career.resume.template.preview', 'unknown'))->assertNotFound();
    }

    public function test_preview_can_use_her_own_resume_without_changing_it_but_not_someone_elses(): void
    {
        $user = $this->participant();
        $resume = Resume::create(['user_id' => $user->id, 'title' => 'My CV', 'template' => 'classic', 'professional_summary' => 'Kenneth summary text.']);
        ResumeExperience::create(['resume_id' => $resume->id, 'job_title' => 'Network Assistant', 'organisation' => 'MTN']);

        $this->actingAs($user)->get(route('career.resume.template.preview', ['template' => 'technology', 'resume' => $resume->id]))
            ->assertOk()->assertSee('Kenneth summary text.')->assertSee('Network Assistant')->assertSee('class="t-technology"', false)
            ->assertDontSee('Amina Nakato');
        $this->assertSame('classic', $resume->fresh()->template);

        $other = $this->participant();
        $this->actingAs($other)->get(route('career.resume.template.preview', ['template' => 'modern', 'resume' => $resume->id]))->assertNotFound();
    }

    public function test_templates_tab_offers_previews_and_choosing_returns_to_it(): void
    {
        $user = $this->participant();
        $this->actingAs($user)->get(route('career.resume.index'))->assertOk()
            ->assertSee('id="template-preview-modal"', false)
            ->assertSee(route('career.resume.template.preview', 'executive'), false)
            ->assertDontSee('Use this template');

        $resume = Resume::create(['user_id' => $user->id, 'title' => 'My CV', 'template' => 'classic']);
        $this->actingAs($user)->get(route('career.resume.index'))->assertSee('Use this template')->assertSee('data-template="classic"', false);

        $this->actingAs($user)->from(route('career.resume.index'))
            ->put(route('career.resume.template', $resume), ['template' => 'graduate'])
            ->assertRedirect(route('career.resume.index').'#templates')
            ->assertSessionHas('success', '"My CV" now uses the Graduate template.');
        $this->assertSame('graduate', $resume->fresh()->template);
    }

    public function test_downloaded_pdf_uses_the_chosen_template(): void
    {
        if (! class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
            $this->markTestSkipped('DomPDF is not installed.');
        }
        Storage::fake('local');
        $user = $this->participant();

        foreach (ResumeTemplates::keys() as $template) {
            $resume = Resume::create(['user_id' => $user->id, 'title' => 'CV '.$template, 'template' => $template, 'professional_summary' => 'Summary']);
            ResumeExperience::create(['resume_id' => $resume->id, 'job_title' => 'Assistant', 'organisation' => 'Org', 'is_current' => true]);
            $path = app(ResumePdfService::class)->render($resume);
            $this->assertStringStartsWith('%PDF', Storage::disk('local')->get($path), $template.' renders a PDF.');
        }
    }
}
