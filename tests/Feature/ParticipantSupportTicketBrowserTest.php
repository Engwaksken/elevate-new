<?php

namespace Tests\Feature;

use App\Models\ItSupportTicket;
use App\Models\Role;
use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ParticipantSupportTicketBrowserTest extends TestCase
{
    use RefreshDatabase;

    private function participant(): User
    {
        return User::factory()->create([
            'user_type' => 'participant', 'status' => 'active', 'email_verified_at' => now(),
        ]);
    }

    public function test_browser_list_contains_only_the_authenticated_requesters_tickets(): void
    {
        $owner = $this->participant();
        $other = $this->participant();
        $ownTicket = ItSupportTicket::factory()->create(['requester_id' => $owner->id]);
        $otherTicket = ItSupportTicket::factory()->create(['requester_id' => $other->id]);

        $this->actingAs($owner)->get('/support/tickets')
            ->assertOk()->assertViewIs('participant.support-tickets.index')
            ->assertSee($ownTicket->subject)->assertDontSee($otherTicket->subject);
    }

    public function test_browser_ticket_detail_is_available_to_owner_and_hidden_from_another_user(): void
    {
        $owner = $this->participant();
        $other = $this->participant();
        $ticket = ItSupportTicket::factory()->create(['requester_id' => $owner->id]);

        $this->actingAs($owner)->get(route('participant.support-tickets.show', $ticket))->assertOk();
        $this->actingAs($other)->get(route('participant.support-tickets.show', $ticket))->assertForbidden();
    }

    public function test_browser_ticket_pages_require_a_session_user(): void
    {
        $ticket = ItSupportTicket::factory()->create();

        $this->get(route('participant.support-tickets.index'))->assertRedirect('/login');
        $this->get(route('participant.support-tickets.show', $ticket))->assertRedirect('/login');
    }

    public function test_browser_submission_audits_ticket_and_keeps_notification_confidential(): void
    {
        $requester = $this->participant();
        $supportRole = Role::firstOrCreate(['slug' => 'it-lead'], ['name' => 'IT Lead']);
        $support = User::factory()->create(['user_type' => 'staff', 'status' => 'active']);
        $support->roles()->attach($supportRole);
        $subject = 'Private browser ticket subject';
        $description = 'Private browser ticket details';

        $this->actingAs($requester)->from('/support')->post(route('participant.support-tickets.store'), [
            'subject' => $subject, 'description' => $description, 'category' => 'access',
        ])->assertRedirect('/support');

        $ticket = ItSupportTicket::query()->where('requester_id', $requester->id)->sole();
        $this->assertDatabaseHas('audit_logs', ['auditable_type' => ItSupportTicket::class, 'auditable_id' => $ticket->id, 'action' => 'created']);
        $notice = UserNotification::query()->where('user_id', $support->id)->sole();
        $notificationText = $notice->title.' '.$notice->message.' '.json_encode($notice->data);
        $this->assertStringNotContainsString($subject, $notificationText);
        $this->assertStringNotContainsString($description, $notificationText);
    }
}
