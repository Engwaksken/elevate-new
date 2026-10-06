<?php

namespace Tests\Feature;

use App\Models\ItSupportTicket;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ItSupportQueuePageTest extends TestCase
{
    use RefreshDatabase;

    private function user(array $roles = [], string $type = 'staff'): User
    {
        $user = User::factory()->create(['user_type' => $type, 'status' => 'active']);
        foreach ($roles as $slug) {
            $user->roles()->attach(Role::firstOrCreate(['slug' => $slug], ['name' => ucwords(str_replace('-', ' ', $slug))]));
        }

        return $user;
    }

    private function ticket(array $attrs = []): ItSupportTicket
    {
        return ItSupportTicket::create($attrs + [
            'subject' => 'Wi-Fi keeps dropping', 'description' => 'Since this morning.', 'category' => 'network',
            'priority' => 'high', 'status' => 'open', 'requester_id' => $this->user()->id,
        ]);
    }

    public function test_queue_uses_the_staff_layout_with_cards_and_filters(): void
    {
        $lead = $this->user(['it-lead']);
        $this->ticket();

        $this->actingAs($lead)->get(route('it-support.tickets.index'))
            ->assertOk()
            ->assertSee('eh-admin', false)          // staff layout
            ->assertSee('class="itq-grid"', false)
            ->assertSee('Wi-Fi keeps dropping');

        // Empty filter values (the "All …" options) must not be rejected.
        $this->actingAs($lead)->get(route('it-support.tickets.index', ['status' => '', 'priority' => '', 'category' => '', 'assignee_id' => '', 'search' => '']))
            ->assertOk()->assertSee('Wi-Fi keeps dropping');
    }

    public function test_assignee_filter_supports_unassigned_and_search(): void
    {
        $lead = $this->user(['it-lead']);
        $this->ticket(['subject' => 'Unassigned printer jam']);
        $this->ticket(['subject' => 'Assigned laptop issue', 'assignee_id' => $lead->id]);

        $this->actingAs($lead)->get(route('it-support.tickets.index', ['assignee_id' => 'none']))
            ->assertOk()->assertSee('Unassigned printer jam')->assertDontSee('Assigned laptop issue');

        $this->actingAs($lead)->get(route('it-support.tickets.index', ['search' => 'laptop']))
            ->assertOk()->assertSee('Assigned laptop issue')->assertDontSee('Unassigned printer jam');
    }

    public function test_super_admins_and_administrators_can_oversee_but_plain_staff_cannot(): void
    {
        $ticket = $this->ticket();

        foreach (['super-administrator', 'administrator'] as $role) {
            $this->actingAs($this->user([$role]))->get(route('it-support.tickets.index'))->assertOk()->assertSee('Wi-Fi keeps dropping');
            $this->actingAs($this->user([$role]))->get(route('it-support.tickets.show', $ticket))->assertOk()->assertSee('Queue controls');
        }

        $this->actingAs($this->user())->get(route('it-support.tickets.index'))->assertForbidden();
    }

    public function test_tickets_can_only_be_assigned_to_the_it_team(): void
    {
        $admin = $this->user(['administrator']);
        $assistant = $this->user(['it-assistant']);
        $ticket = $this->ticket();

        $this->actingAs($admin)->patch(route('it-support.tickets.assignee', $ticket), ['assignee_id' => $assistant->id])->assertRedirect();
        $this->assertSame($assistant->id, (int) $ticket->fresh()->assignee_id);

        // Not IT: refused (validation or policy), and the assignment is unchanged.
        $response = $this->actingAs($admin)->patch(route('it-support.tickets.assignee', $ticket), ['assignee_id' => $admin->id]);
        $this->assertContains($response->getStatusCode(), [302, 403, 422]);
        $this->assertSame($assistant->id, (int) $ticket->fresh()->assignee_id);
    }

    public function test_dashboard_shows_the_it_summary_to_the_it_team_only(): void
    {
        $this->ticket(['subject' => 'Urgent VPN outage', 'priority' => 'urgent']);

        $this->actingAs($this->user(['it-lead']))->get(route('admin.dashboard'))
            ->assertOk()->assertSee('IT Support')->assertSee('Urgent VPN outage');

        $this->actingAs($this->user())->get(route('admin.dashboard'))
            ->assertOk()->assertDontSee('Urgent VPN outage');
    }
}
