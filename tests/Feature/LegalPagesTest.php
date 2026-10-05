<?php

namespace Tests\Feature;

use App\Models\CmsPage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LegalPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_privacy_policy_is_public(): void
    {
        $this->assertSame(url('/privacy-policy'), route('legal.privacy'));

        $this->get('/privacy-policy')
            ->assertOk()
            ->assertSee('Privacy Policy');

        $this->assertGuest();
    }

    public function test_terms_page_is_public(): void
    {
        $this->assertSame(url('/terms'), route('legal.terms'));

        $this->get('/terms')
            ->assertOk()
            ->assertSee('Terms of Use');

        $this->assertGuest();
    }

    public function test_published_cms_content_renders_html(): void
    {
        CmsPage::query()->where('slug', 'privacy-policy')->update([
            'published_data' => json_encode([
                'title' => 'Privacy Policy',
                'body' => '<h2>Our commitment</h2><p>We protect your data.</p>',
            ]),
            'published_at' => now(),
        ]);

        $this->get('/privacy-policy')
            ->assertOk()
            ->assertSee('<h2>Our commitment</h2>', false)
            ->assertSee('We protect your data.');
    }

    public function test_public_footer_links_to_both_legal_pages(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee(route('legal.privacy'), false)
            ->assertSee(route('legal.terms'), false);
    }
}
