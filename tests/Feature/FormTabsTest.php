<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Employer;
use App\Models\Event;
use App\Models\Job;
use App\Models\LibraryResource;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;
use Tests\TestCase;

class FormTabsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['user_type' => 'staff', 'status' => 'active']);
        $this->admin->roles()->attach(Role::create(['name' => 'Super Admin', 'slug' => 'super-admin'])->id);
    }

    private function withErrors(array $messages): static
    {
        return $this->withSession([
            'errors' => (new ViewErrorBag)->put('default', new MessageBag($messages)),
        ]);
    }

    public function test_users_create_and_edit_forms_are_tabbed(): void
    {
        $other = User::factory()->create(['user_type' => 'staff', 'status' => 'active']);

        $this->actingAs($this->admin)
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee('role="tablist"', false)
            ->assertSee('id="user-create-account-tab"', false)
            ->assertSee('aria-controls="user-create-security"', false)
            ->assertSee('id="user-edit-'.$other->id.'-access"', false)
            ->assertSee('Account')
            ->assertSee('Roles &amp; Access', false)
            ->assertSee('Security');
    }

    public function test_tab_with_first_validation_error_is_opened(): void
    {
        $html = $this->actingAs($this->admin)
            ->withErrors(['password' => 'The password field is required.'])
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee('data-error-tab="security"', false)
            ->getContent();

        // Security panel is shown and the Account panel is hidden on first render.
        $this->assertMatchesRegularExpression('/id="user-create-security"[^>]*>/', $html);
        preg_match('/<section[^>]*id="user-create-security"[^>]*>/', $html, $security);
        preg_match('/<section[^>]*id="user-create-account"[^>]*>/', $html, $account);
        $this->assertStringNotContainsString(' hidden', $security[0]);
        $this->assertStringContainsString(' hidden', $account[0]);
        $this->assertMatchesRegularExpression('/id="user-create-security-tab"[^>]*aria-selected="true"/s', $html);
    }

    public function test_without_errors_the_first_tab_is_active(): void
    {
        $html = $this->actingAs($this->admin)->get(route('admin.users.index'))->assertOk()->getContent();

        preg_match('/<section[^>]*id="user-create-account"[^>]*>/', $html, $account);
        $this->assertStringNotContainsString(' hidden', $account[0]);
        $this->assertStringNotContainsString('data-error-tab=', $html);
    }

    public function test_events_form_is_tabbed(): void
    {
        Event::create([
            'title' => 'Career Fair',
            'event_type' => 'training',
            'delivery_mode' => 'physical',
            'starts_at' => now()->addWeek(),
            'status' => 'draft',
        ]);

        $this->actingAs($this->admin)
            ->get(route('admin.events.index'))
            ->assertOk()
            ->assertSee('id="createEvent-tabs"', false)
            ->assertSee('Details')
            ->assertSee('Schedule &amp; Venue', false)
            ->assertSee('Audience &amp; Publishing', false);
    }

    public function test_jobs_form_is_tabbed(): void
    {
        $employer = Employer::create(['company_name' => 'Acme Ltd', 'owner_user_id' => $this->admin->id, 'status' => 'approved']);
        Job::create([
            'employer_id' => $employer->id,
            'title' => 'Junior Developer',
            'slug' => 'junior-developer',
            'description' => 'Build things.',
            'country' => 'Uganda',
            'employment_type' => 'full_time',
            'work_arrangement' => 'onsite',
            'salary_currency' => 'UGX',
            'positions' => 1,
            'status' => 'draft',
        ]);

        $this->actingAs($this->admin)
            ->get(route('admin.jobs.index'))
            ->assertOk()
            ->assertSee('id="createJob-tabs"', false)
            ->assertSee('Basics')
            ->assertSee('Location &amp; Terms', false)
            ->assertSee('Description');
    }

    public function test_library_form_is_tabbed(): void
    {
        $resource = LibraryResource::create([
            'title' => 'Career Guide',
            'slug' => 'career-guide',
            'language' => 'English',
            'access_level' => 'authenticated',
            'is_active' => true,
            'external_url' => 'https://example.com/guide',
            'uploaded_by' => $this->admin->id,
        ]);

        $this->actingAs($this->admin)
            ->get(route('admin.library.index'))
            ->assertOk()
            ->assertSee('id="library-create"', false)
            ->assertSee('id="library-edit-'.$resource->id.'"', false)
            ->assertSee('Files &amp; Links', false)
            ->assertSee('Access');
    }

    public function test_courses_form_is_tabbed(): void
    {
        $course = Course::create([
            'title' => 'Intro to Web',
            'slug' => 'intro-to-web',
            'delivery_mode' => 'online',
            'pass_mark' => 50,
            'status' => 'draft',
        ]);

        $this->actingAs($this->admin)
            ->get(route('admin.elearning.courses.index'))
            ->assertOk()
            ->assertSee('id="course-create"', false)
            ->assertSee('id="course-edit-'.$course->id.'"', false)
            ->assertSee('Overview')
            ->assertSee('Organisation')
            ->assertSee('Schedule &amp; Publishing', false);
    }

    public function test_employees_form_is_tabbed(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.hr.employees.index'))
            ->assertOk()
            ->assertSee('id="employee-create"', false)
            ->assertSee('Employee')
            ->assertSee('Placement')
            ->assertSee('Dates');
    }

    public function test_profile_form_is_tabbed(): void
    {
        $this->actingAs($this->admin)
            ->get(route('profile.edit'))
            ->assertOk()
            ->assertSee('id="profile-account-tab"', false)
            ->assertSee('aria-controls="profile-security"', false)
            ->assertSee('Personal')
            ->assertSee('Location')
            ->assertSee('Career')
            ->assertSee('Preferences')
            ->assertDontSee('data-eh-tabs', false);
    }

    public function test_profile_error_opens_matching_tab(): void
    {
        $this->actingAs($this->admin)
            ->withErrors(['district' => 'Too long.'])
            ->get(route('profile.edit'))
            ->assertOk()
            ->assertSee('data-error-tab="location"', false);
    }

    public function test_tab_script_is_included_in_both_layouts(): void
    {
        $this->actingAs($this->admin)->get(route('admin.users.index'))->assertSee('window.EhFormTabs', false);
        $this->actingAs($this->admin)->get(route('profile.edit'))->assertSee('window.EhFormTabs', false);
    }
}
