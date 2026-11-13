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

    public function test_chat_list_offers_modal_crud_and_creator_can_edit_or_delete_group(): void
    {
        $creator = $this->participant('Amina');
        $member = $this->participant('Naki');
        $outsider = $this->participant('Joan');
        $conversation = ParticipantConversation::create(['type' => 'group', 'title' => 'Original group', 'created_by' => $creator->id]);
        $conversation->members()->attach([$creator->id, $member->id]);

        $this->actingAs($creator)->get(route('participant.chat.index'))
            ->assertOk()->assertSee('data-modal-open="chat-create"', false)
            ->assertSee('id="chat-edit-'.$conversation->id.'"', false)
            ->assertSee('id="chat-delete-'.$conversation->id.'"', false);

        $this->put(route('participant.chat.update', $conversation), ['title' => 'Updated group'])
            ->assertSessionHasNoErrors();
        $this->assertSame('Updated group', $conversation->fresh()->title);
        $this->actingAs($outsider)->put(route('participant.chat.update', $conversation), ['title' => 'Hijacked'])
            ->assertForbidden();
        $this->actingAs($creator)->delete(route('participant.chat.destroy', $conversation))
            ->assertRedirect(route('participant.chat.index'));
        $this->assertDatabaseMissing('participant_conversations', ['id' => $conversation->id]);
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

    public function test_sender_can_edit_and_delete_only_their_own_messages(): void
    {
        $sender = $this->participant('Amina');
        $member = $this->participant('Naki');
        $conversation = ParticipantConversation::create(['type' => 'direct', 'created_by' => $sender->id]);
        $conversation->members()->attach([$sender->id, $member->id]);
        $message = $conversation->messages()->create(['sender_id' => $sender->id, 'body' => 'Original']);

        $this->actingAs($sender)->put(route('participant.chat.messages.update', [$conversation, $message]), ['body' => 'Updated'])
            ->assertRedirect(route('participant.chat.show', $conversation).'#latest');
        $this->assertDatabaseHas('participant_messages', ['id' => $message->id, 'body' => 'Updated']);

        $this->actingAs($member)->put(route('participant.chat.messages.update', [$conversation, $message]), ['body' => 'Hijacked'])
            ->assertForbidden();
        $this->actingAs($sender)->delete(route('participant.chat.messages.destroy', [$conversation, $message]))
            ->assertRedirect(route('participant.chat.show', $conversation).'#latest');
        $this->assertDatabaseMissing('participant_messages', ['id' => $message->id]);
    }

    public function test_thread_page_shows_sidebar_and_supports_inline_send_edit_delete(): void
    {
        $me = $this->participant('Amina Kato');
        $friend = $this->participant('Naki Ruth');
        $third = $this->participant('Joan Akello');
        $conversation = ParticipantConversation::create(['type' => 'direct', 'created_by' => $me->id]);
        $conversation->members()->attach([$me->id, $friend->id]);
        $other = ParticipantConversation::create(['type' => 'group', 'title' => 'Study circle', 'created_by' => $third->id]);
        $other->members()->attach([$me->id, $third->id]);
        $conversation->messages()->create(['sender_id' => $friend->id, 'body' => 'Morning!']);

        $this->actingAs($me)->get(route('participant.chat.show', $conversation))
            ->assertOk()
            ->assertSee('Study circle')            // sidebar lists the other conversation
            ->assertSee('aria-current="page"', false)
            ->assertSee('data-ehc-compose', false)
            ->assertSee('Morning!')
            ->assertSee('NR');                      // initials avatar

        $sent = $this->postJson(route('participant.chat.messages.store', $conversation), ['body' => 'Hi Naki'])
            ->assertCreated()
            ->assertJsonPath('message.body', 'Hi Naki')
            ->assertJsonPath('message.mine', true)
            ->json('message');
        $this->assertNotNull($sent['update_url']);

        $this->putJson($sent['update_url'], ['body' => 'Hi Naki!'])
            ->assertOk()->assertJsonPath('message.body', 'Hi Naki!');
        $this->deleteJson($sent['delete_url'])->assertOk()->assertJsonPath('deleted', $sent['id']);
        $this->assertDatabaseMissing('participant_messages', ['id' => $sent['id']]);

        // Polled messages from someone else are not editable by the viewer.
        $this->getJson(route('participant.chat.messages.index', $conversation).'?after=0')
            ->assertOk()->assertJsonPath('messages.0.mine', false)->assertJsonPath('messages.0.update_url', null);
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
