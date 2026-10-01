<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Certificate;
use App\Models\Course;
use App\Models\CourseApplication;
use App\Models\CourseCall;
use App\Models\Enrolment;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ParticipantIdentityTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['user_type' => 'staff', 'status' => 'active']);
        $this->admin->roles()->attach(Role::create(['name' => 'Super Administrator', 'slug' => 'super-administrator'])->id);
    }

    private function participant(array $attributes = []): User
    {
        return User::factory()->create($attributes + ['user_type' => 'participant', 'status' => 'active']);
    }

    private function instructor(): User
    {
        $user = User::factory()->create(['user_type' => 'staff', 'status' => 'active']);
        $user->roles()->attach(Role::firstOrCreate(['slug' => 'instructor'], ['name' => 'Instructor / Trainer'])->id);

        return $user;
    }

    public function test_participants_get_a_unique_permanent_id_and_staff_do_not(): void
    {
        $first = $this->participant();
        $second = $this->participant();
        $staff = $this->instructor();

        $this->assertSame(sprintf('EH%s-%06d', now()->format('y'), $first->id), $first->fresh()->participant_code);
        $this->assertNotSame($first->fresh()->participant_code, $second->fresh()->participant_code);
        $this->assertNull($staff->fresh()->participant_code);

        // The ID never changes on later saves.
        $code = $first->fresh()->participant_code;
        $first->update(['name' => 'Renamed']);
        $this->assertSame($code, $first->fresh()->participant_code);

        // A staff account converted to a participant gets one.
        $staff->update(['user_type' => 'participant']);
        $this->assertNotNull($staff->fresh()->participant_code);
    }

    public function test_migration_backfills_existing_participants(): void
    {
        $participant = $this->participant();
        DB::table('users')->where('id', $participant->id)->update(['participant_code' => null, 'created_at' => '2024-03-01 10:00:00']);

        (require database_path('migrations/2026_10_01_120000_add_participant_codes_and_instructor_branches.php'))->up();

        $this->assertSame(sprintf('EH24-%06d', $participant->id), $participant->fresh()->participant_code);
    }

    public function test_admin_can_find_a_participant_by_id(): void
    {
        $participant = $this->participant(['name' => 'Grace Achieng']);
        $this->participant(['name' => 'Someone Else']);

        $this->actingAs($this->admin)
            ->get(route('admin.users.index', ['search' => $participant->fresh()->participant_code]))
            ->assertOk()
            ->assertSee('Grace Achieng')
            ->assertSee($participant->fresh()->participant_code)
            ->assertDontSee('Someone Else');
    }

    public function test_application_review_flags_returning_participants_and_possible_duplicates(): void
    {
        $previous = Course::create(['title' => 'Web Basics', 'status' => 'published']);
        $next = Course::create(['title' => 'Advanced Web', 'status' => 'published']);

        $returning = $this->participant(['name' => 'Returning Ruth', 'phone' => '0772 123456']);
        Enrolment::create(['course_id' => $previous->id, 'user_id' => $returning->id, 'status' => 'completed']);
        Certificate::create(['course_id' => $previous->id, 'user_id' => $returning->id, 'certificate_number' => 'EH360-1', 'issued_on' => now(), 'verification_token' => 'tok-1']);

        $newcomer = $this->participant(['name' => 'New Nora', 'phone' => '0700 000001']);
        // A second account registered with Ruth's number in international format.
        $this->participant(['name' => 'Ruth Second Account', 'phone' => '+256772123456']);

        $call = CourseCall::create(['title' => 'October Intake', 'status' => 'published']);
        $call->courses()->attach($next->id);
        foreach ([$returning, $newcomer] as $applicant) {
            CourseApplication::create(['course_call_id' => $call->id, 'user_id' => $applicant->id, 'status' => 'submitted', 'submitted_at' => now()]);
        }

        $this->actingAs($this->admin)
            ->get(route('admin.course-calls.applications', $call))
            ->assertOk()
            ->assertSee('Returning · 1 previous course · 1 completed · 1 certificate')
            ->assertSee('Web Basics')
            ->assertSee('New participant')
            ->assertSee('Possible duplicate account')
            ->assertSee('Ruth Second Account')
            ->assertSee($returning->fresh()->participant_code);
    }

    public function test_approving_an_application_from_the_review_modal_enrols_the_participant(): void
    {
        $course = Course::create(['title' => 'Data Skills', 'status' => 'published']);
        $applicant = $this->participant();
        $call = CourseCall::create(['title' => 'Intake', 'status' => 'published']);
        $call->courses()->attach($course->id);
        $application = CourseApplication::create(['course_call_id' => $call->id, 'user_id' => $applicant->id, 'status' => 'submitted']);

        $this->actingAs($this->admin)
            ->get(route('admin.course-calls.applications', $call))
            ->assertSee('name="approved_course_id"', false)
            ->assertSee('<option value="'.$course->id.'" selected>', false);

        $this->actingAs($this->admin)->put(route('admin.course-applications.review', $application), [
            'application_id' => $application->id,
            'status' => 'approved',
            'approved_course_id' => $course->id,
        ])->assertSessionHas('success');

        $this->assertTrue(Enrolment::where('course_id', $course->id)->where('user_id', $applicant->id)->exists());
    }

    public function test_instructor_can_be_assigned_to_several_branches_and_courses(): void
    {
        $instructor = $this->instructor();
        $kampala = Branch::create(['name' => 'Kampala', 'code' => 'KLA', 'is_active' => true]);
        $gulu = Branch::create(['name' => 'Gulu', 'code' => 'GUL', 'is_active' => true]);
        $courseA = Course::create(['title' => 'Coding 101', 'status' => 'published']);
        $courseB = Course::create(['title' => 'Design 101', 'status' => 'published']);
        $instructor->instructedCourses()->attach($courseA->id, ['is_lead' => true]);

        $payload = [
            'name' => $instructor->name,
            'email' => $instructor->email,
            'user_type' => 'staff',
            'status' => 'active',
            'roles' => $instructor->roles->pluck('id')->all(),
            'sync_assignments' => 1,
            'branches' => [$kampala->id, $gulu->id],
            'courses' => [$courseA->id, $courseB->id],
        ];

        $this->actingAs($this->admin)->put(route('admin.users.update', $instructor), $payload)->assertSessionHas('success');

        $instructor->refresh();
        $this->assertEqualsCanonicalizing([$kampala->id, $gulu->id], $instructor->branches->pluck('id')->all());
        $this->assertEqualsCanonicalizing([$courseA->id, $courseB->id], $instructor->instructedCourses->pluck('id')->all());
        $this->assertTrue((bool) $instructor->instructedCourses->firstWhere('id', $courseA->id)->pivot->is_lead);

        // Both courses are now open to the instructor.
        $this->actingAs($instructor)->get(route('instructor.courses.manage', $courseA))->assertOk();
        $this->actingAs($instructor)->get(route('instructor.courses.manage', $courseB))->assertOk();

        // A save without the assignments tab leaves them untouched.
        unset($payload['sync_assignments'], $payload['branches'], $payload['courses']);
        $this->actingAs($this->admin)->put(route('admin.users.update', $instructor), $payload);
        $this->assertSame(2, $instructor->branches()->count());
        $this->assertSame(2, $instructor->instructedCourses()->count());

        $this->actingAs($this->admin)->get(route('admin.users.index', ['branch_id' => $gulu->id]))
            ->assertOk()
            ->assertSee($instructor->name)
            ->assertSee('Gulu');
    }

    public function test_instructor_participants_tab_shows_id_and_returning_status(): void
    {
        $instructor = $this->instructor();
        $past = Course::create(['title' => 'Intro Course', 'status' => 'published']);
        $course = Course::create(['title' => 'Follow-up Course', 'status' => 'published']);
        $instructor->instructedCourses()->attach($course->id);

        $learner = $this->participant();
        Enrolment::create(['course_id' => $past->id, 'user_id' => $learner->id, 'status' => 'completed']);
        Enrolment::create(['course_id' => $course->id, 'user_id' => $learner->id, 'status' => 'enrolled']);

        $this->actingAs($instructor)
            ->get(route('instructor.courses.manage', ['course' => $course, 'tab' => 'participants', 'participant_search' => $learner->fresh()->participant_code]))
            ->assertOk()
            ->assertSee($learner->fresh()->participant_code)
            ->assertSee('Returning · 1 previous course');
    }

    public function test_bulk_enrolment_accepts_participant_ids(): void
    {
        $course = Course::create(['title' => 'Bulk Course', 'status' => 'published']);
        $learner = $this->participant();
        $code = $learner->fresh()->participant_code;

        $csv = "course_id,participant_code,status\n{$course->id},{$code},enrolled\n";
        $file = UploadedFile::fake()->createWithContent('enrolments.csv', $csv);

        $this->actingAs($this->admin)->post(route('admin.elearning.bulk-enrolment.store'), ['file' => $file]);

        $this->assertTrue(Enrolment::where('course_id', $course->id)->where('user_id', $learner->id)->exists());
    }
}
