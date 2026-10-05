<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InstructorDashboardTabsTest extends TestCase
{
    use RefreshDatabase;

    public function test_instructor_dashboard_renders_tabs_and_panels_with_my_courses_selected_by_default(): void
    {
        $instructor = User::factory()->create(['user_type' => 'staff', 'status' => 'active']);
        $role = Role::firstOrCreate(['slug' => 'instructor'], ['name' => 'Instructor']);
        $instructor->roles()->attach($role);

        $html = $this->actingAs($instructor)
            ->get('/admin/my-courses')
            ->assertOk()
            ->assertSee('id="mc-tab-courses"', false)
            ->assertSee('My Courses')
            ->assertSee('id="mc-tab-overview"', false)
            ->assertSee('Overview')
            ->assertSee('id="mc-panel-courses"', false)
            ->assertSee('id="mc-panel-overview"', false)
            ->getContent();

        $this->assertMatchesRegularExpression('/id="mc-tab-courses"[^>]*aria-selected="true"/', $html);
        $this->assertMatchesRegularExpression('/id="mc-tab-overview"[^>]*aria-selected="false"/', $html);
        preg_match('/<section[^>]*id="mc-panel-courses"[^>]*>/', $html, $coursesPanel);
        preg_match('/<section[^>]*id="mc-panel-overview"[^>]*>/', $html, $overviewPanel);
        $this->assertStringNotContainsString(' hidden', $coursesPanel[0]);
        $this->assertStringContainsString(' hidden', $overviewPanel[0]);
    }
}
