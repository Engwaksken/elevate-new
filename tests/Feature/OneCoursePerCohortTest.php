<?php

namespace Tests\Feature;

use App\Models\Cohort;
use App\Models\Course;
use App\Models\Enrolment;
use App\Models\Role;
use App\Models\User;
use App\Services\EnrolmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class OneCoursePerCohortTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $user = User::factory()->create(['user_type' => 'staff', 'status' => 'active']);
        $user->roles()->attach(Role::firstOrCreate(['slug' => 'administrator'], ['name' => 'administrator']));

        return $user;
    }

    private function participant(): User
    {
        return User::factory()->create(['user_type' => 'participant', 'status' => 'active', 'email_verified_at' => now()]);
    }

    private function course(string $title, array $attributes = []): Course
    {
        return Course::create(array_merge(['title' => $title, 'delivery_mode' => 'online', 'status' => 'published', 'pass_mark' => 50], $attributes));
    }

    private function cohort(string $name): Cohort
    {
        return Cohort::create(['name' => $name, 'status' => 'active']);
    }

    public function test_a_second_chosen_course_in_the_same_cohort_is_refused(): void
    {
        $service = app(EnrolmentService::class);
        $user = $this->participant();
        $cohort = $this->cohort('Cohort 1');

        $service->enrol($user, $this->course('Digital Skills'), ['cohort_id' => $cohort->id]);

        $this->expectException(ValidationException::class);
        $service->enrol($user, $this->course('Tailoring'), ['cohort_id' => $cohort->id]);
    }

    public function test_a_different_cohort_or_a_withdrawn_enrolment_allows_another_course(): void
    {
        $service = app(EnrolmentService::class);
        $user = $this->participant();
        $first = $this->cohort('Cohort 1');

        $digital = $service->enrol($user, $this->course('Digital Skills'), ['cohort_id' => $first->id]);
        $service->enrol($user, $this->course('Tailoring'), ['cohort_id' => $this->cohort('Cohort 2')->id]);

        $digital->update(['status' => 'withdrawn']);
        $service->enrol($user, $this->course('Bakery'), ['cohort_id' => $first->id]);

        $this->assertSame(3, Enrolment::where('user_id', $user->id)->count());
    }

    public function test_compulsory_courses_are_added_in_the_same_cohort_and_do_not_count(): void
    {
        $service = app(EnrolmentService::class);
        $user = $this->participant();
        $cohort = $this->cohort('Cohort 1');
        $primary = $this->course('Digital Skills');
        $orientation = $this->course('Orientation');
        $safety = $this->course('Online Safety');
        $primary->compulsoryCourses()->attach([$orientation->id, $safety->id]);

        $service->enrol($user, $primary, ['cohort_id' => $cohort->id]);

        foreach ([$primary, $orientation, $safety] as $course) {
            $this->assertDatabaseHas('enrolments', ['user_id' => $user->id, 'course_id' => $course->id, 'cohort_id' => $cohort->id]);
        }

        // Enrolling directly in a compulsory course is never blocked either.
        $this->assertNull($service->conflictingEnrolment($user, $orientation, $cohort->id));
    }

    public function test_admin_enrolment_form_enforces_the_rule_and_adds_compulsory_courses(): void
    {
        $user = $this->participant();
        $cohort = $this->cohort('Cohort 1');
        $primary = $this->course('Digital Skills');
        $orientation = $this->course('Orientation');
        $primary->compulsoryCourses()->attach($orientation->id);

        $this->actingAs($this->admin())
            ->post(route('admin.elearning.enrolments.store'), ['user_id' => $user->id, 'course_id' => $primary->id, 'cohort_id' => $cohort->id])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('enrolments', ['user_id' => $user->id, 'course_id' => $orientation->id, 'cohort_id' => $cohort->id]);

        $this->post(route('admin.elearning.enrolments.store'), ['user_id' => $user->id, 'course_id' => $this->course('Tailoring')->id, 'cohort_id' => $cohort->id])
            ->assertSessionHasErrors('course_id');

        // Moving another cohort's enrolment into this cohort is refused too.
        $other = app(EnrolmentService::class)->enrol($user, $this->course('Bakery'), ['cohort_id' => $this->cohort('Cohort 2')->id]);
        $this->put(route('admin.elearning.enrolments.update', $other), ['cohort_id' => $cohort->id, 'status' => 'enrolled'])
            ->assertSessionHasErrors('cohort_id');
    }

    public function test_bulk_enrolment_skips_participants_already_on_another_course_in_the_cohort(): void
    {
        $cohort = $this->cohort('Cohort 1');
        $busy = $this->participant();
        $free = $this->participant();
        app(EnrolmentService::class)->enrol($busy, $this->course('Tailoring'), ['cohort_id' => $cohort->id]);

        $primary = $this->course('Digital Skills');
        $orientation = $this->course('Orientation');
        $primary->compulsoryCourses()->attach($orientation->id);

        $this->actingAs($this->admin())
            ->post(route('admin.elearning.bulk-enrolment.enroll-selected'), [
                'course_id' => $primary->id, 'cohort_id' => $cohort->id, 'user_ids' => [$busy->id, $free->id],
            ])
            ->assertSessionHas('success', fn ($message) => str_contains($message, '1 participant(s) enrolled.') && str_contains($message, '1 skipped'));

        $this->assertDatabaseMissing('enrolments', ['user_id' => $busy->id, 'course_id' => $primary->id]);
        $this->assertDatabaseHas('enrolments', ['user_id' => $free->id, 'course_id' => $orientation->id, 'cohort_id' => $cohort->id]);
    }

    public function test_self_enrolment_is_limited_to_one_chosen_course(): void
    {
        $user = $this->participant();
        $first = $this->course('Digital Skills', ['self_enrolment_enabled' => true]);
        $second = $this->course('Tailoring', ['self_enrolment_enabled' => true]);

        $this->actingAs($user)->post(route('learning.enrol', $first))->assertRedirect(route('learning.my-courses'));
        $this->post(route('learning.enrol', $second))->assertSessionHas('error');

        $this->assertDatabaseMissing('enrolments', ['user_id' => $user->id, 'course_id' => $second->id]);
    }

    public function test_already_registered_participants_get_newly_added_compulsory_courses(): void
    {
        $service = app(EnrolmentService::class);
        $cohort = $this->cohort('Cohort 1');
        $primary = $this->course('Digital Skills');
        $withdrawn = $this->participant();
        $active = $this->participant();

        $service->enrol($active, $primary, ['cohort_id' => $cohort->id]);
        $service->enrol($withdrawn, $primary, ['cohort_id' => $cohort->id])->update(['status' => 'withdrawn']);

        $orientation = $this->course('Orientation');

        $this->actingAs($this->admin())
            ->put(route('admin.elearning.courses.update', $primary), [
                'title' => 'Digital Skills', 'delivery_mode' => 'online', 'pass_mark' => 50, 'status' => 'published',
                'compulsory_course_ids' => [$orientation->id],
            ])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success', 'Course updated. 1 compulsory course enrolment added for existing participants.');

        $this->assertDatabaseHas('enrolments', ['user_id' => $active->id, 'course_id' => $orientation->id, 'cohort_id' => $cohort->id]);
        $this->assertDatabaseMissing('enrolments', ['user_id' => $withdrawn->id, 'course_id' => $orientation->id]);

        // The catch-up command is safe to run again.
        $this->artisan('enrolments:sync-compulsory')->expectsOutput('0 compulsory course enrolment(s) added.')->assertSuccessful();
    }
}
