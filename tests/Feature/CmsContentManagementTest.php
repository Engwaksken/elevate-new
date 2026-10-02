<?php

namespace Tests\Feature;

use App\Models\CmsPage;
use App\Models\Course;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CmsContentManagementTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $user = User::factory()->create(['user_type' => 'staff', 'status' => 'active']);
        $user->roles()->attach(Role::create(['name' => 'Administrator', 'slug' => 'administrator']));
        $this->actingAs($user);
        return $user;
    }

    private function save(string $slug, array $changes = []): CmsPage
    {
        $page = CmsPage::where('slug', $slug)->firstOrFail();
        $this->put(route('admin.cms.update', $page), $changes + ['title' => $page->title, 'body' => 'Published content', 'action' => 'publish'])
            ->assertSessionHasNoErrors();
        return $page->fresh();
    }

    public function test_drafts_do_not_change_published_content_and_previews_are_private(): void
    {
        $this->admin();
        $this->get(route('admin.cms.index'))->assertOk()->assertSee('Frontend CMS');
        foreach (CmsPage::all() as $page) {
            $this->get(route('admin.cms.edit', $page))->assertOk();
        }
        $page = $this->save('privacy-policy', ['body' => 'Live privacy statement']);
        $this->save('privacy-policy', ['body' => 'Secret draft policy', 'action' => 'save']);
        $this->get('/privacy-policy')->assertOk()->assertSee('Live privacy statement')->assertDontSee('Secret draft policy');
        $this->get(route('admin.cms.preview', $page))->assertOk()->assertSee('Secret draft policy');
        auth()->logout();
        $this->get(route('admin.cms.preview', $page))->assertRedirect(route('login'));
        $this->get('/privacy-policy')->assertOk()->assertSee('Live privacy statement');
    }

    public function test_header_footer_catalogues_and_signup_labels_use_published_content(): void
    {
        $this->admin();
        $this->save('site-header', ['title' => 'New Brand', 'summary' => 'Our tagline', 'links_text' => 'Answers | /faqs']);
        $this->save('site-footer', ['title' => 'Contact Us', 'summary' => 'Our Organisation', 'links_text' => 'Mentors | /mentors/signup']);
        $this->save('learning', ['title' => 'Training Catalogue', 'body' => 'Choose your learning path']);
        Course::create(['title' => 'Live Course', 'status' => 'published']);
        $this->get('/learning')->assertOk()->assertSee('Training Catalogue')->assertSee('Live Course')->assertSee('New Brand')->assertSee('Answers')->assertSee('Our Organisation');
        foreach (['jobs' => '/jobs', 'library' => '/library', 'events' => '/events'] as $slug => $path) {
            $this->save($slug, ['title' => 'Managed '.$slug, 'body' => 'New '.$slug.' introduction']);
            $this->get($path)->assertOk()->assertSee('Managed '.$slug)->assertSee('New '.$slug.' introduction');
        }
        $this->save('mentor-signup', ['title' => 'Join Our Mentors', 'field_labels_text' => 'organisation | Place of work', 'button_label' => 'Apply to mentor', 'signup_open' => 1]);
        auth()->logout();
        $this->get('/mentors/signup')->assertOk()->assertSee('Join Our Mentors')->assertSee('Place of work')->assertSee('Apply to mentor');
    }

    public function test_custom_pages_can_be_created_published_unpublished_and_deleted(): void
    {
        $this->admin();
        $this->post(route('admin.cms.store'), ['slug' => 'about-us', 'title' => 'About Us'])->assertSessionHasNoErrors();
        $this->get('/pages/about-us')->assertNotFound();
        $page = $this->save('about-us', ['body' => '# Our story']);
        $this->get('/pages/about-us')->assertOk()->assertSee('<h1>Our story</h1>', false);
        $this->save('about-us', ['action' => 'unpublish']);
        $this->get('/pages/about-us')->assertNotFound();
        $this->delete(route('admin.cms.destroy', $page))->assertSessionHas('success');
        $this->assertDatabaseMissing('cms_pages', ['slug' => 'about-us']);
        $this->delete(route('admin.cms.destroy', CmsPage::where('slug', 'home')->first()))->assertStatus(422);
    }

    public function test_markdown_strips_raw_html_and_unsafe_navigation_is_rejected(): void
    {
        $this->admin();
        $this->save('faqs', ['body' => "## FAQ\n<img src=x onerror=alert(987)>\n[Bad](javascript:alert(987))"]);
        $this->get('/faqs')->assertOk()->assertSee('FAQ')->assertDontSee('<img src=x onerror=alert(987)>', false)->assertDontSee('href="javascript:', false);
        $page = CmsPage::where('slug', 'site-header')->first();
        foreach (['Bad | javascript:alert(1)', 'Bad | //evil.example.test'] as $links) {
            $this->put(route('admin.cms.update', $page), ['title' => 'Brand', 'action' => 'publish', 'links_text' => $links])->assertSessionHasErrors('links_text');
        }
    }

    public function test_cms_can_pause_signup_without_allowing_submission(): void
    {
        $this->admin();
        $this->save('employer-signup', ['signup_open' => 0]);
        auth()->logout();
        $this->get('/employers/signup')->assertOk()->assertSee('Registrations are currently closed.')->assertDontSee('name="password"', false);
        $this->post('/employers/signup', ['name' => 'Owner'])->assertForbidden();
    }

    public function test_cms_image_uploads_are_used_and_non_admins_cannot_edit_content(): void
    {
        Storage::fake('public');
        $this->admin();
        $page = $this->save('home', ['image' => UploadedFile::fake()->image('hero.png')]);
        Storage::disk('public')->assertExists($page->image_path);
        $this->get('/')->assertOk()->assertSee($page->image_path);
        $this->actingAs(User::factory()->create(['user_type' => 'staff', 'status' => 'active']))->get(route('admin.cms.index'))->assertForbidden();
        $this->put(route('admin.cms.update', $page), ['title' => 'Bad', 'body' => 'Hijacked', 'action' => 'publish'])->assertForbidden();
        $this->get('/')->assertDontSee('Hijacked');
    }
}
