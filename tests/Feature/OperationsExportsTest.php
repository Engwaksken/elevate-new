<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Supplier;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OperationsExportsTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $user = User::factory()->create(['user_type' => 'staff', 'status' => 'active']);
        $user->roles()->attach(Role::firstOrCreate(['slug' => 'super-administrator'], ['name' => 'Super Administrator']));

        return $user;
    }

    private function staffWith(string ...$permissions): User
    {
        $user = User::factory()->create(['user_type' => 'staff', 'status' => 'active']);
        $role = Role::firstOrCreate(['slug' => 'ops-export-'.implode('-', $permissions) ?: 'none'], ['name' => 'Ops Export Role']);
        $role->permissions()->sync(collect($permissions)->map(fn ($slug) => Permission::firstOrCreate(
            ['slug' => $slug],
            ['name' => $slug, 'module' => explode('.', $slug)[0]]
        )->id)->all());
        $user->roles()->attach($role);

        return $user;
    }

    public function test_suppliers_csv_respects_filters_for_permitted_user(): void
    {
        Supplier::create(['name' => 'Approved Stationers', 'status' => 'approved']);
        Supplier::create(['name' => 'Pending Printers', 'status' => 'pending']);

        $response = $this->actingAs($this->staffWith('procurement.view'))
            ->get(route('admin.procurement.suppliers.index', ['status' => 'approved', 'export' => 'csv']));

        $response->assertOk();
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('attachment; filename=suppliers-', $response->headers->get('Content-Disposition'));
        $csv = $response->streamedContent();
        $this->assertStringContainsString('Supplier,Category', $csv);
        $this->assertStringContainsString('Approved Stationers', $csv);
        $this->assertStringNotContainsString('Pending Printers', $csv);
    }

    public function test_suppliers_export_is_forbidden_without_permission(): void
    {
        $this->actingAs($this->staffWith('assets.view'))
            ->get(route('admin.procurement.suppliers.index', ['export' => 'csv']))
            ->assertForbidden();
    }

    public function test_my_leave_export_only_contains_own_requests(): void
    {
        $type = LeaveType::firstOrCreate(['name' => 'Annual Leave'], ['code' => 'AL', 'default_days' => 21, 'is_active' => true]);

        $me = User::factory()->create(['user_type' => 'staff', 'status' => 'active']);
        $mine = Employee::create(['user_id' => $me->id, 'employee_number' => 'EMP-ME']);
        $other = Employee::create([
            'user_id' => User::factory()->create(['user_type' => 'staff', 'status' => 'active'])->id,
            'employee_number' => 'EMP-OTHER',
        ]);

        LeaveRequest::create(['employee_id' => $mine->id, 'leave_type_id' => $type->id, 'start_date' => '2026-11-02', 'end_date' => '2026-11-03', 'days_requested' => 2, 'reason' => 'Family visit', 'status' => 'pending']);
        LeaveRequest::create(['employee_id' => $other->id, 'leave_type_id' => $type->id, 'start_date' => '2026-11-05', 'end_date' => '2026-11-06', 'days_requested' => 2, 'reason' => 'Someone elses trip', 'status' => 'pending']);

        $response = $this->actingAs($me)->get(route('hr.leave.index', ['export' => 'csv']));

        $response->assertOk();
        $csv = $response->streamedContent();
        $this->assertStringContainsString('Family visit', $csv);
        $this->assertStringNotContainsString('Someone elses trip', $csv);
    }

    public function test_my_tasks_export_is_scoped_to_the_signed_in_user(): void
    {
        $me = User::factory()->create(['user_type' => 'staff', 'status' => 'active']);
        $other = User::factory()->create(['user_type' => 'staff', 'status' => 'active']);

        Task::create(['title' => 'Write my report', 'assigned_to' => $me->id, 'created_by' => $me->id, 'due_date' => today(), 'status' => 'not_started', 'priority' => 'medium']);
        Task::create(['title' => 'Colleague private task', 'assigned_to' => $other->id, 'created_by' => $other->id, 'due_date' => today(), 'status' => 'not_started', 'priority' => 'medium']);

        $response = $this->actingAs($me)->get(route('staff.tasks.index', ['view' => 'today', 'export' => 'csv']));

        $response->assertOk();
        $csv = $response->streamedContent();
        $this->assertStringContainsString('Write my report', $csv);
        $this->assertStringNotContainsString('Colleague private task', $csv);
    }

    public function test_admin_tasks_export_pdf(): void
    {
        $owner = User::factory()->create(['user_type' => 'staff', 'status' => 'active']);
        Task::create(['title' => 'Quarterly review', 'assigned_to' => $owner->id, 'created_by' => $owner->id, 'status' => 'pending', 'priority' => 'high']);

        $response = $this->actingAs($this->admin())->get(route('admin.tasks.index', ['export' => 'pdf']));

        $response->assertOk();
        $this->assertSame('application/pdf', $response->headers->get('Content-Type'));
    }

    public function test_hr_list_pages_render_export_buttons(): void
    {
        $admin = $this->admin();

        foreach (['admin.hr.employees.index', 'admin.hr.leave.index', 'admin.procurement.purchase-orders.index', 'admin.assets.index', 'admin.indicators.index'] as $route) {
            $this->actingAs($admin)->get(route($route))->assertOk()->assertSee('export=csv', false);
        }
    }
}
