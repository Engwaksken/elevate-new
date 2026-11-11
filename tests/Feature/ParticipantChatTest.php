<?php

namespace Tests\Feature;

use App\Models\ParticipantConversation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ParticipantChatTest extends TestCase
{
    use RefreshDatabase;

    private function participant(string $name): User
    {
        return User::factory()->create([
            'name' => $name,
            'user_type' => 'participant',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);
    }

    public function test_participant_can_start_direct_chat_and_send_a_message(): void
    {
        $sender = $this->participant('Amina');
        $recipient = $this->participant('Naki');

        $response = $this->actingAs($sender)->post(route('participant.chat.store'), [
            'type' => 'direct', 'participant_id' => $recipient->id,
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseCount('participant_conversations', 1);
        $conversation = ParticipantConversation::firstOrFail();
        $response->assertRedirect(route('participant.chat.show', $conversation));
        $this->assertCount(2, $conversation->members);

        $this->post(route('participant.chat.messages.store', $conversation), ['body' => 'Hello there'])
            ->assertRedirect(route('participant.chat.show', $conversation).'#latest');

        $this->assertDatabaseHas('participant_messages', [
            'conversation_id' => $conversation->id,
            'sender_id' => $sender->id,
            'body' => 'Hello there',
        ]);
        $this->getJson(route('participant.chat.messages.index', $conversation).'?after=0')
            ->assertOk()->assertJsonPath('messages.0.body', 'Hello there');
    }

    public function test_participant_can_create_group_and_invite_members_later(): void
    {
        $creator = $this->participant('Amina');
        $invitee = $this->participant('Naki');
        $another = $this->participant('Joan');

        $this->actingAs($creator)->post(route('participant.chat.store'), [
            'type' => 'group', 'title' => 'Study group', 'participant_ids' => [$invitee->id],
        ])->assertRedirect();

        $conversation = ParticipantConversation::firstOrFail();
        $this->assertSame('Study group', $conversation->title);
        $this->post(route('participant.chat.invite', $conversation), ['participant_ids' => [$another->id]])
            ->assertSessionHasNoErrors();
        $this->assertDatabaseHas('participant_conversation_user', [
            'conversation_id' => $conversation->id,
            'user_id' => $another->id,
        ]);
    }

    public function test_non_member_cannot_read_or_send_to_conversation(): void
    {
        $first = $this->participant('Amina');
        $second = $this->participant('Naki');
        $outsider = $this->participant('Joan');
        $conversation = ParticipantConversation::create(['type' => 'direct', 'created_by' => $first->id]);
        $conversation->members()->attach([$first->id, $second->id]);

        $this->actingAs($outsider)->get(route('participant.chat.show', $conversation))->assertForbidden();
        $this->post(route('participant.chat.messages.store', $conversation), ['body' => 'Intrusion'])->assertForbidden();
    }

    public function test_participant_can_open_the_chat_page(): void
    {
        $this->actingAs($this->participant('Amina'))
            ->get(route('participant.chat.index'))
            ->assertOk()
            ->assertSee('Participant Chat');
    }

    public function test_dashboard_links_to_chat_and_lists_recent_conversations(): void
    {
        $user = $this->participant('Amina');
        $friend = $this->participant('Naki');
        $conversation = ParticipantConversation::create(['type' => 'direct', 'created_by' => $user->id]);
        $conversation->members()->attach([$user->id, $friend->id]);
        $conversation->messages()->create(['sender_id' => $friend->id, 'body' => 'See you at the workshop']);

        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('data-eh-tab="chat"', false)
            ->assertSee(route('participant.chat.index'), false)
            ->assertSee('Naki')
            ->assertSee('See you at the workshop');
    }

    public function test_inactive_and_non_participant_accounts_cannot_be_invited(): void
    {
        $creator = $this->participant('Amina');
        $inactive = User::factory()->create(['user_type' => 'participant', 'status' => 'inactive']);
        $staff = User::factory()->create(['user_type' => 'staff', 'status' => 'active']);

        $this->actingAs($creator)->from(route('participant.chat.index'))->post(route('participant.chat.store'), [
            'type' => 'group', 'title' => 'Study group', 'participant_ids' => [$inactive->id],
        ])->assertSessionHasErrors('participant_id');

        $this->post(route('participant.chat.store'), [
            'type' => 'direct', 'participant_id' => $staff->id,
        ])->assertSessionHasErrors('participant_id');

        $this->assertDatabaseCount('participant_conversations', 0);
    }
}
