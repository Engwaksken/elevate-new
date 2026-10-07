<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RolePermissionAdministrationTest extends TestCase
{
    use RefreshDatabase;

    private function administrator(): User
    {
        $user = User::factory()->create(['user_type' => 'staff', 'status' => 'active']);
        $user->roles()->attach(Role::firstOrCreate(
            ['slug' => 'super-administrator'],
            ['name' => 'Super Administrator', 'is_system' => true]
        ));

        return $user;
    }

    public function test_it_roles_exist_and_admin_can_add_permissions_and_custom_roles(): void
    {
        $admin = $this->administrator();
        $itLead = Role::with('permissions')->where('slug', 'it-lead')->firstOrFail();
        $itAssistant = Role::with('permissions')->where('slug', 'it-assistant')->firstOrFail();

        $this->assertSame('IT Lead', $itLead->name);
        $this->assertSame('IT Assistant', $itAssistant->name);
        $this->assertTrue($itLead->permissions->contains('slug', 'it_support_tickets.manage'));
        $this->assertTrue($itAssistant->permissions->contains('slug', 'it_support_tickets.view'));

        $this->actingAs($admin)->get(route('admin.roles.index'))
            ->assertOk()
            ->assertSee('IT Lead')
            ->assertSee('IT Assistant')
            ->assertSee('Add Role')
            ->assertSee('Add Permission');

        $this->actingAs($admin)->post(route('admin.permissions.store'), [
            'name' => 'Review regional plans',
            'slug' => 'plans.review',
            'module' => 'plans',
            'description' => 'Review plans for assigned regions.',
        ])->assertRedirect(route('admin.roles.index'))->assertSessionHasNoErrors();

        $permission = Permission::where('slug', 'plans.review')->firstOrFail();
        $this->assertTrue($admin->roles()->where('roles.slug', 'super-administrator')
            ->firstOrFail()->permissions()->whereKey($permission->id)->exists());

        $this->actingAs($admin)->post(route('admin.roles.store'), [
            'name' => 'Regional Reviewer',
            'slug' => 'regional-reviewer',
            'description' => 'Reviews programme plans.',
            'permissions' => [$permission->id],
        ])->assertRedirect(route('admin.roles.index'))->assertSessionHasNoErrors();

        $role = Role::with('permissions')->where('slug', 'regional-reviewer')->firstOrFail();
        $this->assertFalse($role->is_system);
        $this->assertTrue($role->permissions->contains('slug', 'plans.review'));
    }
}
