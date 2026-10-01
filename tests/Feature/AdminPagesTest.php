<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\Course;
use App\Models\AuditLog;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use ParseError;
use Tests\TestCase;

class AdminPagesTest extends TestCase
{
    use RefreshDatabase;

    private function superAdmin(array $attributes = []): User
    {
        $user = User::factory()->create($attributes + ['user_type' => 'staff', 'status' => 'active']);
        $user->roles()->attach(Role::create(['name' => 'Super Administrator', 'slug' => 'super-administrator'])->id);

        return $user;
    }

    public function test_every_blade_view_compiles_to_valid_php(): void
    {
        $failures = [];

        foreach (File::allFiles(resource_path('views')) as $file) {
            if (! str_ends_with($file->getFilename(), '.blade.php')) {
                continue;
            }

            try {
                token_get_all(Blade::compileString($file->getContents()), TOKEN_PARSE);
            } catch (ParseError $e) {
                $failures[] = $file->getRelativePathname().': '.$e->getMessage();
            }
        }

        $this->assertSame([], $failures, "Views that compile to invalid PHP:\n".implode("\n", $failures));
    }

    public function test_workspace_hubs_render_with_four_stats(): void
    {
        $admin = $this->superAdmin();

        foreach (['learning' => 'Courses', 'planning-meal' => 'Workplans', 'mentorship' => 'Mentors', 'jobs' => 'Applications', 'reports' => 'Import Centre'] as $page => $expected) {
            $response = $this->actingAs($admin)->get('/admin/workspace/'.$page)->assertOk()->assertSee($expected);
            $this->assertSame(4, substr_count($response->getContent(), 'class="admin-stat"'), $page.' should show 4 stat cards');
        }
    }

    public function test_tracking_reports_and_performance_index_render(): void
    {
        $admin = $this->superAdmin();

        $this->actingAs($admin)->get('/admin/reports/mentorship-tracking')->assertOk()->assertSee('Mentorship Tracking');
        $this->actingAs($admin)->get('/admin/reports/jobs-tracking')->assertOk()->assertSee('No tracking events recorded yet.');

        $performance = $this->actingAs($admin)->get('/staff/performance')->assertOk()->assertSee('Awaiting My Action');
        $this->assertSame(4, substr_count($performance->getContent(), 'class="admin-stat"'));
    }

    public function test_csv_template_downloads_as_a_proper_csv_file(): void
    {
        $admin = $this->superAdmin();
        $response = $this->actingAs($admin)->get('/admin/import-centre/template/employees');

        $response->assertOk();
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));
        $this->assertSame('attachment; filename=employees_template.csv', $response->headers->get('Content-Disposition'));

        $body = $response->streamedContent();
        $this->assertStringStartsWith("\xEF\xBB\xBF", $body);
        $this->assertStringContainsString('name,email,employee_number', $body);

        $this->actingAs($admin)->get('/admin/import-centre/template/unknown')->assertNotFound();
    }

    public function test_import_upload_previews_from_the_private_disk(): void
    {
        Storage::fake('local');
        $admin = $this->superAdmin();

        $file = UploadedFile::fake()->createWithContent('staff.csv', "name,email\nAmina,amina@example.com\n");

        $response = $this->actingAs($admin)->post('/admin/import-centre/upload', ['module' => 'employees', 'file' => $file]);
        $response->assertRedirect();

        $this->actingAs($admin)->get($response->headers->get('Location'))
            ->assertOk()
            ->assertSee('amina@example.com')
            ->assertSee('Import Preview');

        $this->actingAs($admin)->get('/admin/import-centre')->assertOk()->assertSee('staff.csv');
    }

    public function test_audit_log_shows_readable_changes(): void
    {
        $admin = $this->superAdmin();

        AuditLog::create([
            'user_id' => $admin->id,
            'module' => 'Human Resources',
            'action' => 'updated',
            'old_values' => ['status' => 'self_assessment', 'manager_user_id' => 3, 'updated_at' => '2026-10-01 10:00:00'],
            'new_values' => ['status' => 'manager_review', 'manager_user_id' => 4, 'updated_at' => '2026-10-02 10:00:00'],
            'occurred_at' => now(),
        ]);

        $this->actingAs($admin)->get('/admin/audit-logs')
            ->assertOk()
            ->assertSee('Self Assessment')
            ->assertSee('Manager Review')
            ->assertSee('Manager User')
            ->assertDontSee('&quot;status&quot;', false)
            ->assertDontSee('"status":', false);
    }

    public function test_super_admin_passes_staff_and_role_restricted_areas(): void
    {
        // An administrator whose account type was never set to staff.
        $admin = $this->superAdmin(['user_type' => 'participant']);

        $this->actingAs($admin)->get('/admin/workspace/learning')->assertOk();
        $this->actingAs($admin)->get('/admin/hr/kpi-templates')->assertOk();
    }

    public function test_audit_log_lists_permission_changes_by_name(): void
    {
        $admin = $this->superAdmin();
        $role = Role::create(['name' => 'Programme Officer', 'slug' => 'programme-officer']);
        $view = Permission::create(['name' => 'View Reports', 'slug' => 'reports.view', 'module' => 'reports']);
        $export = Permission::create(['name' => 'Export Reports', 'slug' => 'reports.export', 'module' => 'reports']);
        $users = Permission::create(['name' => 'Manage Users', 'slug' => 'users.manage', 'module' => 'users']);
        $role->permissions()->sync([$view->id, $users->id]);

        $this->actingAs($admin)->put("/admin/roles/{$role->id}", [
            'name' => 'Programme Officer',
            'permissions' => [$view->id, $export->id],
        ])->assertRedirect();

        $rows = AuditLog::where('action', 'permissions_updated')->latest('id')->firstOrFail()->changeRows();

        $this->assertSame(['Permissions'], array_column($rows, 'field'));
        $this->assertSame(['Export Reports'], array_column($rows[0]['added'], 'label'));
        $this->assertSame(['Manage Users'], array_column($rows[0]['removed'], 'label'));
        $this->assertSame(['View Reports'], array_column($rows[0]['unchanged'], 'label'));

        $this->actingAs($admin)->get('/admin/audit-logs')
            ->assertOk()
            ->assertSee('1 granted')
            ->assertSee('1 revoked')
            ->assertSee('Export Reports')
            ->assertDontSee('&quot;pivot&quot;', false)
            ->assertDontSee('Pivot:', false);
    }

    public function test_notification_modal_marks_read_and_mark_all_works(): void
    {
        $user = User::factory()->create(['user_type' => 'participant', 'status' => 'active']);
        $first = UserNotification::create(['user_id' => $user->id, 'type' => 'general', 'title' => 'Welcome aboard', 'message' => 'Full welcome message body.']);
        $second = UserNotification::create(['user_id' => $user->id, 'type' => 'general', 'title' => 'Second']);

        $this->actingAs($user)->get('/notifications')
            ->assertOk()
            ->assertSee('id="notification-'.$first->id.'"', false)
            ->assertSee('Full welcome message body.');

        $this->actingAs($user)->patchJson("/notifications/{$first->id}/read")->assertNoContent();
        $this->assertNotNull($first->fresh()->read_at);

        $this->actingAs($user)->patch('/notifications/read-all')->assertRedirect();
        $this->assertNotNull($second->fresh()->read_at);
    }

    public function test_submission_review_opens_in_a_modal_and_reopens_on_error(): void
    {
        $admin = $this->superAdmin();
        $course = Course::create(['title' => 'Digital Marketing', 'status' => 'published']);
        $assessment = Assessment::create(['course_id' => $course->id, 'title' => 'Essay', 'type' => 'assignment', 'total_marks' => 50, 'is_published' => true]);
        $participant = User::factory()->create(['user_type' => 'participant', 'status' => 'active']);
        $attempt = AssessmentAttempt::create([
            'assessment_id' => $assessment->id, 'user_id' => $participant->id, 'attempt_number' => 1,
            'submission_text' => 'My essay answer', 'status' => 'submitted', 'submitted_at' => now(),
        ]);

        $url = "/instructor/courses/{$course->id}/manage?tab=submissions";

        $this->actingAs($admin)->get($url)
            ->assertOk()
            ->assertSee('data-modal-open="review-attempt-'.$attempt->id.'"', false)
            ->assertSee('id="review-attempt-'.$attempt->id.'"', false)
            ->assertSee('My essay answer')
            ->assertSee('Out of 50')
            ->assertDontSee('data-modal-autoopen', false);

        $this->actingAs($admin)->from($url)
            ->put("/instructor/courses/{$course->id}/submissions/{$attempt->id}/review", [
                'review_attempt_id' => $attempt->id, 'percentage' => 150, 'status' => 'graded',
            ])->assertRedirect($url);

        $this->actingAs($admin)->get($url)->assertSee('data-modal-autoopen', false);
    }
}
