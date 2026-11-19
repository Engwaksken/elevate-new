<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Enrolment;
use App\Models\Event;
use App\Models\EventInvitation;
use App\Models\EventRegistration;
use App\Models\ParticipantConversation;
use App\Models\ParticipantMessageAttachment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StaffChatAndEventInvitationsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        Storage::fake('local');
    }

    private function user(string $name, string $type): User
    {
        return User::factory()->create([
            'name' => $name, 'user_type' => $type, 'status' => 'active', 'email_verified_at' => now(),
        ]);
    }

    /** An instructor teaching one course with one enrolled participant. */
    private function course(User $instructor, User $participant): Course
    {
        $course = Course::create(['title' => 'Web Basics', 'status' => 'published']);
        $course->instructors()->attach($instructor->id);
        Enrolment::create(['course_id' => $course->id, 'user_id' => $participant->id, 'status' => 'enrolled']);

        return $course;
    }

    public function test_staff_can_open_chat_and_start_a_group_with_colleagues(): void
    {
        $me = $this->user('Grace Staff', 'staff');
        $colleague = $this->user('Peter Staff', 'staff');
        $another = $this->user('Ruth Staff', 'staff');

        $this->actingAs($me)->get(route('admin.chat.index'))->assertOk()->assertSee('Staff Chat')->assertSee('data-people-picker', false);

        $this->post(route('admin.chat.store'), ['type' => 'group', 'title' => 'Programme team', 'participant_ids' => [$colleague->id]])
            ->assertSessionHasNoErrors();
        $conversation = ParticipantConversation::firstOrFail();
        $this->get(route('admin.chat.show', $conversation))->assertOk()->assertSee('Programme team');

        $this->post(route('admin.chat.invite', $conversation), ['participant_ids' => [$another->id]])->assertSessionHasNoErrors();
        $this->assertTrue($conversation->members()->where('users.id', $another->id)->exists());
        $this->assertDatabaseHas('user_notifications', ['user_id' => $another->id, 'type' => 'chat_group_added']);

        // An invited member can leave the group; the creator cannot.
        $this->actingAs($another)->post(route('admin.chat.leave', $conversation))->assertRedirect(route('admin.chat.index'));
        $this->assertFalse($conversation->members()->where('users.id', $another->id)->exists());
        $this->actingAs($me)->post(route('admin.chat.leave', $conversation))->assertForbidden();
    }

    public function test_staff_without_courses_cannot_message_participants(): void
    {
        $staff = $this->user('Grace Staff', 'staff');
        $participant = $this->user('Amina', 'participant');

        $this->actingAs($staff)->post(route('admin.chat.store'), ['type' => 'direct', 'participant_id' => $participant->id])
            ->assertSessionHasErrors('participant_id');
        $this->getJson(route('admin.chat.people', ['q' => 'Amina']))->assertOk()->assertJsonCount(0, 'people');
    }

    public function test_instructor_can_group_their_course_participants_and_participants_can_reach_the_instructor(): void
    {
        $instructor = $this->user('Ivan Instructor', 'staff');
        $student = $this->user('Amina Student', 'participant');
        $outsider = $this->user('Joan Outsider', 'participant');
        $course = $this->course($instructor, $student);

        $this->actingAs($instructor)->getJson(route('admin.chat.people', ['group' => 'course-'.$course->id]))
            ->assertOk()->assertJsonCount(1, 'people')->assertJsonPath('people.0.id', $student->id);

        $this->post(route('admin.chat.store'), ['type' => 'group', 'title' => 'Web Basics class', 'participant_ids' => [$student->id]])
            ->assertSessionHasNoErrors();
        $this->post(route('admin.chat.store'), ['type' => 'group', 'title' => 'Wrong class', 'participant_ids' => [$outsider->id]])
            ->assertSessionHasErrors('participant_id');

        $group = ParticipantConversation::where('title', 'Web Basics class')->firstOrFail();
        $this->actingAs($student)->get(route('participant.chat.show', $group))->assertOk()->assertSee('Web Basics class');

        // The participant can start a direct chat with their own instructor, but not with other staff.
        $otherStaff = $this->user('Other Staff', 'staff');
        $this->post(route('participant.chat.store'), ['type' => 'direct', 'participant_id' => $instructor->id])->assertSessionHasNoErrors();
        $this->post(route('participant.chat.store'), ['type' => 'direct', 'participant_id' => $otherStaff->id])->assertSessionHasErrors('participant_id');
    }

    public function test_chat_messages_carry_attachments_only_members_can_download(): void
    {
        $me = $this->user('Amina', 'participant');
        $friend = $this->user('Naki', 'participant');
        $outsider = $this->user('Joan', 'participant');
        $conversation = ParticipantConversation::create(['type' => 'direct', 'created_by' => $me->id]);
        $conversation->members()->attach([$me->id, $friend->id]);

        $sent = $this->actingAs($me)->postJson(route('participant.chat.messages.store', $conversation), [
            'attachments' => [UploadedFile::fake()->create('notes.pdf', 120, 'application/pdf'), UploadedFile::fake()->image('photo.png')],
        ])->assertCreated()->assertJsonPath('message.body', '')->assertJsonCount(2, 'message.attachments')->json('message');

        $attachment = ParticipantMessageAttachment::where('original_name', 'notes.pdf')->firstOrFail();
        Storage::disk('local')->assertExists($attachment->path);
        $this->assertTrue(collect($sent['attachments'])->firstWhere('name', 'photo.png')['is_image']);

        $this->actingAs($friend)->get(route('participant.chat.attachments.show', [$conversation, $attachment]))
            ->assertOk()->assertDownload('notes.pdf');
        $this->actingAs($outsider)->get(route('participant.chat.attachments.show', [$conversation, $attachment]))->assertForbidden();

        // Disallowed types are rejected; an empty message without files is rejected.
        $this->actingAs($me)->postJson(route('participant.chat.messages.store', $conversation), [
            'attachments' => [UploadedFile::fake()->create('run.exe', 10)],
        ])->assertStatus(422);
        $this->postJson(route('participant.chat.messages.store', $conversation), ['body' => ''])->assertStatus(422);

        // Deleting the message removes its files.
        $this->deleteJson($sent['delete_url'])->assertOk();
        Storage::disk('local')->assertMissing($attachment->path);
        $this->assertDatabaseMissing('participant_message_attachments', ['id' => $attachment->id]);
    }

    public function test_staff_can_invite_people_when_creating_an_event_and_later(): void
    {
        $staff = $this->user('Grace Staff', 'staff');
        $colleague = $this->user('Peter Staff', 'staff');
        $participant = $this->user('Amina', 'participant');
        $later = $this->user('Naki', 'participant');

        $this->actingAs($staff)->post(route('admin.events.store'), [
            'title' => 'Draft Leadership Workshop', 'event_type' => 'workshop', 'starts_at' => now()->addWeek()->format('Y-m-d H:i'),
            'delivery_mode' => 'physical', 'invitee_ids' => [$colleague->id, $participant->id], 'invitation_note' => 'Please join us.',
        ])->assertSessionHasNoErrors();

        $event = Event::firstOrFail();
        $this->assertSame(2, $event->invitations()->count());
        $this->assertDatabaseHas('user_notifications', ['user_id' => $participant->id, 'type' => 'event_invitation', 'action_url' => route('events.show', $event)]);
        $this->assertDatabaseHas('user_notifications', ['user_id' => $colleague->id, 'type' => 'event_invitation', 'action_url' => route('admin.events.view', $event)]);

        // Invited participants can see and register for the unpublished event; others cannot.
        $this->actingAs($participant)->get(route('events.show', $event))->assertOk();
        $this->post(route('events.register', $event))->assertSessionHas('success');
        $this->assertSame('registered', EventInvitation::where('user_id', $participant->id)->value('status'));
        $this->actingAs($later)->get(route('events.show', $event))->assertNotFound();

        $this->actingAs($staff)->post(route('admin.events.invitations.store', $event), ['invitee_ids' => [$later->id, $participant->id]])
            ->assertRedirect(route('admin.events.view', $event).'#people');
        $this->assertSame(3, $event->invitations()->count());
        $this->assertSame(1, \App\Models\UserNotification::where('user_id', $participant->id)->where('type', 'event_invitation')->count());

        $this->get(route('admin.events.view', $event))->assertOk()->assertSee('People &amp; Appreciation', false)->assertSee('Naki');
    }

    public function test_appreciation_reaches_only_selected_people_connected_to_the_event(): void
    {
        $staff = $this->user('Grace Staff', 'staff');
        $attendee = $this->user('Amina', 'participant');
        $invitedStaff = $this->user('Peter Staff', 'staff');
        $stranger = $this->user('Joan', 'participant');
        $event = Event::create(['title' => 'Career Fair', 'event_type' => 'career_fair', 'starts_at' => now()->subDay(), 'delivery_mode' => 'physical', 'is_published' => true]);
        EventRegistration::create(['event_id' => $event->id, 'user_id' => $attendee->id, 'status' => 'registered', 'registered_at' => now()]);
        EventInvitation::create(['event_id' => $event->id, 'user_id' => $invitedStaff->id, 'invited_by' => $staff->id]);

        $this->actingAs($staff)->post(route('admin.events.appreciation.send', $event), [
            'user_ids' => [$attendee->id, $invitedStaff->id, $stranger->id],
            'title' => 'Thank you!', 'message' => 'We appreciate you.', 'send_email' => '1',
        ])->assertSessionHas('success', 'Appreciation message sent to 2 people.');

        $this->assertDatabaseHas('user_notifications', ['user_id' => $attendee->id, 'type' => 'event_appreciation', 'title' => 'Thank you!']);
        $this->assertDatabaseHas('user_notifications', ['user_id' => $invitedStaff->id, 'type' => 'event_appreciation']);
        $this->assertDatabaseMissing('user_notifications', ['user_id' => $stranger->id, 'type' => 'event_appreciation']);

        $this->post(route('admin.events.appreciation.send', $event), ['title' => 'x', 'message' => 'y'])->assertSessionHasErrors('user_ids');
    }
}
