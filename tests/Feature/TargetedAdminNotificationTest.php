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
}
