<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Survey;
use App\Models\User;
use App\Services\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCoreExportsTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $user = User::factory()->create(['user_type' => 'staff', 'status' => 'active']);
        $user->roles()->attach(Role::firstOrCreate(['slug' => 'super-administrator'], ['name' => 'Super Administrator']));

        return $user;
    }

    private function staffWith(array $permissions): User
    {
        $user = User::factory()->create(['user_type' => 'staff', 'status' => 'active']);
        $role = Role::firstOrCreate(['slug' => 'export-tester'], ['name' => 'Export Tester']);
        $role->permissions()->sync(collect($permissions)->map(fn ($slug) => Permission::firstOrCreate(
            ['slug' => $slug],
            ['name' => $slug, 'module' => explode('.', $slug)[0]]
        )->id)->all());
        $user->roles()->attach($role);

        return $user;
    }

    public function test_participants_csv_only_contains_participants_matching_the_filter(): void
    {
        User::factory()->create(['name' => 'Ada Active', 'user_type' => 'participant', 'status' => 'active']);
        User::factory()->create(['name' => 'Ian Inactive', 'user_type' => 'participant', 'status' => 'inactive']);
        User::factory()->create(['name' => 'Sam Staff', 'user_type' => 'staff', 'status' => 'active']);

        $response = $this->actingAs($this->staffWith(['users.view']))
            ->get(route('admin.participants.index', ['status' => 'active', 'export' => 'csv']));

        $response->assertOk();
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('participants-', $response->headers->get('Content-Disposition'));

        $csv = $response->streamedContent();
        $this->assertStringContainsString('Ada Active', $csv);
        $this->assertStringNotContainsString('Ian Inactive', $csv);
        $this->assertStringNotContainsString('Sam Staff', $csv);
    }

    public function test_participants_page_shows_export_buttons(): void
    {
        $this->actingAs($this->admin())
            ->get(route('admin.participants.index', ['status' => 'active']))
            ->assertOk()
            ->assertSee('export=csv', false)
            ->assertSee('export=pdf', false);
    }

    public function test_permission_gated_export_is_forbidden_without_permission(): void
    {
        $this->actingAs($this->staffWith(['users.view']))
            ->get(route('admin.audit-logs.index', ['export' => 'csv']))
            ->assertForbidden();

        $this->actingAs($this->staffWith(['users.view']))
            ->get(route('admin.settings.index', ['export' => 'pdf']))
            ->assertForbidden();
    }

    public function test_settings_export_never_reveals_encrypted_values(): void
    {
        app(SettingsService::class)->set('integrations.secret_key', 'top-secret-value', 'string', 'integrations', false, true);
        app(SettingsService::class)->set('general.motto', 'Rise together', 'string', 'general');

        $csv = $this->actingAs($this->admin())
            ->get(route('admin.settings.index', ['export' => 'csv']))
            ->assertOk()
            ->streamedContent();

        $this->assertStringContainsString('Rise together', $csv);
        $this->assertStringContainsString('integrations.secret_key', $csv);
        $this->assertStringNotContainsString('top-secret-value', $csv);
    }

    public function test_events_index_exports_pdf(): void
    {
        Event::create([
            'title' => 'Leadership Workshop',
            'event_type' => 'workshop',
            'starts_at' => now()->addDay(),
            'delivery_mode' => 'physical',
        ]);

        $response = $this->actingAs($this->admin())
            ->get(route('admin.events.index', ['export' => 'pdf']));

        $response->assertOk();
        $this->assertSame('application/pdf', $response->headers->get('Content-Type'));
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    public function test_survey_responses_export_requires_the_responses_export_permission(): void
    {
        $survey = Survey::create([
            'title' => 'Exit survey',
            'slug' => 'exit-survey',
            'access_type' => 'authenticated',
            'status' => 'published',
        ]);

        $this->actingAs($this->staffWith(['surveys.view']))
            ->get(route('admin.surveys.responses', ['survey' => $survey, 'export' => 'csv']))
            ->assertForbidden();

        $response = $this->actingAs($this->admin())
            ->get(route('admin.surveys.responses', ['survey' => $survey, 'export' => 'csv']));
        $response->assertOk();
        $this->assertStringContainsString('Respondent', $response->streamedContent());
    }

    public function test_roles_csv_lists_roles(): void
    {
        Role::firstOrCreate(['slug' => 'field-officer'], ['name' => 'Field Officer']);

        $csv = $this->actingAs($this->admin())
            ->get(route('admin.roles.index', ['search' => 'Field', 'export' => 'csv']))
            ->assertOk()
            ->streamedContent();

        $this->assertStringContainsString('Field Officer', $csv);
        $this->assertStringNotContainsString('Super Administrator', $csv);
    }
}
