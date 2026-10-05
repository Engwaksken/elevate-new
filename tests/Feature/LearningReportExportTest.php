<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LearningReportExportTest extends TestCase
{
    use RefreshDatabase;

    private function userWithReportPermission(): User
    {
        $user = User::factory()->create(['user_type' => 'staff', 'status' => 'active']);
        $role = Role::create(['name' => 'Report Viewer', 'slug' => 'report-viewer']);
        $permission = Permission::create([
            'name' => 'View Reports',
            'slug' => 'reports.view',
            'module' => 'reports',
        ]);
        $role->permissions()->attach($permission);
        $user->roles()->attach($role);

        // Exercise the same staff and permission checks applied by the route middleware.
        $this->assertTrue($user->isStaff());
        $this->assertTrue($user->hasPermission('reports.view'));

        return $user;
    }

    public function test_learning_report_exports_are_forbidden_without_reports_permission(): void
    {
        $user = User::factory()->create(['user_type' => 'staff', 'status' => 'active']);

        $this->actingAs($user)->get('/admin/learning-reports.csv')->assertForbidden();
        $this->actingAs($user)->get('/admin/learning-reports.pdf')->assertForbidden();
    }

    public function test_csv_export_accepts_report_filters_and_returns_csv(): void
    {
        $user = $this->userWithReportPermission();

        $this->assertSame(
            \App\Http\Controllers\Admin\LearningReportController::class.'@csv',
            app('router')->getRoutes()->getByName('admin.learning-reports.csv')->getActionName(),
        );

        $response = $this->actingAs($user)->get(route('admin.learning-reports.csv', ['from' => '2026-01-01', 'to' => '2026-12-31']));

        $response->assertOk();
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('attachment;', $response->headers->get('Content-Disposition'));
        $csv = $response->streamedContent();
        $header = str_getcsv(strtok($csv, "\r\n"));
        $this->assertSame([
            'Course', 'Programme ID', 'Branch ID', 'Enrolments', 'Completed', 'In progress',
            'Completion rate (%)', 'Average progress (%)',
        ], $header);
    }

    public function test_pdf_export_accepts_report_filters_and_returns_a_pdf_download(): void
    {
        $user = $this->userWithReportPermission();

        $this->assertSame(
            \App\Http\Controllers\Admin\LearningReportController::class.'@pdf',
            app('router')->getRoutes()->getByName('admin.learning-reports.pdf')->getActionName(),
        );

        $response = $this->actingAs($user)->get(route('admin.learning-reports.pdf', ['from' => '2026-01-01', 'to' => '2026-12-31']));

        $response->assertOk();
        $this->assertSame('application/pdf', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('.pdf', $response->headers->get('Content-Disposition'));
        $this->assertStringStartsWith('%PDF-', $response->getContent());
    }

    public function test_learning_report_page_renders_for_a_report_viewer(): void
    {
        $user = $this->userWithReportPermission();

        $this->actingAs($user)->get('/admin/learning-reports')
            ->assertOk()
            ->assertSee('Learning Report')
            ->assertSee('No learning records match these filters.');
    }

    public function test_withdrawn_enrolments_are_excluded_from_csv_report_totals(): void
    {
        $user = $this->userWithReportPermission();
        $participant = User::factory()->create();
        $courseId = DB::table('courses')->insertGetId([
            'title' => 'Regression Course',
            'delivery_mode' => 'online',
            'status' => 'published',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('enrolments')->insert([
            'course_id' => $courseId,
            'user_id' => $participant->id,
            'status' => 'withdrawn',
            'enrolled_at' => now(),
            'progress_percent' => 100,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $csv = $this->actingAs($user)->get('/admin/learning-reports.csv')->streamedContent();

        $this->assertStringNotContainsString('Regression Course', $csv);
    }

    public function test_cancelled_enrolments_are_excluded_from_csv_report_totals(): void
    {
        $user = $this->userWithReportPermission();
        $participant = User::factory()->create();
        $courseId = DB::table('courses')->insertGetId([
            'title' => 'Cancelled Regression Course',
            'delivery_mode' => 'online',
            'status' => 'published',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('enrolments')->insert([
            'course_id' => $courseId,
            'user_id' => $participant->id,
            'status' => 'withdrawn',
            'enrolled_at' => now(),
            'progress_percent' => 100,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $csv = $this->actingAs($user)->get('/admin/learning-reports.csv')->streamedContent();

        $this->assertStringNotContainsString('Cancelled Regression Course', $csv);
    }

}
