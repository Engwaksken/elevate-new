<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Course;
use App\Models\Enrolment;
use App\Models\Profile;
use App\Models\Programme;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourseBranchesAndStaffTimetableTest extends TestCase
{
    use RefreshDatabase;

    private function staff(string $slug): User
    {
        $user = User::factory()->create(['user_type' => 'staff', 'status' => 'active']);
        $user->roles()->attach(Role::firstOrCreate(['slug' => $slug], ['name' => $slug]));
        return $user;
    }

    private function coursePayload(array $extra = []): array
    {
        return $extra + ['title' => 'Digital Skills', 'delivery_mode' => 'blended', 'pass_mark' => 50, 'status' => 'published'];
    }

    public function test_admin_can_assign_edit_clear_and_preserve_course_branches(): void
    {
        $admin = $this->staff('administrator');
        $a = Branch::create(['name' => 'Kampala', 'code' => 'KLA']);
        $b = Branch::create(['name' => 'Gulu', 'code' => 'GUL']);
        $this->actingAs($admin)->post(route('admin.elearning.courses.store'), $this->coursePayload(['branch_ids' => [$a->id, $b->id]]))->assertSessionHasNoErrors();
        $course = Course::sole();
        $this->assertEqualsCanonicalizing([$a->id, $b->id], $course->branches->pluck('id')->all());
        $this->get(route('admin.elearning.courses.index'))->assertOk()->assertSee('name="branch_ids[]"', false)->assertSee('Kampala, Gulu');
        $this->put(route('admin.elearning.courses.update', $course), $this->coursePayload())->assertSessionHasNoErrors();
        $this->assertSame(2, $course->branches()->count());
        $this->put(route('admin.elearning.courses.update', $course), $this->coursePayload(['branch_ids' => [$b->id]]))->assertSessionHasNoErrors();
        $this->assertSame($b->id, $course->fresh()->branch_id);
        $this->getJson('/api/v1/courses/'.$course->id)->assertOk()->assertJsonCount(1, 'data.branches')->assertJsonPath('data.branches.0.name', 'Gulu');
        $this->put(route('admin.elearning.courses.update', $course), $this->coursePayload(['sync_branches' => 1]))->assertSessionHasNoErrors();
        $this->assertSame(0, $course->branches()->count());
        $this->assertNull($course->fresh()->branch_id);
    }

    public function test_invalid_branch_lists_are_rejected(): void
    {
        $this->actingAs($this->staff('administrator'));
        $branch = Branch::create(['name' => 'Kampala', 'code' => 'KLA']);
        foreach ([[$branch->id, $branch->id], [9999]] as $ids) {
            $this->post(route('admin.elearning.courses.store'), $this->coursePayload(['branch_ids' => $ids]))->assertSessionHasErrors();
        }
        $this->assertDatabaseCount('courses', 0);
    }

    public function test_multibranch_enrolment_id_uses_the_participants_assigned_branch(): void
    {
        $a = Branch::create(['name' => 'Kampala', 'code' => 'KLA']);
        $b = Branch::create(['name' => 'Gulu', 'code' => 'GUL']);
        $programme = Programme::create(['name' => 'Digital Empowerment', 'code' => 'DE']);
        $course = Course::create(['title' => 'Skills', 'programme_id' => $programme->id, 'branch_id' => $a->id]);
        $course->branches()->sync([$a->id, $b->id]);
        $user = User::factory()->create(['user_type' => 'participant', 'status' => 'active']);
        Profile::create(['user_id' => $user->id, 'branch_id' => $b->id]);
        $enrolment = Enrolment::create(['user_id' => $user->id, 'course_id' => $course->id, 'enrolled_at' => '2026-10-02']);
        $this->assertSame('DE/GUL/C0/26/001', $enrolment->enrolment_code);
    }

    public function test_requested_program_and_operations_roles_can_manage_timetables(): void
    {
        $course = Course::create(['title' => 'Skills']);
        foreach (['program-officer','programs-officer','programs-lead','operations-lead','operations-officer'] as $i => $role) {
            $user = $this->staff($role);
            $this->actingAs($user)->get(route('admin.elearning.timetable.manage', $course))->assertOk();
            $this->post(route('admin.elearning.timetable.store', $course), [
                'title' => $role.' workshop', 'session_date' => '2026-10-'.(10 + $i),
                'start_time' => '09:00', 'end_time' => '11:00', 'timezone' => 'Africa/Kampala', 'status' => 'scheduled',
            ])->assertSessionHasNoErrors()->assertRedirect(route('admin.elearning.timetable.manage', $course));
        }
        $this->assertDatabaseCount('course_time_slots', 5);
        $slot = $course->timeSlots()->first();
        $this->delete(route('admin.elearning.timetable.destroy', [$course, $slot]))->assertSessionHas('success');
    }

    public function test_other_staff_can_view_but_not_modify_sessions(): void
    {
        $course = Course::create(['title' => 'Skills']);
        $trainer = $this->staff('trainer');
        $trainer->instructedCourses()->attach($course);
        $slot = $course->timeSlots()->create(['title' => 'Trainer session', 'created_by' => $trainer->id, 'starts_at' => '2026-10-15 06:00', 'ends_at' => '2026-10-15 08:00', 'timezone' => 'Africa/Kampala']);
        $this->actingAs($this->staff('viewer'))->get(route('admin.elearning.timetable.index'))->assertOk()->assertSee('Trainer session')->assertSee($trainer->name);
        $this->get(route('admin.elearning.timetable.manage', $course))->assertForbidden();
        $this->delete(route('admin.elearning.timetable.destroy', [$course, $slot]))->assertForbidden();
        $this->actingAs(User::factory()->create(['user_type' => 'participant', 'status' => 'active']))->get(route('admin.elearning.timetable.index'))->assertForbidden();
    }
}
