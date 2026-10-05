<?php

namespace Tests\Feature;

use App\Models\ItSupportTicket;
use App\Models\Role;
use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ItSupportTicketApiTest extends TestCase
{
    use RefreshDatabase;

    private function user(array $attributes = []): User
    {
        return User::factory()->create(array_merge([
            'user_type' => 'participant', 'status' => 'active', 'email_verified_at' => now(),
        ], $attributes));
    }

    private function supportUser(): User
    {
        $user = $this->user(['user_type' => 'staff']);
        $role = Role::firstOrCreate(['slug' => 'it-lead'], ['name' => 'IT Lead']);
        $user->roles()->attach($role);
        return $user;
    }

    public function test_requester_can_view_own_ticket_but_other_requesters_receive_not_found(): void
    {
        $owner = $this->user();
        $other = $this->user();
        $ticket = ItSupportTicket::factory()->create(['requester_id' => $owner->id]);

        $this->actingAs($owner)->getJson("/api/v1/it-support/tickets/{$ticket->id}")->assertOk();
        $this->actingAs($other)->getJson("/api/v1/it-support/tickets/{$ticket->id}")->assertNotFound();
    }

    public function test_requester_list_is_scoped_to_their_tickets(): void
    {
        $owner = $this->user();
        $other = $this->user();
        $ownTicket = ItSupportTicket::factory()->create(['requester_id' => $owner->id]);
        ItSupportTicket::factory()->create(['requester_id' => $other->id]);

        $this->actingAs($owner)->getJson('/api/v1/it-support/tickets')->assertOk()
            ->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $ownTicket->id);
    }

    public function test_it_support_queue_can_view_all_tickets_and_filter_by_assignment(): void
    {
        $support = $this->supportUser();
        $assigned = ItSupportTicket::factory()->create(['assignee_id' => $support->id]);
        ItSupportTicket::factory()->create();

        $this->actingAs($support)->getJson('/api/v1/it-support/tickets?assignee_id='.$support->id)
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $assigned->id);
    }

    public function test_ticket_api_requires_authentication(): void
    {
        $this->getJson('/api/v1/it-support/tickets')->assertUnauthorized();
    }

    public function test_browser_ticket_submission_uses_session_user_as_requester(): void
    {
        $requester = $this->user();

        $this->actingAs($requester)->from('/support')->post('/support/tickets', [
            'subject' => 'Browser support issue',
            'description' => 'Please assist with this issue.',
            'category' => 'access',
        ])->assertRedirect('/support');

        $this->assertDatabaseHas('it_support_tickets', [
            'requester_id' => $requester->id,
            'subject' => 'Browser support issue',
            'status' => 'open',
        ]);
    }

    public function test_browser_ticket_submission_rejects_guests(): void
    {
        $this->from('/support')->post('/support/tickets', [
            'subject' => 'Browser support issue',
            'description' => 'Please assist with this issue.',
        ])->assertRedirect('/login');
    }

    public function test_support_user_can_make_an_allowed_status_transition(): void
    {
        $ticket = ItSupportTicket::factory()->create(['status' => 'open']);
        $this->actingAs($this->supportUser())
            ->patchJson("/api/v1/it-support/tickets/{$ticket->id}/status", ['status' => 'in_progress'])
            ->assertOk()->assertJsonPath('data.status', 'in_progress');
    }

    public function test_invalid_status_transition_is_rejected(): void
    {
        $ticket = ItSupportTicket::factory()->create(['status' => 'open']);
        $this->actingAs($this->supportUser())
            ->patchJson("/api/v1/it-support/tickets/{$ticket->id}/status", ['status' => 'resolved'])
            ->assertStatus(422);
    }

    public function test_support_team_can_assign_and_unassign_ticket(): void
    {
        $support = $this->supportUser();
        $assignee = $this->supportUser();
        $ticket = ItSupportTicket::factory()->create();

        $this->actingAs($support)->patchJson("/api/v1/it-support/tickets/{$ticket->id}/assignee", ['assignee_id' => $assignee->id])
            ->assertOk()->assertJsonPath('data.assignee_id', $assignee->id);
        $this->patchJson("/api/v1/it-support/tickets/{$ticket->id}/assignee", ['assignee_id' => null])
            ->assertOk()->assertJsonPath('data.assignee_id', null);
    }

    public function test_ticket_creation_notifies_active_support_users_once_and_not_the_requester(): void
    {
        $requester = $this->user();
        $support = $this->supportUser();
        $inactiveSupport = $this->supportUser();
        $inactiveSupport->update(['status' => 'inactive']);

        $this->actingAs($requester)->postJson('/api/v1/it-support/tickets', [
            'subject' => 'Cannot access my account',
            'description' => 'Login is failing.',
            'category' => 'access',
        ])->assertCreated();

        $this->assertDatabaseHas('user_notifications', [
            'user_id' => $support->id,
            'type' => 'it_support_ticket',
            'title' => 'New IT support ticket',
        ]);
        $this->assertDatabaseMissing('user_notifications', ['user_id' => $requester->id]);
        $this->assertDatabaseMissing('user_notifications', ['user_id' => $inactiveSupport->id]);
    }

    public function test_ticket_creation_is_throttled_and_named_route_is_registered(): void
    {
        $this->assertSame('/api/v1/it-support/tickets', route('api.v1.it-support.tickets.store', [], false));
        $requester = $this->user();
        $payload = ['subject' => 'Throttle check', 'description' => 'Request details.', 'category' => 'access'];

        $this->actingAs($requester);
        for ($attempt = 0; $attempt < 10; $attempt++) {
            $this->postJson(route('api.v1.it-support.tickets.store'), $payload)->assertCreated();
        }

        $this->postJson(route('api.v1.it-support.tickets.store'), $payload)->assertTooManyRequests();
    }

    public function test_ticket_notifications_do_not_disclose_subject_or_description(): void
    {
        $requester = $this->user();
        $support = $this->supportUser();
        $secretSubject = 'Confidential account subject';
        $secretDescription = 'Confidential report body contents';

        $this->actingAs($requester)->postJson('/api/v1/it-support/tickets', [
            'subject' => $secretSubject,
            'description' => $secretDescription,
            'category' => 'access',
        ])->assertCreated();

        $notification = UserNotification::query()->where('user_id', $support->id)->firstOrFail();
        $contents = $notification->title.' '.$notification->message.' '.json_encode($notification->data);
        $this->assertStringNotContainsString($secretSubject, $contents);
        $this->assertStringNotContainsString($secretDescription, $contents);
    }

    public function test_status_update_notifies_requester_once_when_requester_is_also_assignee(): void
    {
        $support = $this->supportUser();
        $requester = $this->user();
        $ticket = ItSupportTicket::factory()->create([
            'requester_id' => $requester->id,
            'assignee_id' => $requester->id,
            'status' => 'open',
        ]);

        $this->actingAs($support)->patchJson("/api/v1/it-support/tickets/{$ticket->id}/status", [
            'status' => 'in_progress',
        ])->assertOk();

        $this->assertSame(1, UserNotification::query()->where('user_id', $requester->id)
            ->where('type', 'it_support_ticket')->count());
        $this->assertDatabaseHas('user_notifications', [
            'user_id' => $requester->id,
            'type' => 'it_support_ticket',
            'data' => json_encode(['ticket_id' => $ticket->id, 'status' => 'in_progress']),
        ]);
    }

    public function test_assigning_the_existing_assignee_does_not_send_a_duplicate_notice(): void
    {
        $support = $this->supportUser();
        $assignee = $this->supportUser();
        $requester = $this->user();
        $ticket = ItSupportTicket::factory()->create([
            'requester_id' => $requester->id,
            'assignee_id' => $assignee->id,
        ]);

        $this->actingAs($support)->patchJson("/api/v1/it-support/tickets/{$ticket->id}/assignee", [
            'assignee_id' => $assignee->id,
        ])->assertOk();

        $this->assertDatabaseCount('user_notifications', 0);
    }

    public function test_volume_report_is_grouped_and_denied_to_non_support_requester(): void
    {
        $support = $this->supportUser();
        $requester = $this->user();
        ItSupportTicket::factory()->create(['status' => 'open', 'category' => 'access', 'priority' => 'high']);

        $this->actingAs($support)->getJson('/api/v1/it-support/reports/volume')->assertOk()
            ->assertJsonStructure(['data' => ['status', 'category', 'priority']]);
        $this->actingAs($requester)->getJson('/api/v1/it-support/reports/volume')->assertForbidden();
    }
}
