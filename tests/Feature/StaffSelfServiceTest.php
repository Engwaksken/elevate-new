<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\PurchaseRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaffSelfServiceTest extends TestCase
{
    use RefreshDatabase;

    private function staff(): User
    {
        return User::factory()->create(['user_type' => 'staff', 'status' => 'active']);
    }

    private function requestPayload(array $overrides = []): array
    {
        return $overrides + [
            'department' => 'Programmes',
            'justification' => 'Needed for the training',
            'items' => [
                ['item_name' => 'Flip chart paper', 'quantity' => 4, 'unit' => 'pads', 'estimated_unit_cost' => 25000],
                ['item_name' => 'Markers', 'quantity' => 10, 'estimated_unit_cost' => 3000],
            ],
        ];
    }

    public function test_any_staff_member_can_raise_a_purchase_request_without_procurement_permissions(): void
    {
        $me = $this->staff();

        $this->actingAs($me)->get(route('staff.purchase-requests.index'))->assertOk()->assertSee('My Purchase Requests');

        $this->actingAs($me)
            ->post(route('staff.purchase-requests.store'), $this->requestPayload(['submit_now' => '1']))
            ->assertRedirect(route('staff.purchase-requests.index'));

        $request = PurchaseRequest::with('items')->sole();
        $this->assertSame($me->id, (int) $request->requester_user_id);
        $this->assertSame('submitted', $request->status);
        $this->assertCount(2, $request->items);
        $this->assertEquals(130000, (float) $request->estimated_total);
    }

    public function test_draft_can_be_submitted_or_deleted_only_by_its_owner(): void
    {
        $me = $this->staff();
        $other = $this->staff();

        $this->actingAs($me)->post(route('staff.purchase-requests.store'), $this->requestPayload());
        $draft = PurchaseRequest::sole();
        $this->assertSame('draft', $draft->status);

        $this->actingAs($other)->post(route('staff.purchase-requests.submit', $draft))->assertForbidden();
        $this->actingAs($other)->delete(route('staff.purchase-requests.destroy', $draft))->assertForbidden();

        $this->actingAs($me)->post(route('staff.purchase-requests.submit', $draft))->assertRedirect();
        $this->assertSame('submitted', $draft->fresh()->status);

        // Once submitted it can no longer be deleted from self-service.
        $this->actingAs($me)->delete(route('staff.purchase-requests.destroy', $draft))->assertStatus(422);
    }

    public function test_staff_only_see_their_own_purchase_requests(): void
    {
        $me = $this->staff();
        $other = $this->staff();

        $this->actingAs($me)->post(route('staff.purchase-requests.store'), $this->requestPayload(['justification' => 'My own laptop bag']));
        $this->actingAs($other)->post(route('staff.purchase-requests.store'), $this->requestPayload(['justification' => 'Colleague private item']));

        $this->actingAs($me)->get(route('staff.purchase-requests.index'))
            ->assertOk()
            ->assertSee('My own laptop bag')
            ->assertDontSee('Colleague private item');
    }

    public function test_participants_cannot_use_staff_purchase_requests(): void
    {
        $participant = User::factory()->create(['user_type' => 'participant', 'status' => 'active']);

        $response = $this->actingAs($participant)->get(route('staff.purchase-requests.index'));
        $this->assertNotSame(200, $response->getStatusCode());
    }

    public function test_leave_page_explains_missing_employee_record_instead_of_404(): void
    {
        $this->actingAs($this->staff())
            ->get(route('hr.leave.index'))
            ->assertOk()
            ->assertSee('staff record isn’t set up yet', false);
    }

    public function test_staff_with_employee_record_can_request_leave(): void
    {
        $type = LeaveType::firstOrCreate(['name' => 'Annual Leave'], ['code' => 'AL', 'default_days' => 21, 'is_active' => true]);
        $me = $this->staff();
        $employee = Employee::create(['user_id' => $me->id, 'employee_number' => 'EMP-1']);

        $this->actingAs($me)->get(route('hr.leave.index'))->assertOk()->assertSee('Request leave')->assertSee('Annual Leave');

        $start = today()->next('Monday');
        $this->actingAs($me)->post(route('hr.leave.store'), [
            'leave_type_id' => $type->id,
            'start_date' => $start->toDateString(),
            'end_date' => $start->copy()->addDays(4)->toDateString(),
            'reason' => 'Family visit',
        ])->assertRedirect(route('hr.leave.index'));

        $leave = LeaveRequest::sole();
        $this->assertSame($employee->id, (int) $leave->employee_id);
        $this->assertSame('submitted', $leave->status);
        $this->assertEquals(5, (float) $leave->days_requested);
    }

    public function test_sidebar_shows_self_service_links_to_staff_without_permissions(): void
    {
        $this->actingAs($this->staff())
            ->get(route('staff.purchase-requests.index'))
            ->assertSee(route('hr.leave.index'), false)
            ->assertSee('My Purchase Requests');
    }
}
