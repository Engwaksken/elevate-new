<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Cohort;
use App\Models\Course;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ParticipantRegistrationAssignmentTest extends TestCase
{
    use RefreshDatabase;

    /** @return array{0: User, 1: Branch, 2: Cohort, 3: Course} */
    private function registerWithPreferences(): array
    {
        Mail::fake();

        $branch = Branch::create(['name' => 'Kampala Branch', 'code' => 'KLA', 'is_active' => true]);
        $cohort = Cohort::create(['name' => 'October Cohort', 'code' => 'OCT', 'status' => 'active', 'branch_id' => $branch->id]);
        $course = Course::create(['title' => 'Digital Skills', 'delivery_mode' => 'online', 'status' => 'published', 'pass_mark' => 50]);
        $course->branches()->attach($branch->id);

        $this->post(route('register.store'), [
            'surname' => 'Nansubuga',
            'given_name' => 'Sarah',
            'email' => 'sarah.assign@gmail.com',
            'phone' => '+256700000000',
            'password' => 'StrongPass1',
            'password_confirmation' => 'StrongPass1',
            'terms' => '1',
            'interests' => ['learning'],
            'branch_id' => $branch->id,
            'cohort_id' => $cohort->id,
            'course_id' => $course->id,
        ])->assertSessionHasNoErrors();

        return [User::where('email', 'sarah.assign@gmail.com')->firstOrFail(), $branch, $cohort, $course];
    }

    public function test_verifying_activates_the_participant_and_keeps_their_choices(): void
    {
        [$user, $branch, $cohort, $course] = $this->registerWithPreferences();
        $this->assertSame('pending', $user->status);

        $this->get($user->emailVerificationUrl())->assertRedirect(route('dashboard'));

        $user->refresh();
        $this->assertSame('active', $user->status);
        $this->assertSame($branch->id, (int) $user->profile->branch_id);
        $this->assertDatabaseHas('enrolments', [
            'user_id' => $user->id,
            'course_id' => $course->id,
            'cohort_id' => $cohort->id,
        ]);
    }

    public function test_the_participants_list_shows_branch_cohort_and_courses(): void
    {
        [$user] = $this->registerWithPreferences();
        auth()->logout();

        $admin = User::factory()->create(['user_type' => 'staff', 'status' => 'active']);
        $admin->roles()->attach(Role::firstOrCreate(['slug' => 'super-administrator'], ['name' => 'Super Administrator']));

        $this->actingAs($admin)
            ->get(route('admin.participants.index'))
            ->assertOk()
            ->assertSee('Sarah Nansubuga')
            ->assertSee('Kampala Branch')
            ->assertSee('October Cohort')
            ->assertSee('Digital Skills');
    }
}
