<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminBulkUserStatusTest extends TestCase
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

    public function test_admin_can_update_status_of_selected_users(): void
    {
        $admin = $this->administrator();
        $selected = User::factory()->count(3)->create(['user_type' => 'participant', 'status' => 'pending']);
        $untouched = User::factory()->create(['user_type' => 'participant', 'status' => 'pending']);

        $this->actingAs($admin)
            ->from(route('admin.participants.index'))
            ->post(route('admin.users.bulk-status'), [
                'ids' => $selected->pluck('id')->all(),
                'status' => 'active',
            ])
            ->assertRedirect(route('admin.participants.index'))
            ->assertSessionHas('success', '3 user(s) set to Active.');

        foreach ($selected as $user) {
            $this->assertSame('active', $user->fresh()->status);
        }
        $this->assertSame('pending', $untouched->fresh()->status);
    }

    public function test_bulk_deactivation_skips_own_account_and_super_administrators(): void
    {
        $admin = $this->administrator();
        $otherSuper = $this->administrator();
        $participant = User::factory()->create(['user_type' => 'participant', 'status' => 'active']);

        $this->actingAs($admin)
            ->post(route('admin.users.bulk-status'), [
                'ids' => [$admin->id, $otherSuper->id, $participant->id],
                'status' => 'suspended',
            ])
            ->assertSessionHas('success');

        $this->assertSame('active', $admin->fresh()->status);
        $this->assertSame('active', $otherSuper->fresh()->status);
        $this->assertSame('suspended', $participant->fresh()->status);
    }

    public function test_bulk_status_rejects_unknown_status(): void
    {
        $participant = User::factory()->create(['user_type' => 'participant', 'status' => 'active']);

        $this->actingAs($this->administrator())
            ->post(route('admin.users.bulk-status'), [
                'ids' => [$participant->id],
                'status' => 'banished',
            ])
            ->assertSessionHasErrors('status');

        $this->assertSame('active', $participant->fresh()->status);
    }

    public function test_user_without_edit_permission_cannot_bulk_update_status(): void
    {
        $staff = User::factory()->create(['user_type' => 'staff', 'status' => 'active']);
        $participant = User::factory()->create(['user_type' => 'participant', 'status' => 'active']);

        $this->actingAs($staff)
            ->post(route('admin.users.bulk-status'), [
                'ids' => [$participant->id],
                'status' => 'inactive',
            ]);

        $this->assertSame('active', $participant->fresh()->status);
    }

    public function test_user_list_shows_bulk_status_controls(): void
    {
        $this->actingAs($this->administrator())
            ->get(route('admin.participants.index'))
            ->assertOk()
            ->assertSee(route('admin.users.bulk-status'), false)
            ->assertSee('Update Status');
    }
}
