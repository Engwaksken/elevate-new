<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\CourseModule;
use App\Models\Lesson;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AdminParticipantActivityTest extends TestCase
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

    private function lessons(int $count): array
    {
        $course = Course::create(['title' => 'Digital Skills', 'status' => 'published']);
        $module = CourseModule::create(['course_id' => $course->id, 'title' => 'Module', 'position' => 1, 'is_published' => true]);

        return collect(range(1, $count))->map(fn ($i) => Lesson::create([
            'course_module_id' => $module->id, 'title' => 'Lesson '.$i,
            'content_type' => 'text', 'position' => $i, 'is_published' => true,
        ]))->all();
    }

    public function test_participants_page_charts_daily_activity_and_sign_in_recency(): void
    {
        $this->travelTo(now()->setDate(2026, 11, 20)->setTime(12, 0));

        [$lessonA, $lessonB] = $this->lessons(2);
        $today = now()->toDateTimeString();
        $yesterday = now()->subDay()->toDateTimeString();

        $alice = User::factory()->create(['user_type' => 'participant', 'status' => 'active', 'last_login_at' => now()->subDays(2)]);
        $bob = User::factory()->create(['user_type' => 'participant', 'status' => 'active', 'last_login_at' => null]);
        $staff = User::factory()->create(['user_type' => 'staff', 'status' => 'active']);

        // Alice: started + completed a lesson today, started another yesterday. Bob: started one today.
        // Staff activity must not count.
        DB::table('lesson_progress')->insert([
            ['lesson_id' => $lessonA->id, 'user_id' => $alice->id, 'first_opened_at' => $today, 'completed_at' => $today, 'created_at' => $today, 'updated_at' => $today],
            ['lesson_id' => $lessonB->id, 'user_id' => $alice->id, 'first_opened_at' => $yesterday, 'completed_at' => null, 'created_at' => $yesterday, 'updated_at' => $yesterday],
            ['lesson_id' => $lessonA->id, 'user_id' => $bob->id, 'first_opened_at' => $today, 'completed_at' => null, 'created_at' => $today, 'updated_at' => $today],
            ['lesson_id' => $lessonB->id, 'user_id' => $staff->id, 'first_opened_at' => $today, 'completed_at' => $today, 'created_at' => $today, 'updated_at' => $today],
        ]);

        $response = $this->actingAs($this->administrator())
            ->get(route('admin.participants.index', ['tab' => 'activity', 'activity_days' => 7]))
            ->assertOk()
            ->assertSee('Participant activity')
            ->assertSee('data-participant-activity-chart', false)
            ->assertSee('Never signed in');

        $activity = $response->viewData('activity');

        $this->assertSame(7, $activity['days']);
        $this->assertCount(7, $activity['series']);

        $byDate = collect($activity['series'])->keyBy('date');
        $this->assertSame(2, $byDate[now()->toDateString()]['active']);
        $this->assertSame(1, $byDate[now()->subDay()->toDateString()]['active']);
        $this->assertSame(2, $byDate[now()->toDateString()]['registrations']);

        $this->assertSame(2, $activity['totals']['active_participants']);
        $this->assertSame(3, $activity['totals']['lessons_started']);
        $this->assertSame(1, $activity['totals']['lessons_completed']);

        $recency = collect($activity['recency'])->pluck('count', 'label');
        $this->assertSame(1, $recency['Last 7 days']);
        $this->assertSame(1, $recency['Never signed in']);
    }

    public function test_activity_range_falls_back_to_thirty_days(): void
    {
        $activity = $this->actingAs($this->administrator())
            ->get(route('admin.participants.index', ['tab' => 'activity', 'activity_days' => 999]))
            ->assertOk()
            ->viewData('activity');

        $this->assertSame(30, $activity['days']);
        $this->assertCount(30, $activity['series']);
    }

    public function test_users_page_does_not_show_participant_activity(): void
    {
        $this->actingAs($this->administrator())
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertDontSee('Participant activity');
    }
}
