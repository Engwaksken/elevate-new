<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\Department;
use App\Models\Employee;
use App\Models\FundingSource;
use App\Models\Permission;
use App\Models\PurchaseRequest;
use App\Models\Role;
use App\Models\User;
use App\Models\Workplan;
use App\Services\PurchaseRequestService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

/**
 * Departments and funding sources are managed lists that staff pick from
 * instead of typing free text.
 */
class MasterListsTest extends TestCase
{
    use RefreshDatabase;

    private function staff(array $roles = [], array $permissions = []): User
    {
        $user = User::factory()->create(['user_type' => 'staff', 'status' => 'active']);
        foreach ($roles as $slug) {
            $role = Role::firstOrCreate(['slug' => $slug], ['name' => ucwords(str_replace('-', ' ', $slug))]);
            foreach ($permissions as $perm) {
                $role->permissions()->syncWithoutDetaching([
                    Permission::firstOrCreate(['slug' => $perm], ['name' => $perm, 'module' => explode('.', $perm)[0]])->id,
                ]);
            }
            $user->roles()->attach($role);
        }

        return $user;
    }

    private function admin(): User
    {
        return $this->staff(['super-administrator']);
    }

    private function purchasePayload(array $overrides = []): array
    {
        return $overrides + [
            'justification' => 'Training supplies',
            'items' => [['item_name' => 'Markers', 'quantity' => 2, 'estimated_unit_cost' => 3000]],
        ];
    }

    // ---- seeding ---------------------------------------------------------

    public function test_migrations_seed_default_lists_on_an_empty_database(): void
    {
        $this->assertEqualsCanonicalizing(
            ['Programmes', 'Finance & Administration', 'Human Resources', 'IT', 'Monitoring & Evaluation', 'Communications'],
            Department::pluck('name')->all()
        );
        $this->assertEqualsCanonicalizing(['Core funding', 'Unrestricted'], FundingSource::pluck('name')->all());
    }

    public function test_seed_migrations_use_existing_text_values_and_only_run_when_empty(): void
    {
        $departments = require database_path('migrations/2026_10_08_000200_seed_default_departments.php');
        $funding = require database_path('migrations/2026_10_08_000100_create_funding_sources_table.php');

        DB::table('departments')->delete();
        DB::table('funding_sources')->delete();
        $user = $this->staff();
        foreach ([['Logistics', 'USAID grant'], [' Logistics ', 'USAID grant'], ['Field Ops', null]] as $i => [$dept, $fund]) {
            DB::table('purchase_requests')->insert(['request_number' => 'PR-'.$i, 'requester_user_id' => $user->id, 'department' => $dept, 'funding_source' => $fund, 'status' => 'draft', 'created_at' => now(), 'updated_at' => now()]);
        }

        $departments->up();
        $funding->up();

        $this->assertEqualsCanonicalizing(['Logistics', 'Field Ops'], Department::pluck('name')->all());
        $this->assertSame(['USAID grant'], FundingSource::pluck('name')->all());

        // Not empty any more: running again changes nothing.
        DB::table('purchase_requests')->insert(['request_number' => 'PR-9', 'requester_user_id' => $user->id, 'department' => 'New Dept', 'funding_source' => 'New Fund', 'status' => 'draft', 'created_at' => now(), 'updated_at' => now()]);
        $departments->up();
        $funding->up();

        $this->assertSame(2, Department::count());
        $this->assertSame(1, FundingSource::count());
    }

    // ---- permissions -----------------------------------------------------

    public function test_only_hr_and_admins_can_manage_departments(): void
    {
        $this->actingAs($this->staff())->get(route('admin.departments.index'))->assertForbidden();
        $this->actingAs($this->staff())->post(route('admin.departments.store'), ['name' => 'X', 'is_active' => 1])->assertForbidden();
        $this->actingAs($this->staff(['procurement-officer'], ['procurement.create']))->get(route('admin.departments.index'))->assertForbidden();

        $this->actingAs($this->staff(['hr']))->get(route('admin.departments.index'))->assertOk()->assertSee('Departments')->assertSee('Programmes');
        $this->actingAs($this->staff(['people-ops'], ['hr.manage']))->get(route('admin.departments.index'))->assertOk();
        $this->actingAs($this->admin())->get(route('admin.departments.index'))->assertOk();
    }

    public function test_only_procurement_finance_and_admins_can_manage_funding_sources(): void
    {
        $this->actingAs($this->staff())->get(route('admin.funding-sources.index'))->assertForbidden();
        $this->actingAs($this->staff(['hr']))->get(route('admin.funding-sources.index'))->assertForbidden();
        $this->actingAs($this->staff())->delete(route('admin.funding-sources.destroy', FundingSource::first()))->assertForbidden();

        $this->actingAs($this->staff(['finance']))->get(route('admin.funding-sources.index'))->assertOk()->assertSee('Core funding');
        $this->actingAs($this->staff(['buyer'], ['procurement.create']))->get(route('admin.funding-sources.index'))->assertOk();
        $this->actingAs($this->admin())->get(route('admin.funding-sources.index'))->assertOk();
    }

    public function test_sidebar_links_follow_access(): void
    {
        $this->actingAs($this->staff())->get(route('staff.purchase-requests.index'))
            ->assertDontSee(route('admin.departments.index'))->assertDontSee(route('admin.funding-sources.index'));

        $this->actingAs($this->staff(['hr']))->get(route('staff.purchase-requests.index'))
            ->assertSee(route('admin.departments.index'))->assertDontSee(route('admin.funding-sources.index'));

        $this->actingAs($this->admin())->get(route('admin.departments.index'))
            ->assertSee(route('admin.funding-sources.index'));
    }

    // ---- CRUD ------------------------------------------------------------

    public function test_department_crud(): void
    {
        $admin = $this->admin();
        $head = $this->staff();

        $this->actingAs($admin)->post(route('admin.departments.store'), ['name' => '  Logistics ', 'code' => 'LOG', 'head_user_id' => $head->id, 'is_active' => 1])
            ->assertRedirect(route('admin.departments.index'));
        $dept = Department::where('name', 'Logistics')->sole();
        $this->assertSame('LOG', $dept->code);
        $this->assertTrue($dept->is_active);
        $this->assertSame($head->id, (int) $dept->head_user_id);

        // Duplicate name rejected.
        $this->actingAs($admin)->post(route('admin.departments.store'), ['name' => 'Logistics'])->assertSessionHasErrors('name');

        // Rename carries over to purchase requests that used the old name.
        $pr = PurchaseRequest::create(['request_number' => 'PR-1', 'requester_user_id' => $admin->id, 'department' => 'Logistics', 'status' => 'draft']);
        $this->actingAs($admin)->put(route('admin.departments.update', $dept), ['name' => 'Logistics & Fleet', 'code' => 'LOG', 'is_active' => 1])
            ->assertRedirect(route('admin.departments.index'));
        $this->assertSame('Logistics & Fleet', $dept->fresh()->name);
        $this->assertSame('Logistics & Fleet', $pr->fresh()->department);

        // Deactivate / reactivate.
        $this->actingAs($admin)->patch(route('admin.departments.toggle', $dept))->assertRedirect();
        $this->assertFalse($dept->fresh()->is_active);
        $this->actingAs($admin)->patch(route('admin.departments.toggle', $dept));
        $this->assertTrue($dept->fresh()->is_active);

        // In use: cannot be deleted, singly or in bulk.
        $this->actingAs($admin)->delete(route('admin.departments.destroy', $dept))->assertSessionHas('error');
        $this->assertModelExists($dept);
        $unused = Department::create(['name' => 'Temporary', 'is_active' => true]);
        $this->actingAs($admin)->delete(route('admin.departments.bulk-destroy'), ['ids' => [$dept->id, $unused->id]]);
        $this->assertModelExists($dept);
        $this->assertModelMissing($unused);

        // Employee use also counts.
        $pr->delete();
        Employee::create(['user_id' => $head->id, 'employee_number' => 'E-1', 'department_id' => $dept->id]);
        $this->actingAs($admin)->delete(route('admin.departments.destroy', $dept))->assertSessionHas('error');
        $this->assertModelExists($dept);

        // Unused: deleted.
        $other = Department::create(['name' => 'Spare', 'is_active' => true]);
        $this->actingAs($admin)->delete(route('admin.departments.destroy', $other))->assertSessionHas('success');
        $this->assertModelMissing($other);
    }

    public function test_funding_source_crud_and_export(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.funding-sources.store'), ['name' => 'MCF Grant', 'code' => 'MCF', 'description' => '2026-2028', 'is_active' => 1])
            ->assertRedirect(route('admin.funding-sources.index'));
        $source = FundingSource::where('name', 'MCF Grant')->sole();

        $this->actingAs($admin)->get(route('admin.funding-sources.index', ['search' => 'MCF']))->assertOk()->assertSee('MCF Grant')->assertDontSee('Unrestricted');
        $this->actingAs($admin)->get(route('admin.funding-sources.index', ['export' => 'csv']))->assertOk();

        Asset::create(['asset_code' => 'A-1', 'description' => 'Laptop', 'funding_source' => 'MCF Grant', 'status' => 'available']);
        $this->actingAs($admin)->put(route('admin.funding-sources.update', $source), ['name' => 'MCF Grant II', 'is_active' => 0]);
        $this->assertFalse($source->fresh()->is_active);
        $this->assertSame('MCF Grant II', Asset::first()->funding_source);

        $this->actingAs($admin)->delete(route('admin.funding-sources.destroy', $source))->assertSessionHas('error');
        $this->assertModelExists($source);

        $unused = FundingSource::where('name', 'Unrestricted')->sole();
        $this->actingAs($admin)->delete(route('admin.funding-sources.destroy', $unused))->assertSessionHas('success');
        $this->assertModelMissing($unused);
    }

    // ---- forms use the lists ---------------------------------------------

    public function test_purchase_request_forms_show_selects_of_active_values(): void
    {
        Department::where('name', 'IT')->update(['is_active' => false]);

        $this->actingAs($this->staff())->get(route('staff.purchase-requests.index'))->assertOk()
            ->assertSee('<select name="department"', false)
            ->assertSee('<select name="funding_source"', false)
            ->assertSee('<option value="Programmes"', false)
            ->assertSee('<option value="Core funding"', false)
            ->assertDontSee('<option value="IT"', false);

        $this->actingAs($this->admin())->get(route('admin.procurement.requests.index'))->assertOk()
            ->assertSee('<select name="department"', false)
            ->assertSee('<select name="funding_source"', false);

        $this->actingAs($this->admin())->get(route('admin.assets.index'))->assertOk()
            ->assertSee('<select name="funding_source"', false);

        Workplan::create(['title' => 'Annual plan', 'period_type' => 'annual', 'status' => 'draft']);
        $this->actingAs($this->admin())->get(route('admin.workplans.index'))->assertOk()
            ->assertSee('<select name="funding_source"', false);
    }

    public function test_staff_default_department_is_preselected_when_active(): void
    {
        $me = $this->staff();
        $dept = Department::where('name', 'Communications')->sole();
        Employee::create(['user_id' => $me->id, 'employee_number' => 'E-9', 'department_id' => $dept->id]);

        $this->actingAs($me)->get(route('staff.purchase-requests.index'))
            ->assertSee('<option value="Communications" selected', false);
    }

    public function test_purchase_request_store_only_accepts_active_list_values(): void
    {
        $me = $this->staff();
        Department::where('name', 'IT')->update(['is_active' => false]);

        $this->actingAs($me)->post(route('staff.purchase-requests.store'), $this->purchasePayload(['department' => 'Made Up Dept']))
            ->assertSessionHasErrors('department');
        $this->actingAs($me)->post(route('staff.purchase-requests.store'), $this->purchasePayload(['department' => 'IT']))
            ->assertSessionHasErrors('department');
        $this->actingAs($me)->post(route('staff.purchase-requests.store'), $this->purchasePayload(['funding_source' => 'Unknown donor']))
            ->assertSessionHasErrors('funding_source');
        $this->actingAs($this->admin())->post(route('admin.procurement.requests.store'), $this->purchasePayload(['funding_source' => 'Unknown donor']))
            ->assertSessionHasErrors('funding_source');
        $this->assertSame(0, PurchaseRequest::count());

        $this->actingAs($me)->post(route('staff.purchase-requests.store'), $this->purchasePayload(['department' => 'Programmes', 'funding_source' => 'Core funding']))
            ->assertSessionHasNoErrors();
        $pr = PurchaseRequest::sole();
        $this->assertSame('Programmes', $pr->department);
        $this->assertSame('Core funding', $pr->funding_source);

        // Both stay optional.
        $this->actingAs($me)->post(route('staff.purchase-requests.store'), $this->purchasePayload())->assertSessionHasNoErrors();
    }

    public function test_asset_and_activity_reject_unknown_funding_source(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->post(route('admin.assets.store'), ['description' => 'Projector', 'funding_source' => 'Typed by hand'])
            ->assertSessionHasErrors('funding_source');
        $this->actingAs($admin)->post(route('admin.assets.store'), ['description' => 'Projector', 'funding_source' => 'Unrestricted'])
            ->assertSessionHasNoErrors();

        $workplan = Workplan::create(['title' => 'Plan', 'period_type' => 'annual', 'status' => 'draft']);
        $this->actingAs($admin)->post(route('admin.activities.store', $workplan), ['title' => 'Bootcamp', 'funding_source' => 'Typed by hand'])
            ->assertSessionHasErrors('funding_source');
        $this->actingAs($admin)->post(route('admin.activities.store', $workplan), ['title' => 'Bootcamp', 'funding_source' => 'Core funding'])
            ->assertSessionHasNoErrors();
    }

    public function test_employee_department_must_be_active(): void
    {
        $inactive = Department::create(['name' => 'Closed Unit', 'is_active' => false]);
        $this->actingAs($this->admin())->post(route('admin.hr.employees.store'), [
            'user_id' => $this->staff()->id, 'employee_number' => 'E-77', 'status' => 'active', 'department_id' => $inactive->id,
        ])->assertSessionHasErrors('department_id');
    }

    // ---- older free-text records -----------------------------------------

    public function test_older_records_with_unlisted_text_still_display_and_validate(): void
    {
        $me = $this->staff();
        $old = PurchaseRequest::create(['request_number' => 'PR-OLD', 'requester_user_id' => $me->id, 'department' => 'Legacy Typed Dept', 'funding_source' => 'Old Donor', 'status' => 'draft']);

        $this->actingAs($me)->get(route('staff.purchase-requests.index'))->assertOk()->assertSee('Legacy Typed Dept');
        $this->actingAs($this->admin())->get(route('admin.procurement.requests.index'))->assertOk()
            ->assertSee('Legacy Typed Dept')->assertSee('Old Donor');

        // Editing that record may keep its existing value; a new record may not use it.
        $payload = $this->purchasePayload(['department' => 'Legacy Typed Dept', 'funding_source' => 'Old Donor']);
        $this->assertTrue(Validator::make($payload, PurchaseRequestService::rules($old))->passes());
        $this->assertTrue(Validator::make($payload, PurchaseRequestService::rules())->fails());
        $this->assertTrue(Validator::make($this->purchasePayload(['department' => 'Other Unlisted']), PurchaseRequestService::rules($old))->fails());

        // The select keeps showing the stored value, flagged as off-list.
        $html = $this->blade('<x-list-select name="department" :options="$o" :selected="$s" />', ['o' => ['Programmes'], 's' => 'Legacy Typed Dept']);
        $html->assertSee('<option value="Legacy Typed Dept" selected>Legacy Typed Dept (not on the list)</option>', false);
        $html->assertSee('<option value="Programmes"', false);
    }
}
