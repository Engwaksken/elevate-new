<?php

namespace Tests\Feature;

use App\Models\LoginAudit;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ParticipantSignInHistoryTest extends TestCase
{
    use RefreshDatabase;

    private function administrator(): User
    {
        $user = User::factory()->create(['user_type' => 'staff', 'status' => 'active']);
        $user->roles()->attach(Role::firstOrCreate(
            ['slug' => 'super-administrator'],
            ['name' => 'Super Administrator']
        ));

        return $user;
    }

    private function participant(array $attributes = []): User
    {
        return User::factory()->create(array_merge([
            'user_type' => 'participant',
            'status' => 'active',
            'password' => Hash::make('Secret-pass1'),
        ], $attributes));
    }

    public function test_every_web_sign_in_is_recorded(): void
    {
        $participant = $this->participant();

        foreach (range(1, 2) as $_) {
            $this->post(route('login.attempt'), ['email' => $participant->email, 'password' => 'Secret-pass1'])
                ->assertRedirect();
            $this->post(route('logout'));
        }

        $this->assertSame(2, LoginAudit::where('user_id', $participant->id)->where('successful', true)->where('channel', 'web')->count());
        $this->assertNotNull($participant->fresh()->last_login_at);
    }

    public function test_a_failed_sign_in_is_not_recorded_as_a_sign_in(): void
    {
        $participant = $this->participant();

        $this->post(route('login.attempt'), ['email' => $participant->email, 'password' => 'wrong-password']);

        $this->assertSame(0, LoginAudit::where('user_id', $participant->id)->count());
    }

    public function test_mobile_app_sign_in_is_recorded_as_app(): void
    {
        // The API login itself needs Sanctum's token table, which the test database
        // does not create; it calls the same recorder with the "app" channel.
        $participant = $this->participant();

        app(\App\Services\SignInRecorder::class)->record($participant, \Illuminate\Http\Request::create('/api/v1/participant/login', 'POST'), 'app');

        $this->assertDatabaseHas('login_audits', ['user_id' => $participant->id, 'channel' => 'app', 'successful' => true]);
    }

    public function test_participants_page_has_tabs_and_lists_sign_ins(): void
    {
        $admin = $this->administrator();
        $amina = $this->participant(['name' => 'Amina Sign-in']);
        $other = $this->participant(['name' => 'Grace Other']);

        LoginAudit::create(['user_id' => $amina->id, 'email' => $amina->email, 'successful' => true, 'channel' => 'app', 'attempted_at' => now()->subHour()]);
        LoginAudit::create(['user_id' => $other->id, 'email' => $other->email, 'successful' => true, 'channel' => 'web', 'user_agent' => 'Mozilla/5.0 (Windows NT 10.0) Chrome/120.0', 'attempted_at' => now()->subDays(3)]);
        LoginAudit::create(['user_id' => $admin->id, 'email' => $admin->email, 'successful' => true, 'channel' => 'web', 'attempted_at' => now()]);

        // The default tab is the participant list.
        $this->actingAs($admin)->get(route('admin.participants.index'))
            ->assertOk()
            ->assertSee('Participant activity')
            ->assertSee('Sign-ins')
            ->assertSee('Amina Sign-in')
            ->assertDontSee('data-participant-activity-chart', false);

        $page = $this->actingAs($admin)->get(route('admin.participants.index', ['tab' => 'signins']))
            ->assertOk()
            ->assertSee('Amina Sign-in')
            ->assertSee('Grace Other')
            ->assertSee('Mobile app')
            ->assertSee('Chrome on Windows');

        // Staff sign-ins are not listed.
        $this->assertSame([$amina->id, $other->id], $page->viewData('signIns')['items']->pluck('user_id')->all());

        $this->assertSame(1, $page->viewData('signIns')['today']);
        $this->assertSame(2, $page->viewData('signIns')['week']);

        $this->actingAs($admin)->get(route('admin.participants.index', ['tab' => 'signins', 'channel' => 'app']))
            ->assertOk()
            ->assertSee('Amina Sign-in')
            ->assertDontSee('Grace Other');

        $this->actingAs($admin)->get(route('admin.participants.index', ['tab' => 'signins', 'signin_search' => 'Grace']))
            ->assertOk()
            ->assertSee('Grace Other')
            ->assertDontSee('Amina Sign-in');
    }

    public function test_activity_counts_sign_ins(): void
    {
        $participant = $this->participant();
        LoginAudit::create(['user_id' => $participant->id, 'email' => $participant->email, 'successful' => true, 'channel' => 'web', 'attempted_at' => now()]);

        $activity = $this->actingAs($this->administrator())
            ->get(route('admin.participants.index', ['tab' => 'activity']))
            ->assertOk()
            ->viewData('activity');

        $this->assertSame(1, $activity['totals']['sign_ins']);
        $this->assertSame(1, $activity['totals']['active_participants']);
        $this->assertSame(1, collect($activity['series'])->last()['active']);
    }
}
