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

    public function test_admin_can_move_selected_participants_to_a_branch(): void
    {
        $kampala = \App\Models\Branch::create(['name' => 'Kampala Hub', 'code' => 'KLA', 'is_active' => true]);
        $gulu = \App\Models\Branch::create(['name' => 'Gulu Hub', 'code' => 'GUL', 'is_active' => true]);

        $moving = User::factory()->count(2)->create(['user_type' => 'participant', 'status' => 'active']);
        foreach ($moving as $participant) {
            $participant->profile()->updateOrCreate(['user_id' => $participant->id], ['branch_id' => $kampala->id]);
            $participant->branches()->sync([$kampala->id]);
        }
        $staying = User::factory()->create(['user_type' => 'participant', 'status' => 'active']);
        $staying->profile()->updateOrCreate(['user_id' => $staying->id], ['branch_id' => $kampala->id]);

        $this->actingAs($this->administrator())
            ->from(route('admin.participants.index'))
            ->post(route('admin.users.bulk-branch'), [
                'ids' => $moving->pluck('id')->all(),
                'branch_id' => $gulu->id,
            ])
            ->assertRedirect(route('admin.participants.index'))
            ->assertSessionHas('success', '2 user(s) assigned to Gulu Hub.');

        foreach ($moving as $participant) {
            $participant->refresh();
            $this->assertSame($gulu->id, (int) $participant->profile->branch_id);
            $this->assertSame([$gulu->id], $participant->branches()->pluck('branches.id')->map(fn ($id) => (int) $id)->all());
        }
        $this->assertSame($kampala->id, (int) $staying->fresh()->profile->branch_id);
    }

    public function test_bulk_branch_adds_to_staff_branches_and_skips_users_already_there(): void
    {
        $kampala = \App\Models\Branch::create(['name' => 'Kampala Hub', 'code' => 'KLA', 'is_active' => true]);
        $gulu = \App\Models\Branch::create(['name' => 'Gulu Hub', 'code' => 'GUL', 'is_active' => true]);

        $staff = User::factory()->create(['user_type' => 'staff', 'status' => 'active']);
        $staff->branches()->sync([$kampala->id]);
        $alreadyThere = User::factory()->create(['user_type' => 'participant', 'status' => 'active']);
        $alreadyThere->profile()->updateOrCreate(['user_id' => $alreadyThere->id], ['branch_id' => $gulu->id]);
        $alreadyThere->branches()->sync([$gulu->id]);

        $this->actingAs($this->administrator())
            ->post(route('admin.users.bulk-branch'), [
                'ids' => [$staff->id, $alreadyThere->id],
                'branch_id' => $gulu->id,
            ])
            ->assertSessionHas('success', '1 user(s) assigned to Gulu Hub. 1 already there.');

        $this->assertEqualsCanonicalizing(
            [$kampala->id, $gulu->id],
            $staff->branches()->pluck('branches.id')->map(fn ($id) => (int) $id)->all()
        );
    }

    public function test_bulk_branch_requires_edit_permission_and_a_real_branch(): void
    {
        $participant = User::factory()->create(['user_type' => 'participant', 'status' => 'active']);

        $this->actingAs($this->administrator())
            ->post(route('admin.users.bulk-branch'), ['ids' => [$participant->id], 'branch_id' => 9999])
            ->assertSessionHasErrors('branch_id');

        $branch = \App\Models\Branch::create(['name' => 'Gulu Hub', 'code' => 'GUL', 'is_active' => true]);
        $viewer = User::factory()->create(['user_type' => 'staff', 'status' => 'active']);

        $this->actingAs($viewer)
            ->post(route('admin.users.bulk-branch'), ['ids' => [$participant->id], 'branch_id' => $branch->id]);

        $this->assertNull($participant->fresh()->profile?->branch_id);
    }

    public function test_user_list_shows_bulk_branch_control(): void
    {
        \App\Models\Branch::create(['name' => 'Gulu Hub', 'code' => 'GUL', 'is_active' => true]);

        $this->actingAs($this->administrator())
            ->get(route('admin.participants.index'))
            ->assertOk()
            ->assertSee(route('admin.users.bulk-branch'), false)
            ->assertSee('Update Branch');
    }
}
