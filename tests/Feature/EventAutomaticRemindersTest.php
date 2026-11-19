<?php

namespace Tests\Feature;

use App\Mail\SystemMail;
use App\Models\Event;
use App\Models\EventInvitation;
use App\Models\EventRegistration;
use App\Models\EventReminder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class EventAutomaticRemindersTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
    }

    private function user(string $name, string $type): User
    {
        return User::factory()->create(['name' => $name, 'user_type' => $type, 'status' => 'active', 'email_verified_at' => now()]);
    }

    private function event(array $attributes = []): Event
    {
        return Event::create($attributes + [
            'title' => 'Career Fair', 'event_type' => 'career_fair', 'starts_at' => now()->addHours(20),
            'delivery_mode' => 'physical', 'venue' => 'Innovation Hub', 'is_published' => true, 'registration_required' => true,
        ]);
    }

    public function test_new_events_get_default_one_day_and_one_hour_reminders(): void
    {
        $this->actingAs($this->user('Grace Staff', 'staff'))->post(route('admin.events.store'), [
            'title' => 'Workshop', 'event_type' => 'workshop', 'starts_at' => now()->addWeek()->format('Y-m-d H:i'), 'delivery_mode' => 'physical',
        ])->assertSessionHasNoErrors();

        $this->assertEqualsCanonicalizing([1440, 60], EventReminder::pluck('minutes_before')->all());
        $this->get(route('admin.events.view', Event::firstOrFail()))->assertOk()->assertSee('Automatic Reminders')->assertSee('1 day before');
    }

    public function test_due_reminder_reaches_registered_and_invited_people_once(): void
    {
        $staff = $this->user('Grace Staff', 'staff');
        $registered = $this->user('Amina', 'participant');
        $invitedParticipant = $this->user('Naki', 'participant');
        $invitedStaff = $this->user('Peter Staff', 'staff');
        $stranger = $this->user('Joan', 'participant');

        $event = $this->event();
        EventRegistration::create(['event_id' => $event->id, 'user_id' => $registered->id, 'status' => 'registered', 'registered_at' => now()->subDays(3)]);
        foreach ([$invitedParticipant, $invitedStaff] as $person) {
            EventInvitation::create(['event_id' => $event->id, 'user_id' => $person->id, 'invited_by' => $staff->id]);
        }
        EventInvitation::query()->update(['created_at' => now()->subDays(2)]);
        app(\App\Services\EventReminderService::class)->ensureDefaults($event);

        // 20 hours out: the 1-day reminder is due (even though its exact minute passed), the 1-hour one is not.
        $this->artisan('events:send-reminders')->assertSuccessful();

        foreach ([$registered, $invitedParticipant, $invitedStaff] as $person) {
            $this->assertDatabaseHas('user_notifications', ['user_id' => $person->id, 'type' => 'event_reminder']);
            Mail::assertSent(SystemMail::class, fn ($mail) => $mail->hasTo($person->email));
        }
        $this->assertDatabaseMissing('user_notifications', ['user_id' => $stranger->id, 'type' => 'event_reminder']);
        $this->assertDatabaseHas('user_notifications', ['user_id' => $invitedStaff->id, 'action_url' => route('admin.events.view', $event)]);
        $this->assertStringContainsString('have not registered yet',
            \App\Models\UserNotification::where('user_id', $invitedParticipant->id)->value('message'));
        $this->assertStringNotContainsString('have not registered yet',
            \App\Models\UserNotification::where('user_id', $registered->id)->value('message'));

        // Running again sends nothing new.
        $this->artisan('events:send-reminders')->assertSuccessful();
        $this->assertSame(3, \App\Models\UserNotification::where('type', 'event_reminder')->count());
    }

    public function test_only_the_most_imminent_reminder_is_sent_and_late_joiners_skip_earlier_ones(): void
    {
        $early = $this->user('Amina', 'participant');
        $late = $this->user('Naki', 'participant');
        $event = $this->event(['starts_at' => now()->addMinutes(30)]);
        app(\App\Services\EventReminderService::class)->ensureDefaults($event);
        EventRegistration::create(['event_id' => $event->id, 'user_id' => $early->id, 'status' => 'registered', 'registered_at' => now()->subDays(2)]);
        // Registered 10 minutes ago, after the 1-hour reminder fell due.
        EventRegistration::create(['event_id' => $event->id, 'user_id' => $late->id, 'status' => 'registered', 'registered_at' => now()->subMinutes(10)]);

        $this->artisan('events:send-reminders')->assertSuccessful();

        $notices = \App\Models\UserNotification::where('type', 'event_reminder')->get();
        $this->assertCount(1, $notices);
        $this->assertSame($early->id, (int) $notices->first()->user_id);
        $this->assertSame(EventReminder::where('minutes_before', 60)->value('id'), $notices->first()->data['reminder_id']);
    }

    public function test_unpublished_events_remind_invitees_and_past_events_are_skipped(): void
    {
        $invitee = $this->user('Peter Staff', 'staff');
        $draft = $this->event(['is_published' => false]);
        EventInvitation::create(['event_id' => $draft->id, 'user_id' => $invitee->id]);
        EventInvitation::query()->update(['created_at' => now()->subDays(2)]);
        $past = $this->event(['title' => 'Old', 'starts_at' => now()->subHour()]);
        EventRegistration::create(['event_id' => $past->id, 'user_id' => $this->user('Amina', 'participant')->id, 'status' => 'registered', 'registered_at' => now()->subDays(2)]);
        foreach ([$draft, $past] as $event) {
            app(\App\Services\EventReminderService::class)->ensureDefaults($event);
        }

        $this->artisan('events:send-reminders')->assertSuccessful();

        $this->assertSame(1, \App\Models\UserNotification::where('type', 'event_reminder')->count());
        $this->assertDatabaseHas('user_notifications', ['user_id' => $invitee->id, 'type' => 'event_reminder']);
    }

    public function test_staff_can_add_and_remove_reminders_from_the_event_page(): void
    {
        $event = $this->event();
        $this->actingAs($this->user('Grace Staff', 'staff'))
            ->post(route('admin.events.reminders.store', $event), ['preset' => 'custom', 'custom_value' => 30, 'custom_unit' => 'minutes', 'delivery_method' => 'system'])
            ->assertRedirect(route('admin.events.view', $event).'#settings');
        $reminder = EventReminder::where('minutes_before', 30)->firstOrFail();

        $this->get(route('admin.events.view', $event))->assertSee('30 minutes before');
        $this->delete(route('admin.events.reminders.destroy', [$event, $reminder]))->assertRedirect();
        $this->assertDatabaseMissing('event_reminders', ['id' => $reminder->id]);
    }
}
