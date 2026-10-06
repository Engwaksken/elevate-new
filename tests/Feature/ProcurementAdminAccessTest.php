<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Procurement Admin is for procurement officers, finance, management and
 * administrators; other staff raise requests from My Purchase Requests.
 */
class ProcurementAdminAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    private function staff(?string $role = null): User
    {
        $user = User::factory()->create(['user_type' => 'staff', 'status' => 'active']);
        if ($role) {
            $user->roles()->attach(Role::where('slug', $role)->firstOrFail());
        }

        return $user;
    }

    public function test_plain_staff_cannot_open_procurement_admin_or_see_its_link(): void
    {
        $staff = $this->staff();

        $this->actingAs($staff)->get(route('admin.procurement.requests.index'))->assertForbidden();
        $this->actingAs($staff)->get(route('staff.purchase-requests.index'))
            ->assertOk()
            ->assertDontSee(route('admin.procurement.requests.index'), false)
            ->assertSee(route('staff.purchase-requests.index'), false);
    }

    public function test_procurement_officer_finance_and_administrators_have_access(): void
    {
        foreach (['procurement-officer', 'finance', 'administrator'] as $role) {
            $user = $this->staff($role);
            $this->actingAs($user)->get(route('admin.procurement.requests.index'))->assertOk();
            $this->actingAs($user)->get(route('staff.tasks.index'))->assertSee(route('admin.procurement.requests.index'), false);
        }
    }

    public function test_role_grants_match_their_work(): void
    {
        $officer = $this->staff('procurement-officer');
        $finance = $this->staff('finance');

        foreach (['procurement.view', 'procurement.create', 'procurement.approve', 'procurement.receive'] as $permission) {
            $this->assertTrue($officer->hasPermission($permission), $permission);
        }
        $this->assertTrue($finance->hasPermission('procurement.approve'));
        $this->assertFalse($finance->hasPermission('procurement.create'));
        $this->assertFalse($finance->hasPermission('procurement.receive'));
    }
}
