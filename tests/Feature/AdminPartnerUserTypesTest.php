<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use App\Support\RoleShell;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPartnerUserTypesTest extends TestCase
{
    use RefreshDatabase;

    private function administrator(): User
    {
        $user = User::factory()->create(['user_type' => 'staff', 'status' => 'active']);
        $user->roles()->attach(Role::firstOrCreate(
            ['slug' => 'super-administrator'],
            ['name' => 'Super Administrator']
        ));

        return $user;
    }

    public function test_admin_can_create_mentor_and_employer_user_types(): void
    {
        $admin = $this->administrator();

        foreach (['mentor', 'employer'] as $type) {
            $this->actingAs($admin)->post(route('admin.users.store'), [
                'name' => ucfirst($type).' Account',
                'email' => $type.'@example.test',
                'user_type' => $type,
                'status' => 'active',
                'password' => 'InitialPass123',
                'password_confirmation' => 'InitialPass123',
                'roles' => [],
            ])->assertRedirect(route('admin.users.index'))->assertSessionHasNoErrors();

            $user = User::where('email', $type.'@example.test')->firstOrFail();
            $this->assertSame($type, $user->user_type);
            $this->assertSame($type, RoleShell::roleFor($user));
        }
    }

    public function test_admin_user_list_exposes_partner_types_for_filtering_and_creation(): void
    {
        $this->actingAs($this->administrator())
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee('value="mentor"', false)
            ->assertSee('value="employer"', false)
            ->assertSee('Mentors')
            ->assertSee('Employers');
    }
}
