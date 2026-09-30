<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LegalPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_privacy_policy_is_public_and_has_expected_sections(): void
    {
        $this->assertSame(url('/privacy-policy'), route('legal.privacy'));

        $response = $this->get('/privacy-policy');

        $response->assertOk();
        $this->assertGuest();
        $response->assertSeeInOrder([
            'Privacy Policy',
            'Last updated: 30 September 2026',
            'Data we collect',
            'Data stored on your device',
            'Why we use your data',
            'Legal basis and consent',
            'Who we share data with',
            'How long we keep data',
            'How we protect data',
            'Your rights',
            'Deleting your account and data',
            'Children',
            'Changes to this policy',
            'Contact us',
        ]);
        $response->assertSee('Firebase Cloud Messaging');
        $response->assertSee('support@elevateher360.org');
        $response->assertSee(route('legal.terms'), false);
    }

    public function test_terms_page_is_public_and_has_expected_sections(): void
    {
        $this->assertSame(url('/terms'), route('legal.terms'));

        $response = $this->get('/terms');

        $response->assertOk();
        $this->assertGuest();
        $response->assertSeeInOrder([
            'Terms of Use',
            'Last updated: 30 September 2026',
            'Eligibility',
            'Your account',
            'Acceptable use',
            'Our content and intellectual property',
            'Your submissions and content',
            'Availability, offline use and changes',
            'Suspension and termination',
            'Disclaimers',
            'Limitation of liability',
            'Governing law',
            'Contact us',
        ]);
        $response->assertSee(route('legal.privacy'), false);
    }

    public function test_unfilled_legal_details_show_marked_placeholders(): void
    {
        config([
            'legal.organisation_name' => null,
            'legal.jurisdiction' => null,
        ]);

        $this->get('/terms')
            ->assertOk()
            ->assertSee('legal-placeholder', false)
            ->assertSee('Governing law / jurisdiction to be confirmed');
    }

    public function test_configured_legal_details_replace_placeholders(): void
    {
        config([
            'legal.organisation_name' => 'Example Operator Ltd',
            'legal.address' => 'PO Box 1, Example City',
            'legal.jurisdiction' => 'Exampleland',
            'legal.support_email' => 'privacy@example.org',
        ]);

        $this->get('/privacy-policy')
            ->assertOk()
            ->assertSee('Example Operator Ltd')
            ->assertSee('PO Box 1, Example City')
            ->assertSee('privacy@example.org')
            ->assertDontSee('legal-placeholder', false);
    }

    public function test_public_footer_links_to_both_legal_pages(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee(route('legal.privacy'), false)
            ->assertSee(route('legal.terms'), false);
    }
}
