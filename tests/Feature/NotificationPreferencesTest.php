<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\NotificationDispatcher;
use App\Services\UserNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationPreferencesTest extends TestCase
{
    use RefreshDatabase;

    private function participant(array $preferences = null): User
    {
        return User::factory()->create([
            'user_type' => 'participant',
            'status' => 'active',
            'email_verified_at' => now(),
            'notification_preferences' => $preferences,
        ]);
    }

    public function test_a_user_who_disables_a_category_receives_no_in_app_notification(): void
    {
        $user = $this->participant(['disabled' => ['jobs']]);

        app(NotificationDispatcher::class)->notify($user, 'job_application', 'Application', 'Message', null, [], false);

        $this->assertDatabaseCount('user_notifications', 0);
    }

    public function test_a_user_with_defaults_receives_notifications(): void
    {
        $user = $this->participant();

        app(NotificationDispatcher::class)->notify($user, 'job_application', 'Application', 'Message', null, [], false);

        $this->assertDatabaseHas('user_notifications', ['user_id' => $user->id, 'type' => 'job_application']);
    }

    public function test_bulk_notifications_skip_users_who_opted_out(): void
    {
        $enabled = $this->participant();
        $disabled = $this->participant(['disabled' => ['mentorship']]);

        app(UserNotificationService::class)->sendToMany([$enabled->id, $disabled->id], 'mentorship_session', 'Session', null, null, [], false);

        $this->assertDatabaseHas('user_notifications', ['user_id' => $enabled->id, 'type' => 'mentorship_session']);
        $this->assertDatabaseMissing('user_notifications', ['user_id' => $disabled->id, 'type' => 'mentorship_session']);
    }

    public function test_profile_update_saves_notification_preferences(): void
    {
        $user = $this->participant();

        $this->actingAs($user)->put(route('profile.update'), [
            'name' => $user->name,
            'email' => $user->email,
            'notifications' => ['learning', 'account'],
        ])->assertSessionHasNoErrors();

        $disabled = $user->fresh()->notification_preferences['disabled'];

        $this->assertNotContains('learning', $disabled);
        $this->assertNotContains('account', $disabled);
        $this->assertContains('jobs', $disabled);
        $this->assertContains('mentorship', $disabled);
    }
}
