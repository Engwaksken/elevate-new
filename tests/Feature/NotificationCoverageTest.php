<?php

namespace Tests\Feature;

use App\Models\Appraisal;
use App\Models\AppraisalCycle;
use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\Course;
use App\Models\CourseApplication;
use App\Models\CourseCall;
use App\Models\CourseModule;
use App\Models\Employee;
use App\Models\Enrolment;
use App\Models\Lesson;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\MentorMatch;
use App\Models\MentorshipSession;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Models\UserNotification;
use App\Services\HR\AppraisalWorkflowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class NotificationCoverageTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Course $course;
    private CourseModule $module;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['user_type' => 'staff', 'status' => 'active']);
        $this->admin->roles()->attach(Role::create(['name' => 'Super Admin', 'slug' => 'super-admin'])->id);

        $this->course = Course::create(['title' => 'Digital Marketing', 'status' => 'published']);
        $this->module = CourseModule::create([
            'course_id' => $this->course->id, 'title' => 'Module 1', 'position' => 1, 'is_published' => true,
        ]);
    }

    private function enrol(string $status = 'enrolled'): User
    {
        $user = User::factory()->create(['user_type' => 'participant', 'status' => 'active', 'phone' => '07'.random_int(10000000, 99999999)]);
        Enrolment::create(['course_id' => $this->course->id, 'user_id' => $user->id, 'status' => $status]);

        // Seeding an enrolment without an actor notifies; start each test clean.
        DB::table('user_notifications')->where('user_id', $user->id)->delete();

        return $user;
    }

    private function notificationsFor(User $user, ?string $type = null)
    {
        return UserNotification::query()
            ->where('user_id', $user->id)
            ->when($type, fn ($q) => $q->where('type', $type))
            ->get();
    }

    /* ------------------------------------------------------------ Enrolment */

    public function test_admin_enrolment_and_status_change_notify_the_participant(): void
    {
        $learner = User::factory()->create();

        $this->actingAs($this->admin)
            ->post('/admin/elearning/enrolments', ['course_id' => $this->course->id, 'user_id' => $learner->id])
            ->assertSessionHas('success');

        $this->assertDatabaseHas('user_notifications', [
            'user_id' => $learner->id, 'type' => 'enrolment', 'title' => 'Enrolled: Digital Marketing',
        ]);

        $enrolment = Enrolment::where('user_id', $learner->id)->firstOrFail();

        $this->actingAs($this->admin)
            ->put("/admin/elearning/enrolments/{$enrolment->id}", ['status' => 'completed'])
            ->assertSessionHas('success');

        $this->assertDatabaseHas('user_notifications', [
            'user_id' => $learner->id, 'type' => 'enrolment', 'title' => 'Course completed: Digital Marketing',
        ]);
        $this->assertDatabaseMissing('user_notifications', ['user_id' => $this->admin->id]);
    }

    public function test_self_enrolment_does_not_notify_the_actor(): void
    {
        $learner = User::factory()->create();
        $this->actingAs($learner);

        Enrolment::create(['course_id' => $this->course->id, 'user_id' => $learner->id, 'status' => 'enrolled']);

        $this->assertCount(0, $this->notificationsFor($learner));
    }

    public function test_bulk_enrolment_sends_one_batched_notification_per_participant(): void
    {
        $a = User::factory()->create();
        $b = User::factory()->create();
        $existing = $this->enrol();

        $csv = "course_id,email,status\n"
            ."{$this->course->id},{$a->email},enrolled\n"
            ."{$this->course->id},{$b->email},enrolled\n"
            ."{$this->course->id},{$existing->email},enrolled\n"   // unchanged: no notice
            ."{$this->course->id},missing@example.com,enrolled\n"; // skipped row

        $file = UploadedFile::fake()->createWithContent('enrolments.csv', $csv);

        $this->actingAs($this->admin)
            ->post('/admin/elearning/bulk-enrolment', ['file' => $file])
            ->assertSessionHas('success');

        $this->assertCount(1, $this->notificationsFor($a, 'enrolment'));
        $this->assertCount(1, $this->notificationsFor($b, 'enrolment'));
        $this->assertCount(0, $this->notificationsFor($existing));
        $this->assertSame(true, $this->notificationsFor($a, 'enrolment')->first()->data['bulk']);
    }

    /* ------------------------------------------------------------ Lessons */

    public function test_publishing_a_lesson_notifies_active_learners_only(): void
    {
        $active = $this->enrol();
        $withdrawn = $this->enrol('withdrawn');
        $instructor = $this->admin;

        $this->actingAs($instructor)
            ->post("/instructor/courses/{$this->course->id}/modules/{$this->module->id}/lessons", [
                'title' => 'Draft lesson', 'content_type' => 'text', 'content' => 'x',
            ])->assertSessionHas('success');

        $this->assertCount(0, $this->notificationsFor($active, 'lesson_published'), 'Unpublished lessons are silent.');

        $lesson = Lesson::where('title', 'Draft lesson')->firstOrFail();

        $this->actingAs($instructor)
            ->put("/instructor/courses/{$this->course->id}/modules/{$this->module->id}/lessons/{$lesson->id}", [
                'title' => 'Draft lesson', 'content_type' => 'text', 'content' => 'x', 'is_published' => 1,
            ])->assertSessionHas('success');

        $notice = $this->notificationsFor($active, 'lesson_published')->sole();
        $this->assertSame('New lesson: Draft lesson', $notice->title);
        $this->assertSame($lesson->id, $notice->data['lesson_id']);
        $this->assertStringContainsString('/learning/lessons/'.$lesson->id, $notice->action_url);
        $this->assertCount(0, $this->notificationsFor($withdrawn));
        $this->assertCount(0, $this->notificationsFor($instructor));

        // Editing an already published lesson does not notify again.
        $this->actingAs($instructor)
            ->put("/instructor/courses/{$this->course->id}/modules/{$this->module->id}/lessons/{$lesson->id}", [
                'title' => 'Renamed', 'content_type' => 'text', 'content' => 'x', 'is_published' => 1,
            ]);
        $this->assertCount(1, $this->notificationsFor($active, 'lesson_published'));
    }

    /* ------------------------------------------------------------ Assignments */

    public function test_publishing_an_assignment_and_changing_its_due_date_notify_learners(): void
    {
        $learner = $this->enrol();

        $this->actingAs($this->admin)
            ->post("/instructor/courses/{$this->course->id}/assessments", [
                'title' => 'Brief', 'type' => 'assignment', 'pass_mark' => 50, 'max_attempts' => 1,
                'due_at' => now()->addWeek()->format('Y-m-d H:i'), 'is_published' => 1,
            ])->assertSessionHas('success');

        $notice = $this->notificationsFor($learner, 'assignment_published')->sole();
        $this->assertSame('New assignment: Brief', $notice->title);
        $this->assertNotNull($notice->data['due_at']);

        $assessment = Assessment::where('title', 'Brief')->firstOrFail();
        $assessment->update(['due_at' => now()->addWeeks(2)]);

        $this->assertCount(1, $this->notificationsFor($learner, 'assignment_due_date_changed'));
    }

    public function test_submission_notifies_instructors_and_grading_notifies_the_participant(): void
    {
        $instructor = User::factory()->create(['user_type' => 'staff', 'status' => 'active']);
        $this->course->instructors()->attach($instructor->id);
        $learner = $this->enrol();

        $assessment = Assessment::create([
            'course_id' => $this->course->id, 'title' => 'Essay', 'type' => 'assignment',
            'max_attempts' => 2, 'due_at' => now()->addWeek(), 'is_published' => true,
        ]);
        DB::table('user_notifications')->delete();

        Sanctum::actingAs($learner, ['participant']);
        $this->postJson("/api/v1/participant/assignments/{$assessment->id}/submit", ['submission_text' => 'My essay'])
            ->assertSuccessful();

        $this->assertCount(1, $this->notificationsFor($instructor, 'assignment_submitted'));
        $this->assertCount(0, $this->notificationsFor($learner));

        $attempt = AssessmentAttempt::where('user_id', $learner->id)->firstOrFail();

        $this->actingAs($this->admin)
            ->put("/instructor/courses/{$this->course->id}/submissions/{$attempt->id}/review", [
                'status' => 'graded', 'score' => 18, 'percentage' => 90, 'instructor_feedback' => 'Strong work.',
            ])->assertSessionHas('success');

        $notice = $this->notificationsFor($learner, 'assignment_graded')->sole();
        $this->assertSame('Graded: Essay', $notice->title);
        $this->assertStringContainsString('90%', $notice->message);
        $this->assertStringContainsString('Strong work.', $notice->message);
    }

    public function test_auto_graded_quiz_does_not_notify_instructors_or_the_participant(): void
    {
        $instructor = User::factory()->create(['user_type' => 'staff', 'status' => 'active']);
        $this->course->instructors()->attach($instructor->id);
        $learner = $this->enrol();

        $quiz = Assessment::create([
            'course_id' => $this->course->id, 'title' => 'Quiz', 'type' => 'quiz', 'max_attempts' => 3, 'is_published' => true,
        ]);
        $question = $quiz->questions()->create([
            'question_type' => 'true_false', 'question_text' => 'Sky is blue?', 'options' => ['true' => 'True', 'false' => 'False'],
            'correct_answer' => ['value' => 'true'], 'marks' => 1, 'position' => 1,
        ]);
        DB::table('user_notifications')->delete();

        $this->actingAs($learner)
            ->post("/learning/assessments/{$quiz->id}", ['answers' => [$question->id => 'true']]);

        $this->assertSame('graded', AssessmentAttempt::where('user_id', $learner->id)->value('status'));
        $this->assertCount(0, $this->notificationsFor($instructor));
        $this->assertCount(0, $this->notificationsFor($learner, 'assignment_graded'));
    }

    /* ------------------------------------------------------------ Announcements */

    public function test_announcement_notifies_learners_and_is_visible_in_the_participant_api(): void
    {
        $learner = $this->enrol();

        $this->actingAs($this->admin)
            ->post("/instructor/courses/{$this->course->id}/announcements", ['title' => 'Class moved', 'body' => 'We meet on Friday.'])
            ->assertSessionHas('success');

        $this->assertCount(0, $this->notificationsFor($this->admin));

        Sanctum::actingAs($learner, ['participant']);
        $this->getJson('/api/v1/participant/notifications')
            ->assertOk()
            ->assertJsonPath('data.0.type', 'course_announcement')
            ->assertJsonPath('data.0.title', 'Announcement: Class moved');
    }

    public function test_scheduled_announcement_is_not_notified_yet(): void
    {
        $learner = $this->enrol();

        $this->actingAs($this->admin)
            ->post("/instructor/courses/{$this->course->id}/announcements", [
                'title' => 'Later', 'body' => 'Soon', 'published_at' => now()->addDay()->format('Y-m-d H:i'),
            ]);

        $this->assertCount(0, $this->notificationsFor($learner));
    }

    /* ------------------------------------------------------------ Applications, mentorship, jobs */

    public function test_course_application_decision_notifies_the_applicant(): void
    {
        $applicant = User::factory()->create();
        $call = CourseCall::create(['title' => 'Spring Intake', 'status' => 'published']);
        $application = CourseApplication::create(['course_call_id' => $call->id, 'user_id' => $applicant->id, 'status' => 'submitted']);

        $this->actingAs($this->admin);
        $application->update(['status' => 'rejected', 'reviewer_comments' => 'Places are full.']);

        $notice = $this->notificationsFor($applicant, 'course_application')->sole();
        $this->assertStringContainsString('Spring Intake', $notice->message);
        $this->assertStringContainsString('Places are full.', $notice->message);
    }

    public function test_mentor_match_and_session_changes_notify_the_other_party(): void
    {
        $mentor = User::factory()->create(['user_type' => 'staff', 'status' => 'active']);
        $mentee = User::factory()->create();

        $this->actingAs($this->admin);
        $match = MentorMatch::create(['mentor_user_id' => $mentor->id, 'mentee_user_id' => $mentee->id, 'status' => 'active']);

        $this->assertCount(1, $this->notificationsFor($mentor, 'mentorship_match'));
        $this->assertCount(1, $this->notificationsFor($mentee, 'mentorship_match'));

        $this->actingAs($mentor);
        $session = MentorshipSession::create([
            'mentor_match_id' => $match->id, 'title' => 'Goals', 'scheduled_at' => now()->addDays(2), 'status' => 'scheduled',
        ]);
        $session->update(['scheduled_at' => now()->addDays(3)]);

        $this->assertCount(2, $this->notificationsFor($mentee, 'mentorship_session'));
        $this->assertCount(0, $this->notificationsFor($mentor, 'mentorship_session'));
    }

    public function test_job_application_status_change_notifies_the_applicant(): void
    {
        $applicant = User::factory()->create();
        $employerUser = User::factory()->create(['user_type' => 'staff', 'status' => 'active']);
        $employerId = DB::table('employers')->insertGetId(['owner_user_id' => $employerUser->id, 'company_name' => 'Acme', 'created_at' => now(), 'updated_at' => now()]);
        $jobId = DB::table('jobs')->insertGetId(['employer_id' => $employerId, 'title' => 'Analyst', 'status' => 'published', 'created_at' => now(), 'updated_at' => now()]);
        $application = \App\Models\JobApplication::create(['job_id' => $jobId, 'user_id' => $applicant->id, 'status' => 'submitted', 'applied_at' => now()]);

        $this->actingAs($employerUser);
        $application->update(['status' => 'interview']);

        $notice = $this->notificationsFor($applicant, 'job_application')->sole();
        $this->assertStringContainsString('interview', $notice->message);
    }

    /* ------------------------------------------------------------ Leave */

    public function test_leave_submission_and_decisions_notify_the_right_people(): void
    {
        $employeeUser = User::factory()->create(['user_type' => 'staff', 'status' => 'active']);
        $supervisor = User::factory()->create(['user_type' => 'staff', 'status' => 'active']);
        $hr = User::factory()->create(['user_type' => 'staff', 'status' => 'active']);
        $hrRole = Role::create(['name' => 'HR', 'slug' => 'hr']);
        $hrRole->permissions()->attach(Permission::create(['name' => 'Approve leave', 'slug' => 'leave.approve'])->id);
        $hr->roles()->attach($hrRole->id);

        Employee::create(['user_id' => $employeeUser->id, 'employee_number' => 'EMP-9', 'supervisor_user_id' => $supervisor->id]);
        $type = LeaveType::create(['name' => 'Annual Leave', 'code' => 'AL', 'default_days' => 21, 'is_active' => true]);

        $this->actingAs($employeeUser)->post('/hr/leave', [
            'leave_type_id' => $type->id,
            'start_date' => now()->addDays(7)->toDateString(),
            'end_date' => now()->addDays(9)->toDateString(),
        ])->assertSessionHas('success');

        $this->assertStringContainsString('Annual Leave', $this->notificationsFor($supervisor, 'leave')->sole()->message);
        $this->assertCount(0, $this->notificationsFor($employeeUser));

        $leave = LeaveRequest::firstOrFail();

        $this->actingAs($this->admin)->post("/admin/hr/leave/{$leave->id}/supervisor-approve");
        $this->assertSame('Leave approved by supervisor', $this->notificationsFor($employeeUser, 'leave')->sole()->title);
        $this->assertSame('Leave awaiting HR approval', $this->notificationsFor($hr, 'leave')->sole()->title);

        $this->actingAs($this->admin)->post("/admin/hr/leave/{$leave->id}/reject", ['decision_notes' => 'Busy period']);
        $rejected = $this->notificationsFor($employeeUser, 'leave')->firstWhere('title', 'Leave request rejected');
        $this->assertNotNull($rejected);
        $this->assertStringContainsString('Busy period', $rejected->message);
    }

    /* ------------------------------------------------------------ Appraisals */

    public function test_appraisal_workflow_notifies_whoever_acts_next(): void
    {
        $employeeUser = User::factory()->create(['user_type' => 'staff', 'status' => 'active']);
        $supervisor = User::factory()->create(['user_type' => 'staff', 'status' => 'active']);
        $employee = Employee::create(['user_id' => $employeeUser->id, 'employee_number' => 'EMP-1', 'supervisor_user_id' => $supervisor->id]);
        $cycle = AppraisalCycle::create(['name' => 'Annual 2026', 'start_date' => '2026-01-01', 'end_date' => '2026-12-31']);
        $appraisal = Appraisal::create([
            'appraisal_cycle_id' => $cycle->id, 'employee_id' => $employee->id,
            'manager_user_id' => $supervisor->id, 'status' => 'in_progress',
        ]);
        DB::table('user_notifications')->delete();

        $workflow = app(AppraisalWorkflowService::class);
        $titles = fn (User $u) => $this->notificationsFor($u, 'appraisal')->pluck('title')->all();

        $this->actingAs($employeeUser);
        $workflow->transition($appraisal, 'submitted');
        $this->assertSame(['Appraisal submitted for review'], $titles($supervisor));

        $this->actingAs($supervisor);
        $workflow->transition($appraisal->fresh(), 'returned_for_revision', 'Returned for revision: Add evidence');
        $returned = $this->notificationsFor($employeeUser, 'appraisal')->sole();
        $this->assertSame('Appraisal returned for revision', $returned->title);
        $this->assertStringContainsString('Add evidence', $returned->message);
        $this->assertStringContainsString('/staff/performance/'.$appraisal->id, $returned->action_url);

        $workflow->transition($appraisal->fresh(), 'meeting_pending');
        $workflow->transition($appraisal->fresh(), 'meeting_completed');
        $this->assertContains('Appraisal meeting pending', $titles($employeeUser));
        $this->assertContains('Confirm your appraisal', $titles($employeeUser));

        $this->actingAs($employeeUser);
        $workflow->transition($appraisal->fresh(), 'employee_confirmation');
        $this->assertContains('Appraisal confirmed by employee', $titles($supervisor));

        $this->actingAs($supervisor);
        $before = count($titles($employeeUser));
        $workflow->transition($appraisal->fresh(), 'supervisor_confirmation');
        $workflow->transition($appraisal->fresh(), 'completed');
        $this->assertSame($before + 1, count($titles($employeeUser)), 'Only "completed" is notified, not the transient step.');
        $this->assertContains('Appraisal completed', $titles($employeeUser));
        $this->assertNotContains('Appraisal completed', $titles($supervisor), 'The supervisor completed it themselves.');

        // HR lock and reopen.
        $this->actingAs($this->admin)
            ->post("/admin/hr/appraisals/{$appraisal->id}/lock", ['reason' => 'Year end'])
            ->assertSessionHas('success');
        $this->assertContains('Appraisal locked', $titles($employeeUser));
        $this->assertContains('Appraisal locked', $titles($supervisor));

        $this->actingAs($this->admin)
            ->post("/admin/hr/appraisals/{$appraisal->id}/reopen", ['reason' => 'Correction'])
            ->assertSessionHas('success');
        $this->assertSame(2, collect($titles($employeeUser))->filter(fn ($t) => $t === 'Appraisal returned for revision')->count());
    }
}
