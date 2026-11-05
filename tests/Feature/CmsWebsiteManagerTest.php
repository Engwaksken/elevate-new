<?php

namespace Tests\Feature;

use App\Models\CmsPage;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CmsWebsiteManagerTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $user = User::factory()->create(['user_type' => 'staff', 'status' => 'active']);
        $user->roles()->attach(Role::firstOrCreate(['slug' => 'administrator'], ['name' => 'Administrator']));
        $this->actingAs($user);
        return $user;
    }

    private function publish(string $slug, array $data): CmsPage
    {
        $page = CmsPage::where('slug', $slug)->firstOrFail();
        $this->put(route('admin.cms.update', $page), $data + ['title' => $page->title, 'action' => 'publish'])
            ->assertSessionHasNoErrors();
        return $page->fresh();
    }

    public function test_admin_screens_render(): void
    {
        $this->admin();
        $this->get(route('admin.cms.index'))->assertOk()->assertSee('Home page')->assertSee('Header &amp; site identity', false)->assertSee('Footer');
        foreach (['home', 'site-header', 'site-footer', 'faqs'] as $slug) {
            $this->get(route('admin.cms.edit', CmsPage::where('slug', $slug)->first()))->assertOk();
        }
        $this->get(route('admin.cms.edit', CmsPage::where('slug', 'home')->first()))
            ->assertSee('Background &amp; layout', false)->assertSee('Image grid')->assertSee('Load starter home page');
        $this->get(route('admin.cms.edit', CmsPage::where('slug', 'site-header')->first()))->assertSee('Announcement bar')->assertSee('Header logo');
        $this->get(route('admin.cms.edit', CmsPage::where('slug', 'site-footer')->first()))->assertSee('Social media')->assertSee('Link columns');
    }

    public function test_sections_render_image_video_colour_and_gradient_backgrounds(): void
    {
        $this->admin();
        $this->publish('home', ['sections' => [
            ['type' => 'hero', 'title' => 'Photo hero', 'style' => ['bg' => 'image', 'bg_image' => 'https://cdn.example.com/hero.jpg', 'overlay_color' => '#000000', 'overlay_opacity' => 40, 'padding' => 'xl', 'anchor' => 'top']],
            ['type' => 'text', 'title' => 'Video section', 'content' => 'Hello', 'style' => ['bg' => 'video', 'bg_video' => 'https://cdn.example.com/loop.mp4']],
            ['type' => 'text', 'title' => 'YouTube bg', 'content' => 'x', 'style' => ['bg' => 'video', 'bg_video' => 'https://www.youtube.com/watch?v=abcDEF12345']],
            ['type' => 'cta', 'title' => 'Gradient cta', 'style' => ['bg' => 'gradient', 'bg_color' => '#112233', 'bg_color2' => '#445566', 'gradient_angle' => 90, 'text_tone' => 'light']],
            ['type' => 'text', 'title' => 'Secret hidden section', 'content' => 'x', 'hidden' => 1],
        ]]);

        $html = $this->get('/')->assertOk()
            ->assertSee('background-image:url(&quot;https://cdn.example.com/hero.jpg&quot;)', false)
            ->assertSee('opacity:0.4', false)
            ->assertSee('cms-pad-xl', false)
            ->assertSee('id="top"', false)
            ->assertSee('<source src="https://cdn.example.com/loop.mp4" type="video/mp4">', false)
            ->assertSee('https://www.youtube.com/embed/abcDEF12345?autoplay=1&amp;mute=1', false)
            ->assertSee('linear-gradient(90deg,#112233,#445566)', false)
            ->assertSee('cms-tone-light', false)
            ->assertDontSee('Secret hidden section')
            ->getContent();
        // Image backgrounds default to light text.
        $this->assertMatchesRegularExpression('/cms-block-hero[^"]*cms-tone-light/', $html);
    }

    public function test_unsafe_style_values_are_dropped(): void
    {
        $this->admin();
        $page = $this->publish('home', ['sections' => [
            ['type' => 'text', 'title' => 'One', 'content' => 'x', 'style' => ['bg' => 'image', 'bg_image' => 'https://x.test/a.jpg");background:url(javascript:alert(1)']],
            ['type' => 'text', 'title' => 'Two', 'content' => 'x', 'style' => ['bg' => 'color', 'bg_color' => 'red;position:fixed']],
            ['type' => 'text', 'title' => 'Three', 'content' => 'x', 'style' => ['bg' => 'video', 'bg_video' => 'javascript:alert(1)//.mp4', 'anchor' => '"><script>']],
            ['type' => 'text', 'title' => 'Four', 'content' => 'x', 'style' => ['bg' => 'bogus', 'padding' => ['array'], 'text_tone' => 'evil']],
        ]]);
        $styles = collect($page->settings['sections'])->pluck('style');
        $this->assertSame('', $styles[0]['bg_image']);
        $this->assertSame('', $styles[1]['bg_color']);
        $this->assertSame('', $styles[2]['bg_video']);
        $this->assertSame('', $styles[2]['anchor']);
        $this->assertSame(['none', 'md', 'auto'], [$styles[3]['bg'], $styles[3]['padding'], $styles[3]['text_tone']]);
        $this->get('/')->assertOk()->assertDontSee('alert(1)', false)->assertDontSee('red;position:fixed', false)->assertDontSee('"><script>', false);
    }

    public function test_image_grid_and_video_blocks(): void
    {
        $this->admin();
        $this->publish('home', ['sections' => [
            ['type' => 'gallery', 'title' => 'Our moments', 'columns' => 4, 'ratio' => 'landscape', 'captions' => 'overlay', 'items' => [
                ['image_url' => 'https://cdn.example.com/1.jpg', 'caption' => 'Graduation', 'link' => '/events'],
                ['image_url' => 'https://cdn.example.com/2.jpg', 'caption' => ''],
                ['image_url' => 'javascript:alert(1)', 'caption' => 'Bad'],
            ]],
            ['type' => 'video', 'title' => 'Watch our story', 'video_url' => 'https://youtu.be/XyZ123abcde', 'width' => 'wide'],
            ['type' => 'video', 'title' => 'Uploaded clip', 'video_url' => '/cms-media/cms/media/2026/11/clip.mp4', 'poster_url' => '/cms-media/cms/media/2026/11/p.jpg', 'autoplay' => 1],
            ['type' => 'video', 'title' => 'Rejected video', 'video_url' => 'https://evil.example/embed.html'],
            ['type' => 'gallery', 'title' => 'Empty grid', 'items' => [['image_url' => '']]],
        ]]);
        $this->get('/')->assertOk()
            ->assertSee('--cms-gallery-cols: 4', false)
            ->assertSee('cms-gallery--landscape', false)
            ->assertSee('https://cdn.example.com/1.jpg', false)
            ->assertSee('href="/events"', false)
            ->assertSee('<span class="cms-gallery__overlay">Graduation</span>', false)
            ->assertDontSee('Bad')
            ->assertSee('https://www.youtube.com/embed/XyZ123abcde', false)
            ->assertSee('<video src="/cms-media/cms/media/2026/11/clip.mp4"', false)
            ->assertSee('poster="/cms-media/cms/media/2026/11/p.jpg"', false)
            ->assertDontSee('Rejected video')
            ->assertDontSee('Empty grid');
    }

    public function test_media_upload_is_admin_only_validated_and_served(): void
    {
        Storage::fake('public');
        $this->admin();
        $res = $this->postJson(route('admin.cms.media.store'), ['file' => UploadedFile::fake()->image('photo.png', 40, 40)])
            ->assertOk()->assertJsonStructure(['url', 'path', 'kind'])->assertJson(['kind' => 'image']);
        Storage::disk('public')->assertExists($res->json('path'));
        $this->assertStringStartsWith('cms/media/', $res->json('path'));

        $video = $this->postJson(route('admin.cms.media.store'), ['file' => UploadedFile::fake()->create('clip.mp4', 200, 'video/mp4')])
            ->assertOk()->assertJson(['kind' => 'video']);

        $this->postJson(route('admin.cms.media.store'), ['file' => UploadedFile::fake()->createWithContent('x.svg', '<svg onload="alert(1)"/>')])->assertStatus(422);
        $this->postJson(route('admin.cms.media.store'), ['file' => UploadedFile::fake()->createWithContent('shell.php', '<?php echo 1;')])->assertStatus(422);
        $this->postJson(route('admin.cms.media.store'), ['file' => UploadedFile::fake()->createWithContent('fake.png', 'not an image')])->assertStatus(422);

        auth()->logout();
        $served = $this->get($res->json('url'))->assertOk();
        $this->assertSame('image/png', $served->headers->get('Content-Type'));
        $this->assertSame('nosniff', $served->headers->get('X-Content-Type-Options'));
        $this->get(route('cms.media', ['path' => $video->json('path')]))->assertOk()->assertHeader('Content-Type', 'video/mp4');
        $this->get('/cms-media/cms/../.env')->assertNotFound();
        $this->get('/cms-media/cms/media/missing.png')->assertNotFound();
        $this->get('/cms-media/cms/media/file.php')->assertNotFound();

        $this->postJson(route('admin.cms.media.store'), ['file' => UploadedFile::fake()->image('x.png')])->assertUnauthorized();
        $this->actingAs(User::factory()->create(['user_type' => 'staff', 'status' => 'active']))
            ->postJson(route('admin.cms.media.store'), ['file' => UploadedFile::fake()->image('x.png')])->assertForbidden();
    }

    public function test_header_identity_logo_menu_and_announcement(): void
    {
        Storage::fake('public');
        $this->admin();
        $page = $this->publish('site-header', [
            'title' => 'Elevate Women', 'summary' => 'Tech for all',
            'image' => UploadedFile::fake()->image('logo.png', 200, 60),
            'links' => [
                ['label' => 'About', 'url' => '#about'],
                ['label' => 'Blog', 'url' => 'https://blog.example.com', 'new_tab' => 1],
            ],
            'header' => ['logo_height' => 60, 'show_name' => 1, 'show_tagline' => 0, 'sticky' => 0, 'show_partners' => 0, 'show_auth' => 0, 'background' => '#101828', 'text_color' => '#FFFFFF', 'cta_label' => 'Donate', 'cta_url' => 'https://give.example.com'],
            'announcement' => ['enabled' => 1, 'text' => 'Cohort 2027 applications open', 'link_label' => 'Apply', 'link_url' => '/register', 'background' => '#d4af37'],
        ]);
        $this->assertStringStartsWith('cms/', $page->image_path);
        auth()->logout();

        $this->get('/faqs')->assertOk()
            ->assertSee('Elevate Women')
            ->assertDontSee('Tech for all')
            ->assertSee(route('cms.media', ['path' => $page->image_path]), false)
            ->assertSee('height:60px', false)
            ->assertSee('href="#about"', false)
            ->assertSeeInOrder(['href="https://blog.example.com"', 'target="_blank" rel="noopener"'], false)
            ->assertSee('site-header--static', false)
            ->assertSee('--site-header-bg:#101828', false)
            ->assertSee('--site-header-fg:#ffffff', false)
            ->assertSee('Donate')
            ->assertSee('Cohort 2027 applications open')
            ->assertDontSee('data-partner-menu', false)
            ->assertDontSee('nav-signin', false);
    }

    public function test_header_without_new_settings_keeps_previous_defaults(): void
    {
        $this->admin();
        // Legacy text-box links (no header[] fields posted) keep auth buttons and sticky header.
        $this->publish('site-header', ['title' => 'Brand', 'summary' => 'Line', 'links_text' => 'FAQs | /faqs']);
        auth()->logout();
        $this->get('/faqs')->assertOk()->assertSee('nav-signin', false)->assertDontSee('site-header--static', false)->assertSee('Line');
    }

    public function test_footer_logo_contact_social_and_columns(): void
    {
        Storage::fake('public');
        $this->admin();
        $page = $this->publish('site-footer', [
            'title' => 'WITU', 'summary' => 'Women in Technology Uganda', 'body' => 'We empower women.',
            'image' => UploadedFile::fake()->image('white.png', 120, 40),
            'links' => [
                ['label' => 'Courses', 'url' => '/learning', 'column' => 1],
                ['label' => 'Privacy', 'url' => '/privacy-policy', 'column' => 3],
            ],
            'footer' => [
                'show_logo' => 1, 'logo_height' => 50, 'background' => '#1d2939', 'text_color' => '#eeeeee',
                'column_titles' => ['Learn', 'Unused', 'Legal'],
                'email' => 'info@example.org', 'phone' => '+256 700 000000', 'address' => 'Kampala, Uganda',
                'social' => ['linkedin' => 'https://linkedin.com/company/x', 'x' => 'https://x.com/y'],
            ],
        ]);
        auth()->logout();
        $this->get('/faqs')->assertOk()
            ->assertSee(route('cms.media', ['path' => $page->image_path]), false)
            ->assertSee('--site-footer-bg:#1d2939', false)
            ->assertSee('We empower women.')
            ->assertSee('mailto:info@example.org', false)
            ->assertSee('tel:+256700000000', false)
            ->assertSee('Kampala, Uganda')
            ->assertSee('https://linkedin.com/company/x', false)
            ->assertSee('<h2>Learn</h2><a href="/learning"', false)
            ->assertSee('<h2>Legal</h2><a href="/privacy-policy"', false)
            ->assertDontSee('<h2>Unused</h2>', false);

        $this->admin();
        $this->put(route('admin.cms.update', $page), ['title' => 'WITU', 'action' => 'publish', 'footer' => ['social' => ['facebook' => 'javascript:alert(1)']]])
            ->assertSessionHasErrors('footer.social.facebook');
        $this->put(route('admin.cms.update', $page), ['title' => 'WITU', 'action' => 'publish', 'links' => [['label' => 'Bad', 'url' => 'javascript:alert(1)']]])
            ->assertSessionHasErrors('links');
    }
}
