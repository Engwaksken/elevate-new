<?php

namespace Tests\Feature\Admin;

use App\Models\Programme;
use App\Models\ProgrammeTarget;
use App\Models\Role;
use App\Models\User;
use App\Models\Workplan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProgrammeTargetAndWorkplanApprovalTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $user = User::factory()->create(['user_type' => 'staff', 'status' => 'active']);
        $role = Role::firstOrCreate(['slug' => 'super-administrator'], ['name' => 'Super Administrator']);

        if (method_exists($user, 'assignRole')) {
            $user->assignRole($role);
        } else {
            $user->roles()->attach($role);
        }

        $this->actingAs($user);

        return $user;
    }

    private function programme(): Programme
    {
        return Programme::create(['name' => 'Digital Skills', 'status' => 'active']);
    }

    public function test_admin_can_add_programme_targets_and_progress_is_tracked(): void
    {
        $this->admin();
        $programme = $this->programme();

        $this->post(route('admin.programmes.targets.store', $programme), [
            'name' => '500 women trained',
            'target_value' => 500,
            'achieved_value' => 125,
            'unit' => 'women',
            'weight' => 2,
        ])->assertRedirect();

        $target = ProgrammeTarget::firstOrFail();
        $this->assertSame('in_progress', $target->status);
        $this->assertSame(25.0, (float) $target->progress_percent);
        $this->assertSame(25.0, (float) $programme->fresh()->progress_percent);

        $this->put(route('admin.programme-targets.update', $target), [
            'name' => '500 women trained',
            'target_value' => 500,
            'achieved_value' => 500,
        ])->assertRedirect();

        $this->assertSame(100.0, (float) $target->fresh()->progress_percent);
        $this->assertSame('achieved', $target->fresh()->status);
        $this->assertSame(100.0, (float) $programme->fresh()->progress_percent);
    }

    public function test_admin_can_approve_return_and_reject_workplans(): void
    {
        $this->admin();

        $submitted = Workplan::create(['title' => 'Q1 plan', 'period_type' => 'quarterly', 'status' => 'submitted']);
        $this->post(route('admin.workplans.approve', $submitted), ['comments' => 'Looks good'])
            ->assertRedirect();

        $submitted->refresh();
        $this->assertSame('approved', $submitted->status);
        $this->assertNotNull($submitted->approved_at);
        $this->assertSame('approved', $submitted->approvals()->first()->action);

        $toReturn = Workplan::create(['title' => 'Q2 plan', 'period_type' => 'quarterly', 'status' => 'submitted']);
        $this->post(route('admin.workplans.return', $toReturn), ['comments' => 'Add milestones'])
            ->assertRedirect();
        $this->assertSame('draft', $toReturn->fresh()->status);

        $toReject = Workplan::create(['title' => 'Q3 plan', 'period_type' => 'quarterly', 'status' => 'submitted']);
        $this->post(route('admin.workplans.reject', $toReject), ['comments' => 'Out of scope'])
            ->assertRedirect();
        $this->assertSame('cancelled', $toReject->fresh()->status);
    }

    public function test_return_requires_a_comment(): void
    {
        $this->admin();
        $workplan = Workplan::create(['title' => 'Needs notes', 'period_type' => 'quarterly', 'status' => 'submitted']);

        $this->post(route('admin.workplans.return', $workplan), [])
            ->assertSessionHasErrors('comments');

        $this->assertSame('submitted', $workplan->fresh()->status);
    }
}
