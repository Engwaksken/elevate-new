<?php

namespace Tests\Feature;

use App\Models\CmsPage;
use App\Models\Role;
use App\Models\User;
use App\Services\CmsContentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CmsLegalFaqAndDashboardTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $user = User::factory()->create(['user_type' => 'staff', 'status' => 'active']);
        $user->roles()->attach(Role::firstOrCreate(['slug' => 'administrator'], ['name' => 'Administrator']));
        $this->actingAs($user);
        return $user;
    }

    public function test_staff_opening_participant_dashboard_go_to_admin_dashboard(): void
    {
        $this->admin();
        // The home page's "Continue to your dashboard" and the header button both reach the admin dashboard.
        $this->get('/')->assertOk()->assertSee(route('admin.dashboard'), false);
        $this->get('/dashboard')->assertRedirect(route('admin.dashboard'));
    }

    public function test_built_in_legal_pages_render_professionally_with_contents(): void
    {
        $this->get('/privacy-policy')->assertOk()
            ->assertSee('class="legal-doc__hero"', false)
            ->assertSee('On this page')
            ->assertSee('href="#who-we-are"', false)
            ->assertSee('<section id="who-we-are">', false)
            ->assertSee('Last updated')
            ->assertSee('Print / save as PDF')
            ->assertSee('Related document');
        $this->get('/terms')->assertOk()->assertSee('Terms of Use')->assertSee('class="legal-doc__toc"', false);
    }

    public function test_admin_edits_prefilled_legal_text_and_publishes_it(): void
    {
        $this->admin();
        $page = CmsPage::where('slug', 'privacy-policy')->first();
        $this->get(route('admin.cms.edit', $page))->assertOk()
            ->assertSee('Draft text loaded.')
            ->assertSee('1. Who we are')
            ->assertDontSee('data-cms-builder', false);

        $body = "## Who we are\nWe are **Example Org**.\n\n## Your rights\n- Access\n- Deletion\n\n<script>alert(1)</script>";
        $this->put(route('admin.cms.update', $page), ['title' => 'Privacy Policy', 'body' => $body, 'action' => 'publish'])->assertSessionHasNoErrors();
        $this->get(route('admin.cms.preview', $page))->assertOk()->assertSee('class="legal-doc__body"', false);

        auth()->logout();
        $this->get('/privacy-policy')->assertOk()
            ->assertSee('<h2 id="who-we-are">Who we are</h2>', false)
            ->assertSee('href="#your-rights"', false)
            ->assertSee('Example Org')
            ->assertSee(now()->format('j F Y'))
            ->assertDontSee('<script>alert(1)</script>', false);
    }

    public function test_faq_page_shows_suggested_questions_with_structured_data(): void
    {
        $count = count(CmsContentService::defaultFaqs());
        $this->assertGreaterThanOrEqual(15, $count);
        $this->get('/faqs')->assertOk()
            ->assertSee('How do I enrol in a course?')
            ->assertSee('data-faq-search', false)
            ->assertSee('Getting started')
            ->assertSee('"@type":"FAQPage"', false)
            ->assertSee('Still have a question?');
    }

    public function test_admin_edits_and_publishes_faqs(): void
    {
        $this->admin();
        $page = CmsPage::where('slug', 'faqs')->first();
        $this->get(route('admin.cms.edit', $page))->assertOk()
            ->assertSee('suggested questions have been loaded')
            ->assertSee('value="What is ElevateHer360?"', false);

        $this->put(route('admin.cms.update', $page), ['title' => 'Help', 'action' => 'publish', 'faqs_submitted' => 1, 'faqs' => [
            ['category' => 'Billing', 'question' => 'Is it free?', 'answer' => 'Yes, **completely** free.'],
            ['category' => '', 'question' => 'Who runs it?', 'answer' => 'Our team. <script>x</script>'],
            ['category' => '', 'question' => '', 'answer' => ''],
        ]])->assertSessionHasNoErrors();
        $this->assertCount(2, $page->fresh()->published_data['settings']['faqs']);

        auth()->logout();
        $this->get('/faqs')->assertOk()
            ->assertSee('Is it free?')
            ->assertSee('<strong>completely</strong>', false)
            ->assertSee('General')
            ->assertDontSee('How do I enrol in a course?')
            ->assertDontSee('<script>x</script>', false);

        $this->admin();
        $this->put(route('admin.cms.update', $page), ['title' => 'Help', 'action' => 'publish', 'faqs_submitted' => 1, 'faqs' => [['question' => 'No answer?', 'answer' => '']]])
            ->assertSessionHasErrors('faqs');
    }
}
