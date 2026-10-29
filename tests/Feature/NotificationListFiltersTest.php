<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class NotificationListFiltersTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-29 12:00:00'); // a Thursday

        $this->user = User::factory()->create(['user_type' => 'participant', 'status' => 'active', 'email_verified_at' => now()]);

        $this->notify('Today alert', now());
        $this->notify('Earlier this month', Carbon::parse('2026-10-05 09:00'));
        $this->notify('Last year reminder', Carbon::parse('2025-06-01 09:00'), read: true);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function notify(string $title, Carbon $at, bool $read = false): void
    {
        $notification = UserNotification::create(['user_id' => $this->user->id, 'type' => 'general', 'title' => $title, 'message' => $title.' details']);
        $notification->forceFill(['created_at' => $at, 'read_at' => $read ? $at : null])->save();
    }

    public function test_statistics_cards_show_week_month_and_total_counts(): void
    {
        $this->actingAs($this->user)->get(route('notifications.index'))
            ->assertOk()
            ->assertViewHas('stats', ['week' => 1, 'month' => 2, 'total' => 3])
            ->assertViewHas('unreadCount', 2)
            ->assertSee('This Week')
            ->assertSee('This Month');
    }

    public function test_period_filters_limit_the_list(): void
    {
        $this->actingAs($this->user)->get(route('notifications.index', ['period' => 'week']))
            ->assertSee('Today alert')->assertDontSee('Earlier this month')->assertDontSee('Last year reminder');

        $this->actingAs($this->user)->get(route('notifications.index', ['period' => 'month']))
            ->assertSee('Earlier this month')->assertDontSee('Last year reminder');

        $this->actingAs($this->user)->get(route('notifications.index', ['period' => 'custom', 'from' => '2025-01-01', 'to' => '2025-12-31']))
            ->assertSee('Last year reminder')->assertDontSee('Today alert');
    }

    public function test_search_and_pagination(): void
    {
        $this->actingAs($this->user)->get(route('notifications.index', ['search' => 'reminder']))
            ->assertSee('Last year reminder')->assertDontSee('Today alert');

        $response = $this->actingAs($this->user)->get(route('notifications.index', ['per_page' => 10]))->assertOk();
        $this->assertSame(10, $response->viewData('notifications')->perPage());

        $response = $this->actingAs($this->user)->get(route('notifications.index', ['per_page' => 999]))->assertOk();
        $this->assertSame(15, $response->viewData('notifications')->perPage());
    }
}
