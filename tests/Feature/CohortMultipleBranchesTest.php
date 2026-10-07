<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Cohort;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CohortMultipleBranchesTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_assign_multiple_branches_to_a_cohort_and_update_them(): void
    {
        $admin = User::factory()->create(['user_type' => 'staff', 'status' => 'active']);
        $admin->roles()->attach(Role::firstOrCreate(
            ['slug' => 'super-administrator'],
            ['name' => 'Super Administrator']
        ));
        $branches = collect(['Kampala', 'Gulu', 'Mbarara'])->map(
            fn (string $name) => Branch::create(['name' => $name])
        );

        $this->actingAs($admin)
            ->post(route('admin.cohorts.store'), [
                'name' => 'Regional Cohort',
                'status' => 'open',
                'branch_ids' => $branches->take(2)->pluck('id')->all(),
            ])
            ->assertRedirect(route('admin.cohorts.index'))
            ->assertSessionHasNoErrors();

        $cohort = Cohort::where('name', 'Regional Cohort')->firstOrFail();
        $this->assertEqualsCanonicalizing(
            $branches->take(2)->pluck('id')->all(),
            $cohort->branches()->pluck('branches.id')->all()
        );
        $this->assertSame($branches[0]->id, $cohort->branch_id);

        $this->actingAs($admin)
            ->put(route('admin.cohorts.update', $cohort), [
                'name' => 'Regional Cohort',
                'status' => 'active',
                'branch_ids' => [$branches[2]->id],
            ])
            ->assertRedirect(route('admin.cohorts.index'))
            ->assertSessionHasNoErrors();

        $this->assertSame([$branches[2]->id], $cohort->fresh()->branches()->pluck('branches.id')->all());
        $this->assertSame($branches[2]->id, $cohort->fresh()->branch_id);
    }
}
