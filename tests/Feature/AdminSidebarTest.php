<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminSidebarTest extends TestCase
{
    use RefreshDatabase;

    private function staff(): User
    {
        return User::factory()->create(['user_type' => 'staff', 'status' => 'active', 'email_verified_at' => now()]);
    }

    public function test_admin_sidebar_is_grouped_by_task_with_search_and_my_work_open(): void
    {
        $admin = $this->staff();
        $admin->roles()->attach(Role::firstOrCreate(['slug' => 'super-administrator'], ['name' => 'Super Administrator']));

        $html = $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk()
            ->assertSee('data-admin-sidebar-search', false)
            ->assertSeeInOrder(['Home', 'My Work', 'Approvals &amp; Queues', 'Programmes &amp; Learning', 'Participants', 'Planning &amp; Reports', 'HR &amp; People', 'Finance &amp; Operations', 'System Administration', 'Account'], false)
            ->getContent();

        // HR items sit together; user management moved to System Administration.
        $this->assertMatchesRegularExpression('/HR &amp; People.*Recruitment.*Employee Directory.*Finance &amp; Operations/s', $html);
        $this->assertMatchesRegularExpression('/System Administration.*title="Users".*title="Roles &amp; Permissions"/s', $html);

        // My Work is open by default; other groups without the current page stay closed.
        $this->assertMatchesRegularExpression('/id="admin-sidebar-group-my-work" class="eh-admin-sidebar__links" >/', $html);
        $this->assertMatchesRegularExpression('/id="admin-sidebar-group-finance-operations" class="eh-admin-sidebar__links"\s+hidden/', $html);

        // Searchable by everyday words, not just the page name.
        $this->assertStringContainsString('holiday time off', $html);
    }

    public function test_plain_staff_see_only_their_own_groups(): void
    {
        $this->actingAs($this->staff())->get(route('staff.hr.index'))->assertOk()
            ->assertSee('My Work')->assertSee('title="My Leave"', false)->assertSee('title="My HR"', false)
            ->assertDontSee('System Administration')->assertDontSee('HR &amp; People', false)->assertDontSee('title="Users"', false);
    }
}
