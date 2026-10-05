<?php

namespace Tests\Feature;

use App\Models\ItSupportTicket;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupportTicketQueueBrowserTest extends TestCase
{
    use RefreshDatabase;

    private function supportUser(): User
    {
        $user = User::factory()->create(['user_type' => 'staff', 'status' => 'active']);
        $role = Role::firstOrCreate(['slug' => 'it-lead'], ['name' => 'IT Lead']);
        $user->roles()->attach($role);

        return $user;
    }

    public function test_queue_index_renders_for_support_staff_and_lists_tickets(): void
    {
        $staff = $this->supportUser();
        $ticket = ItSupportTicket::factory()->create(['subject' => 'Queue render target']);

        $this->actingAs($staff)->get(route('it-support.tickets.index'))
            ->assertOk()
            ->assertViewIs('support.tickets.index')
            ->assertSee($ticket->subject);
    }

    public function test_queue_index_filters_by_status(): void
    {
        $staff = $this->supportUser();
        $open = ItSupportTicket::factory()->create(['subject' => 'Open queue ticket', 'status' => 'open']);
        ItSupportTicket::factory()->create(['subject' => 'Resolved queue ticket', 'status' => 'resolved']);

        $this->actingAs($staff)->get(route('it-support.tickets.index', ['status' => 'open']))
            ->assertOk()
            ->assertSee($open->subject)
            ->assertDontSee('Resolved queue ticket');
    }

    public function test_queue_detail_renders_for_support_staff(): void
    {
        $staff = $this->supportUser();
        $ticket = ItSupportTicket::factory()->create();

        $this->actingAs($staff)->get(route('it-support.tickets.show', $ticket))
            ->assertOk()
            ->assertViewIs('support.tickets.show')
            ->assertSee($ticket->subject);
    }

    public function test_support_queue_requires_authentication_and_support_role(): void
    {
        $ticket = ItSupportTicket::factory()->create();

        $this->get(route('it-support.tickets.index'))->assertRedirect('/login');
        $this->get(route('it-support.tickets.show', $ticket))->assertRedirect('/login');

        $ordinaryStaff = User::factory()->create(['user_type' => 'staff', 'status' => 'active']);
        $this->actingAs($ordinaryStaff)->get(route('it-support.tickets.index'))->assertForbidden();
    }

    public function test_queue_detail_is_forbidden_to_a_non_support_user(): void
    {
        $ticket = ItSupportTicket::factory()->create();
        $ordinaryStaff = User::factory()->create(['user_type' => 'staff', 'status' => 'active']);

        $this->actingAs($ordinaryStaff)->get(route('it-support.tickets.show', $ticket))->assertForbidden();
    }

    public function test_queue_can_filter_by_priority_category_and_assignee(): void
    {
        $staff = $this->supportUser();
        $assignee = $this->supportUser();
        $match = ItSupportTicket::factory()->create([
            'subject' => 'Matching filtered ticket', 'priority' => 'urgent',
            'category' => 'access', 'assignee_id' => $assignee->id,
        ]);
        ItSupportTicket::factory()->create([
            'subject' => 'Nonmatching filtered ticket', 'priority' => 'low',
            'category' => 'general', 'assignee_id' => null,
        ]);

        $this->actingAs($staff)->get(route('it-support.tickets.index', [
            'priority' => 'urgent', 'category' => 'access', 'assignee_id' => $assignee->id,
        ]))->assertOk()->assertSee($match->subject)->assertDontSee('Nonmatching filtered ticket');
    }

    public function test_support_staff_can_update_ticket_status(): void
    {
        $staff = $this->supportUser();
        $ticket = ItSupportTicket::factory()->create(['status' => 'open']);

        $this->actingAs($staff)->patch(route('it-support.tickets.status', $ticket), ['status' => 'in_progress'])
            ->assertRedirect();

        $this->assertDatabaseHas('it_support_tickets', ['id' => $ticket->id, 'status' => 'in_progress']);
        $this->assertDatabaseHas('audit_logs', ['module' => 'it_support_ticket', 'action' => 'status_updated', 'auditable_id' => $ticket->id]);
        $this->assertDatabaseHas('user_notifications', ['user_id' => $ticket->requester_id, 'type' => 'it_support_ticket', 'title' => 'IT support ticket status updated']);
    }

    public function test_support_staff_can_assign_ticket_to_it_staff(): void
    {
        $staff = $this->supportUser();
        $assignee = $this->supportUser();
        $ticket = ItSupportTicket::factory()->create();

        $this->actingAs($staff)->patch(route('it-support.tickets.assignee', $ticket), ['assignee_id' => $assignee->id])
            ->assertRedirect();

        $this->assertDatabaseHas('it_support_tickets', ['id' => $ticket->id, 'assignee_id' => $assignee->id]);
        $this->assertDatabaseHas('audit_logs', ['module' => 'it_support_ticket', 'action' => 'assigned', 'auditable_id' => $ticket->id]);
        $this->assertDatabaseHas('user_notifications', ['user_id' => $assignee->id, 'type' => 'it_support_ticket', 'title' => 'IT support ticket assignment updated']);
    }

    public function test_reassigning_to_the_current_assignee_is_a_no_op(): void
    {
        $staff = $this->supportUser();
        $assignee = $this->supportUser();
        $ticket = ItSupportTicket::factory()->create(['assignee_id' => $assignee->id]);

        $this->actingAs($staff)->patch(route('it-support.tickets.assignee', $ticket), ['assignee_id' => $assignee->id])->assertRedirect();

        $this->assertDatabaseCount('audit_logs', 0);
        $this->assertDatabaseCount('user_notifications', 0);
    }

    public function test_status_and_assignment_changes_are_forbidden_to_non_support_staff(): void
    {
        $ticket = ItSupportTicket::factory()->create();
        $ordinaryStaff = User::factory()->create(['user_type' => 'staff', 'status' => 'active']);

        $this->actingAs($ordinaryStaff)->patch(route('it-support.tickets.status', $ticket), ['status' => 'in_progress'])->assertForbidden();
        $this->actingAs($ordinaryStaff)->patch(route('it-support.tickets.assignee', $ticket), ['assignee_id' => null])->assertForbidden();
    }

    public function test_status_and_assignment_changes_require_authentication(): void
    {
        $ticket = ItSupportTicket::factory()->create();

        $this->patch(route('it-support.tickets.status', $ticket), ['status' => 'in_progress'])->assertRedirect('/login');
        $this->patch(route('it-support.tickets.assignee', $ticket), ['assignee_id' => null])->assertRedirect('/login');
    }
}
