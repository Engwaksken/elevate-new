<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\CourseTimeSlot;
use App\Models\Enrolment;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CourseTimetableTest extends TestCase
{
    use RefreshDatabase;

    private Course $course;
    private User $trainer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->course = Course::create(['title' => 'Digital Skills', 'status' => 'published']);
        $this->trainer = User::factory()->create(['user_type' => 'staff', 'status' => 'active']);
        $this->trainer->roles()->attach(Role::create(['name' => 'Trainer', 'slug' => 'trainer']));
        $this->trainer->instructedCourses()->attach($this->course);
    }

    private function payload(array $changes = []): array
    {
        return array_replace([
            'title' => 'Practical workshop', 'session_date' => '2026-10-15',
            'start_time' => '09:00', 'end_time' => '11:00', 'timezone' => 'Africa/Kampala',
            'venue' => 'Room 2', 'meeting_link' => 'https://meet.example.test/session',
            'notes' => 'Bring your laptop.', 'status' => 'scheduled',
        ], $changes);
    }

    private function createSlot(array $changes = []): CourseTimeSlot
    {
        $this->actingAs($this->trainer)->post(route('instructor.courses.timetable.store', $this->course), $this->payload($changes))
            ->assertSessionHasNoErrors()->assertRedirect(route('instructor.courses.manage', ['course' => $this->course, 'tab' => 'timetable']));

        return $this->course->timeSlots()->latest('id')->firstOrFail();
    }

    private function participant(bool $enrolled = true): User
    {
        $user = User::factory()->create(['user_type' => 'participant', 'status' => 'active']);
        if ($enrolled) {
            Enrolment::create(['user_id' => $user->id, 'course_id' => $this->course->id, 'status' => 'enrolled']);
        }

        return $user;
    }

    public function test_assigned_trainer_can_create_edit_cancel_and_delete_slots(): void
    {
        $slot = $this->createSlot();
        $this->assertSame($this->trainer->id, $slot->created_by);
        $this->assertSame('2026-10-15 06:00', $slot->starts_at->format('Y-m-d H:i'));
        $this->assertSame('2026-10-15 08:00', $slot->ends_at->format('Y-m-d H:i'));
        $this->assertSame('UTC', $slot->starts_at->timezoneName);
        $this->assertSame('09:00', $slot->starts_at->setTimezone($slot->timezone)->format('H:i'));
        $this->get(route('instructor.courses.manage', $this->course))->assertOk()
            ->assertSee(route('instructor.courses.manage', ['course' => $this->course, 'tab' => 'timetable']), false);
        $this->get(route('instructor.courses.manage', ['course' => $this->course, 'tab' => 'timetable']))
            ->assertOk()->assertSee('Practical workshop')->assertSee('09:00 – 11:00')->assertSee('Room 2');
        $this->put(route('instructor.courses.timetable.update', [$this->course, $slot]), $this->payload([
            'title' => 'Revised workshop', 'start_time' => '10:00', 'end_time' => '12:00', 'status' => 'cancelled',
        ]))->assertSessionHasNoErrors();
        $this->assertSame('cancelled', $slot->fresh()->status);
        $this->assertSame('Revised workshop', $slot->fresh()->title);
        $this->delete(route('instructor.courses.timetable.destroy', [$this->course, $slot]))->assertSessionHas('success');
        $this->assertDatabaseMissing('course_time_slots', ['id' => $slot->id]);
    }

    public function test_management_is_restricted_to_assigned_staff_and_nested_course_is_checked(): void
    {
        $slot = $this->createSlot();
        $otherCourse = Course::create(['title' => 'Other', 'status' => 'published']);
        $this->post(route('instructor.courses.timetable.store', $otherCourse), $this->payload())->assertForbidden();
        $this->trainer->instructedCourses()->attach($otherCourse);
        $this->put(route('instructor.courses.timetable.update', [$otherCourse, $slot]), $this->payload())->assertNotFound();
        $this->delete(route('instructor.courses.timetable.destroy', [$otherCourse, $slot]))->assertNotFound();
        $this->actingAs($this->participant())->post(route('instructor.courses.timetable.store', $this->course), $this->payload())->assertForbidden();
        $this->put(route('instructor.courses.timetable.update', [$this->course, $slot]), $this->payload())->assertForbidden();
        $this->delete(route('instructor.courses.timetable.destroy', [$this->course, $slot]))->assertForbidden();
        $this->assertDatabaseHas('course_time_slots', ['id' => $slot->id, 'title' => 'Practical workshop']);
    }

    public function test_assigned_instructors_and_administrators_can_manage_the_timetable(): void
    {
        $instructor = User::factory()->create(['user_type' => 'staff', 'status' => 'active']);
        $instructor->roles()->attach(Role::create(['name' => 'Instructor', 'slug' => 'instructor']));
        $instructor->instructedCourses()->attach($this->course);
        $this->actingAs($instructor)->post(route('instructor.courses.timetable.store', $this->course), $this->payload())
            ->assertSessionHasNoErrors();

        $admin = User::factory()->create(['user_type' => 'staff', 'status' => 'active']);
        $admin->roles()->attach(Role::create(['name' => 'Administrator', 'slug' => 'administrator']));
        $this->actingAs($admin)->post(route('instructor.courses.timetable.store', $this->course), $this->payload([
            'start_time' => '12:00', 'end_time' => '13:00',
        ]))->assertSessionHasNoErrors();
        $this->assertDatabaseCount('course_time_slots', 2);
    }

    public function test_enrolled_participants_can_view_timetable_on_web_and_mobile(): void
    {
        $slot = $this->createSlot();
        $participant = $this->participant();
        $this->actingAs($participant)->get(route('learning.course.dashboard', $this->course))
            ->assertOk()->assertSee('Course Timetable')->assertSee('Practical workshop')->assertSee('Bring your laptop.')
            ->assertSee('Africa/Kampala')->assertSee('https://meet.example.test/session');
        Sanctum::actingAs($participant, ['participant']);
        $this->getJson('/api/v1/participant/courses/'.$this->course->id)->assertOk()
            ->assertJsonPath('course.timetable.0.title', 'Practical workshop')
            ->assertJsonPath('course.timetable.0.time_label', '09:00 – 11:00')
            ->assertJsonPath('course.timetable.0.starts_at', '2026-10-15T06:00:00+00:00');
        $slot->update(['status' => 'cancelled']);
        $this->getJson('/api/v1/participant/courses/'.$this->course->id)->assertJsonPath('course.timetable.0.status', 'cancelled');
    }

    public function test_unenrolled_and_inactive_participants_cannot_read_private_schedule(): void
    {
        $this->createSlot();
        $participant = $this->participant(false);
        $this->actingAs($participant)->get(route('learning.course.show', $this->course))->assertOk()
            ->assertDontSee('Practical workshop')->assertDontSee('https://meet.example.test/session');
        Sanctum::actingAs($participant, ['participant']);
        $this->getJson('/api/v1/participant/courses/'.$this->course->id)->assertForbidden();
        $enrolled = $this->participant();
        $enrolled->update(['status' => 'inactive']);
        $this->actingAs($enrolled)->get(route('learning.course.show', $this->course))->assertOk()->assertDontSee('Practical workshop');
    }

    public function test_overlaps_are_rejected_but_adjacent_and_cancelled_slots_are_allowed(): void
    {
        $slot = $this->createSlot();
        $this->post(route('instructor.courses.timetable.store', $this->course), $this->payload([
            'start_time' => '10:00', 'end_time' => '12:00',
        ]))->assertSessionHasErrors('start_time');
        $this->assertDatabaseCount('course_time_slots', 1);
        $adjacent = $this->createSlot(['title' => 'Afternoon', 'start_time' => '11:00', 'end_time' => '12:00']);
        $this->put(route('instructor.courses.timetable.update', [$this->course, $adjacent]), $this->payload())
            ->assertSessionHasErrors('start_time');
        $this->assertSame('Afternoon', $adjacent->fresh()->title);
        $this->createSlot(['title' => 'Cancelled session', 'status' => 'cancelled']);
        $this->put(route('instructor.courses.timetable.update', [$this->course, $slot]), $this->payload())->assertSessionHasNoErrors();
    }

    public function test_invalid_dates_times_timezones_and_meeting_links_are_rejected(): void
    {
        $this->actingAs($this->trainer);
        foreach ([
            ['session_date' => '2026-02-30'], ['end_time' => '09:00'], ['end_time' => '08:00'],
            ['timezone' => 'invalid/timezone'], ['meeting_link' => 'javascript:alert(1)'],
            ['timezone' => 'Europe/London', 'session_date' => '2026-03-29', 'start_time' => '01:30', 'end_time' => '03:00'],
        ] as $changes) {
            $this->post(route('instructor.courses.timetable.store', $this->course), $this->payload($changes))->assertSessionHasErrors();
        }
        $this->assertDatabaseCount('course_time_slots', 0);
    }
}
