<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\LeaveType;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaffDashboardTweaksTest extends TestCase
{
    use RefreshDatabase;

    private function staff(): User
    {
        return User::factory()->create(['user_type' => 'staff', 'status' => 'active']);
    }

    public function test_settings_link_is_only_in_the_profile_menu_for_people_who_can_manage_settings(): void
    {
        $settingsUrl = route('admin.settings.index');

        $this->actingAs($this->staff())->get(route('staff.tasks.index'))
            ->assertOk()->assertDontSee($settingsUrl, false);

        $admin = $this->staff();
        $admin->roles()->attach(Role::firstOrCreate(['slug' => 'super-administrator'], ['name' => 'Super Administrator']));
        $this->actingAs($admin)->get(route('staff.tasks.index'))
            ->assertOk()->assertSee($settingsUrl, false);
    }

    public function test_default_leave_types_exist_so_staff_can_request_leave(): void
    {
        $this->assertTrue(LeaveType::where('code', 'ANNUAL')->where('is_active', true)->exists());
        $this->assertNull(LeaveType::where('code', 'UNPAID')->value('default_days'));

        $me = $this->staff();
        Employee::create(['user_id' => $me->id, 'employee_number' => 'EMP-9']);

        $this->actingAs($me)->get(route('hr.leave.index'))
            ->assertOk()
            ->assertSee('data-modal-open="leave-new"', false)
            ->assertSee('Annual Leave (21 days left)')
            ->assertSee('Unpaid Leave (no set limit)');
    }
}
