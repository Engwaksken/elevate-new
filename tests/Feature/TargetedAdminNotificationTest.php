<?php

namespace Tests\Feature;

use App\Mail\SystemMail;
use App\Models\Role;
use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class TargetedAdminNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_notification_only_reaches_selected_users_and_role_members(): void
    {
        Mail::fake();
        $admin = User::factory()->create(['user_type' => 'staff', 'status' => 'active']);
        $admin->roles()->attach(Role::firstOrCreate(
            ['slug' => 'super-administrator'],
            ['name' => 'Super Administrator', 'is_system' => true]
        ));
        $selectedUser = User::factory()->create(['user_type' => 'participant', 'status' => 'active']);
        $roleMember = User::factory()->create(['user_type' => 'staff', 'status' => 'active']);
        $unselected = User::factory()->create(['user_type' => 'participant', 'status' => 'active']);
        $role = Role::create(['name' => 'Regional Coaches', 'slug' => 'regional-coaches']);
        $roleMember->roles()->attach($role);

        $this->actingAs($admin)->post(route('admin.notifications.send'), [
            'title' => 'Schedule update',
            'message' => 'Please review the updated schedule.',
            'user_ids' => [$selectedUser->id],
            'role_ids' => [$role->id],
        ])->assertRedirect(route('admin.notifications.index'))
            ->assertSessionHas('success', 'Notification sent to 2 selected user(s).');

        $this->assertEqualsCanonicalizing(
            [$selectedUser->id, $roleMember->id],
            UserNotification::where('title', 'Schedule update')->pluck('user_id')->all()
        );
        UserNotification::where('title', 'Schedule update')->get()->each(function (UserNotification $notification): void {
            $this->assertArrayNotHasKey('recipient_user_ids', $notification->data ?? []);
        });
        $this->assertSame(0, UserNotification::where('user_id', $unselected->id)->count());
        Mail::assertSent(SystemMail::class, 2);
    }

    public function test_admin_notification_requires_an_explicit_user_or_role_target(): void
    {
        $admin = User::factory()->create(['user_type' => 'staff', 'status' => 'active']);
        $admin->roles()->attach(Role::firstOrCreate(
            ['slug' => 'super-administrator'],
            ['name' => 'Super Administrator', 'is_system' => true]
        ));

        $this->actingAs($admin)->post(route('admin.notifications.send'), [
            'title' => 'Schedule update',
            'message' => 'Please review the updated schedule.',
        ])->assertSessionHasErrors('recipients');

        $this->assertDatabaseMissing('user_notifications', ['title' => 'Schedule update']);
    }

    public function test_admin_can_mark_selected_notifications_read_across_recipients(): void
    {
        $admin = User::factory()->create(['user_type' => 'staff', 'status' => 'active']);
        $admin->roles()->attach(Role::firstOrCreate(
            ['slug' => 'super-administrator'],
            ['name' => 'Super Administrator', 'is_system' => true]
        ));
        $firstRecipient = User::factory()->create(['user_type' => 'participant']);
        $secondRecipient = User::factory()->create(['user_type' => 'participant']);
        $first = UserNotification::create(['user_id' => $firstRecipient->id, 'type' => 'general', 'title' => 'First']);
        $second = UserNotification::create(['user_id' => $secondRecipient->id, 'type' => 'general', 'title' => 'Second']);
        $untouched = UserNotification::create(['user_id' => $secondRecipient->id, 'type' => 'general', 'title' => 'Untouched']);

        $this->actingAs($admin)->post(route('admin.notifications.bulk-action'), [
            'action' => 'mark_read', 'ids' => [$first->id, $second->id],
        ])->assertRedirect(route('admin.notifications.index'))
            ->assertSessionHas('success', 'Marked 2 notification(s) as read.');

        $this->assertNotNull($first->fresh()->read_at);
        $this->assertNotNull($second->fresh()->read_at);
        $this->assertNull($untouched->fresh()->read_at);
    }

    public function test_only_administrators_can_delete_notifications(): void
    {
        $admin = User::factory()->create(['user_type' => 'staff', 'status' => 'active']);
        $admin->roles()->attach(Role::firstOrCreate(['slug' => 'super-administrator'], ['name' => 'Super Administrator', 'is_system' => true]));
        $recipient = User::factory()->create(['user_type' => 'participant']);
        $notification = UserNotification::create(['user_id' => $recipient->id, 'type' => 'general', 'title' => 'Delete me']);

        $this->actingAs($admin)->post(route('admin.notifications.bulk-action'), [
            'action' => 'delete', 'ids' => [$notification->id],
        ])->assertRedirect(route('admin.notifications.index'));

        $this->assertDatabaseMissing('user_notifications', ['id' => $notification->id]);
    }
}
