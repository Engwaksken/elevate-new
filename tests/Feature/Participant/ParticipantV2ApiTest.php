<?php

namespace Tests\Feature\Participant;

use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\AssignmentExtensionRequest;
use App\Models\Course;
use App\Models\CourseModule;
use App\Models\Enrolment;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\MentorMatch;
use App\Models\MentorshipSession;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ParticipantV2ApiTest extends TestCase
{
    use RefreshDatabase;

    private const BASE = '/api/v1/participant';

    private Course $course;
    private CourseModule $module;
    private Lesson $lessonA;
    private Lesson $lessonB;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        Storage::fake('local');

        $this->course = Course::create(['title' => 'Digital Marketing', 'status' => 'published']);
        $this->module = CourseModule::create([
            'course_id' => $this->course->id, 'title' => 'Module 1', 'position' => 1, 'is_published' => true,
        ]);
        $this->lessonA = Lesson::create([
            'course_module_id' => $this->module->id, 'title' => 'Lesson A', 'content' => 'a',
            'content_type' => 'text', 'position' => 1, 'is_published' => true,
        ]);
        $this->lessonB = Lesson::create([
            'course_module_id' => $this->module->id, 'title' => 'Lesson B', 'content' => 'b',
            'content_type' => 'text', 'position' => 2, 'is_published' => true,
        ]);
    }

    private function participant(bool $enrolled = true): User
    {
        $user = User::factory()->create(['user_type' => 'participant', 'status' => 'active', 'phone' => '0700000001']);

        if ($enrolled) {
            Enrolment::create(['course_id' => $this->course->id, 'user_id' => $user->id, 'status' => 'enrolled']);
        }

        Sanctum::actingAs($user, ['participant']);

        return $user;
    }

    private function assessment(array $attributes = []): Assessment
    {
        return Assessment::create($attributes + [
            'course_id' => $this->course->id,
            'title' => 'Brief',
            'type' => 'assignment',
            'max_attempts' => 1,
            'due_at' => now()->addDays(7),
            'is_published' => true,
        ]);
    }

    private function staff(string $roleSlug): User
    {
        $user = User::factory()->create(['user_type' => 'staff', 'status' => 'active']);
        $role = Role::firstOrCreate(['slug' => $roleSlug], ['name' => ucfirst($roleSlug)]);
        $user->roles()->attach($role);

        return $user;
    }

    private function mentorSession(User $mentee, array $attributes = []): MentorshipSession
    {
        $mentor = User::factory()->create(['user_type' => 'staff', 'status' => 'active', 'name' => 'Mentor Mary']);
        $match = MentorMatch::create(['mentor_user_id' => $mentor->id, 'mentee_user_id' => $mentee->id, 'status' => 'active']);

        return MentorshipSession::create($attributes + [
            'mentor_match_id' => $match->id,
            'title' => 'Goal setting',
            'scheduled_at' => now()->subDay(),
            'duration_minutes' => 60,
            'meeting_link' => 'https://meet.example/abc',
            'status' => 'scheduled',
        ]);
    }

    // ---------------------------------------------------------------- Profile

    public function test_profile_get_returns_user_and_profile_fields(): void
    {
        $user = $this->participant();
        $user->profile()->create(['gender' => 'female', 'district' => 'Kisumu', 'date_of_birth' => '1998-04-12']);

        $this->getJson(self::BASE.'/profile')
            ->assertOk()
            ->assertJsonPath('profile.id', $user->id)
            ->assertJsonPath('profile.email', $user->email)
            ->assertJsonPath('profile.phone', '0700000001')
            ->assertJsonPath('profile.gender', 'female')
            ->assertJsonPath('profile.district', 'Kisumu')
            ->assertJsonPath('profile.date_of_birth', '1998-04-12')
            ->assertJsonPath('profile.photo_url', null)
            ->assertJsonPath('profile.has_photo', false)
            ->assertJsonPath('profile.is_pwd', false)
            ->assertJsonStructure(['profile' => ['name', 'education_level', 'employment_status', 'disability_types', 'branch'], 'editable_fields']);

        $editable = $this->getJson(self::BASE.'/profile')->json('editable_fields');
        $this->assertContains('phone', $editable);
        $this->assertContains('date_of_birth', $editable);
        $this->assertNotContains('email', $editable);
        $this->assertNotContains('branch_id', $editable);
    }

    public function test_profile_get_works_without_a_profile_row(): void
    {
        $this->participant();

        $this->getJson(self::BASE.'/profile')
            ->assertOk()
            ->assertJsonPath('profile.gender', null)
            ->assertJsonPath('profile.disability_types', []);
    }

    public function test_profile_update_changes_only_editable_fields(): void
    {
        $user = $this->participant();
        $originalEmail = $user->email;

        $this->putJson(self::BASE.'/profile', [
            'name' => 'Jane Achieng',
            'phone' => '0711111111',
            'email' => 'hacker@example.com',
            'user_type' => 'staff',
            'gender' => 'female',
            'date_of_birth' => '1999-01-31',
            'education_level' => 'Diploma',
            'employment_status' => 'Self-employed',
            'is_pwd' => true,
            'disability_types' => ['visual', ' '],
        ])
            ->assertOk()
            ->assertJsonPath('profile.name', 'Jane Achieng')
            ->assertJsonPath('profile.phone', '0711111111')
            ->assertJsonPath('profile.email', $originalEmail)
            ->assertJsonPath('profile.user_type', 'participant')
            ->assertJsonPath('profile.date_of_birth', '1999-01-31')
            ->assertJsonPath('profile.is_pwd', true)
            ->assertJsonPath('profile.disability_types', ['visual']);

        $this->assertDatabaseHas('users', ['id' => $user->id, 'email' => $originalEmail, 'user_type' => 'participant']);
        $this->assertDatabaseHas('profiles', ['user_id' => $user->id, 'education_level' => 'Diploma']);

        // Partial update keeps other fields.
        $this->putJson(self::BASE.'/profile', ['district' => 'Kisumu'])
            ->assertOk()
            ->assertJsonPath('profile.district', 'Kisumu')
            ->assertJsonPath('profile.education_level', 'Diploma');
    }

    public function test_profile_update_validation_errors(): void
    {
        $this->participant();

        $this->putJson(self::BASE.'/profile', [
            'name' => '',
            'gender' => 'robot',
            'date_of_birth' => now()->addYear()->toDateString(),
            'disability_types' => 'not-an-array',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'gender', 'date_of_birth', 'disability_types']);
    }

    public function test_profile_photo_upload_stream_and_delete(): void
    {
        $user = $this->participant();

        $response = $this->post(self::BASE.'/profile/photo', [
            'photo' => UploadedFile::fake()->image('me.jpg', 200, 200),
        ], ['Accept' => 'application/json'])->assertOk()->assertJsonPath('profile.has_photo', true);

        $photoUrl = $response->json('profile.photo_url');
        $this->assertStringContainsString('/api/v1/participant/profile/photo', $photoUrl);
        $path = $user->fresh()->profile->photo_path;
        Storage::disk('local')->assertExists($path);

        $stream = $this->get(self::BASE.'/profile/photo');
        $stream->assertOk();
        $this->assertStringContainsString('inline', $stream->headers->get('Content-Disposition'));

        // Replacing deletes the previous file.
        $this->post(self::BASE.'/profile/photo', ['photo' => UploadedFile::fake()->image('new.png')], ['Accept' => 'application/json'])
            ->assertOk();
        Storage::disk('local')->assertMissing($path);

        $this->deleteJson(self::BASE.'/profile/photo')
            ->assertOk()
            ->assertJsonPath('profile.photo_url', null);
        $this->getJson(self::BASE.'/profile/photo')->assertNotFound()->assertJsonPath('message', 'No profile photo.');
    }

    public function test_profile_photo_validation(): void
    {
        $this->participant();

        $this->post(self::BASE.'/profile/photo', [
            'photo' => UploadedFile::fake()->create('doc.pdf', 10, 'application/pdf'),
        ], ['Accept' => 'application/json'])->assertStatus(422)->assertJsonValidationErrors(['photo']);

        $this->post(self::BASE.'/profile/photo', [
            'photo' => UploadedFile::fake()->image('big.jpg')->size(6000),
        ], ['Accept' => 'application/json'])->assertStatus(422)->assertJsonValidationErrors(['photo']);
    }

    public function test_password_change(): void
    {
        // The project has no personal_access_tokens migration of its own; use Sanctum's for this test.
        if (! \Illuminate\Support\Facades\Schema::hasTable('personal_access_tokens')) {
            (include base_path('vendor/laravel/sanctum/database/migrations/2019_12_14_000001_create_personal_access_tokens_table.php'))->up();
        }

        $user = User::factory()->create([
            'user_type' => 'participant', 'status' => 'active', 'password' => Hash::make('old-password-1'),
        ]);
        $current = $user->createToken('phone')->plainTextToken;
        $user->createToken('tablet');

        $this->withToken($current)->putJson(self::BASE.'/profile/password', [
            'current_password' => 'wrong-password',
            'password' => 'new-password-1',
            'password_confirmation' => 'new-password-1',
        ])->assertStatus(422)->assertJsonValidationErrors(['current_password']);

        $this->withToken($current)->putJson(self::BASE.'/profile/password', [
            'current_password' => 'old-password-1',
            'password' => 'short',
            'password_confirmation' => 'short',
        ])->assertStatus(422)->assertJsonValidationErrors(['password']);

        $this->withToken($current)->putJson(self::BASE.'/profile/password', [
            'current_password' => 'old-password-1',
            'password' => 'new-password-1',
            'password_confirmation' => 'new-password-1',
        ])->assertNoContent();

        $this->assertTrue(Hash::check('new-password-1', $user->fresh()->password));
        $this->assertSame(1, $user->tokens()->count(), 'Other tokens are revoked, the current one is kept.');
    }

    // ---------------------------------------------------------------- Progress

    public function test_progress_summary_counts(): void
    {
        $user = $this->participant();

        // Unpublished lesson and a lesson in an unpublished module don't count.
        Lesson::create(['course_module_id' => $this->module->id, 'title' => 'Draft', 'content_type' => 'text', 'position' => 3, 'is_published' => false]);
        $hidden = CourseModule::create(['course_id' => $this->course->id, 'title' => 'Hidden', 'position' => 2, 'is_published' => false]);
        Lesson::create(['course_module_id' => $hidden->id, 'title' => 'Hidden lesson', 'content_type' => 'text', 'position' => 1, 'is_published' => true]);

        LessonProgress::create(['lesson_id' => $this->lessonA->id, 'user_id' => $user->id, 'completed_at' => now()->subHour(), 'time_spent_seconds' => 300]);
        LessonProgress::create(['lesson_id' => $this->lessonB->id, 'user_id' => $user->id, 'time_spent_seconds' => 120]);

        // Assessments: submitted+graded, submitted, overdue, pending, overdue-with-pending-extension, unpublished.
        $graded = $this->assessment(['title' => 'Graded']);
        AssessmentAttempt::create(['assessment_id' => $graded->id, 'user_id' => $user->id, 'status' => 'graded', 'submitted_at' => now()->subMinutes(30)]);
        $submitted = $this->assessment(['title' => 'Submitted', 'due_at' => now()->subDay()]);
        AssessmentAttempt::create(['assessment_id' => $submitted->id, 'user_id' => $user->id, 'status' => 'submitted', 'submitted_at' => now()->subDays(2)]);
        $this->assessment(['title' => 'Overdue', 'due_at' => now()->subDay()]);
        $this->assessment(['title' => 'Pending', 'due_at' => now()->addDay()]);
        $withRequest = $this->assessment(['title' => 'Late', 'due_at' => now()->subHours(2)]);
        AssignmentExtensionRequest::create(['assessment_id' => $withRequest->id, 'user_id' => $user->id, 'reason' => 'I was unwell for days']);
        $this->assessment(['title' => 'Draft', 'is_published' => false]);

        // Mentorship: attended, missed, upcoming, no answer yet.
        $session = $this->mentorSession($user, ['mentee_attended' => true, 'status' => 'completed']);
        $matchId = $session->mentor_match_id;
        MentorshipSession::create(['mentor_match_id' => $matchId, 'title' => 'Missed', 'scheduled_at' => now()->subDays(3), 'status' => 'missed']);
        MentorshipSession::create(['mentor_match_id' => $matchId, 'title' => 'Next', 'scheduled_at' => now()->addDays(3), 'status' => 'scheduled']);
        MentorshipSession::create(['mentor_match_id' => $matchId, 'title' => 'Unknown', 'scheduled_at' => now()->subDays(2), 'status' => 'scheduled']);

        $eventId = DB::table('events')->insertGetId(['title' => 'Expo', 'starts_at' => now()->subDays(5), 'is_published' => true, 'created_at' => now(), 'updated_at' => now()]);
        DB::table('event_attendance_records')->insert(['event_id' => $eventId, 'user_id' => $user->id, 'attendance_status' => 'present', 'created_at' => now(), 'updated_at' => now()]);

        $response = $this->getJson(self::BASE.'/progress')->assertOk();

        $response->assertJson(['summary' => [
            'courses_enrolled' => 1,
            'courses_completed' => 0,
            'lessons_total' => 2,
            'lessons_completed' => 1,
            'time_spent_seconds' => 420,
            'assignments_total' => 5,
            'assignments_submitted' => 2,
            'assignments_graded' => 1,
            'assignments_pending' => 1,
            'assignments_overdue' => 2,
            'extension_requests_pending' => 1,
            'mentorship_sessions_total' => 4,
            'mentorship_sessions_attended' => 1,
            'mentorship_sessions_missed' => 1,
            'mentorship_sessions_upcoming' => 1,
            'events_attended' => 1,
        ]]);

        $response->assertJsonPath('courses.0.id', $this->course->id)
            ->assertJsonPath('courses.0.lessons_total', 2)
            ->assertJsonPath('courses.0.lessons_completed', 1)
            ->assertJsonPath('courses.0.time_spent_seconds', 420);
        $this->assertEquals(50, $response->json('courses.0.progress_percent'));
        $this->assertNotNull($response->json('courses.0.last_activity_at'));

        $types = collect($response->json('recent_activity'))->pluck('type')->all();
        $this->assertContains('lesson_completed', $types);
        $this->assertContains('assignment_submitted', $types);
        $this->assertContains('session_attended', $types);
        $this->assertSame('assignment_submitted', $types[0], 'Newest first (graded attempt 30 min ago).');
    }

    // ---------------------------------------------------------------- Reading time

    public function test_time_delta_accumulates_with_or_without_completed(): void
    {
        $user = $this->participant();

        $this->putJson(self::BASE."/lessons/{$this->lessonA->id}/progress", ['completed' => true, 'time_spent_seconds_delta' => 30])
            ->assertOk()
            ->assertJsonPath('time_spent_seconds', 30)
            ->assertJsonPath('completed', true);

        // No "completed": completion is kept, time is added.
        $this->putJson(self::BASE."/lessons/{$this->lessonA->id}/progress", ['time_spent_seconds_delta' => 45])
            ->assertOk()
            ->assertJsonPath('time_spent_seconds', 75)
            ->assertJsonPath('time_spent_seconds_added', 45)
            ->assertJsonPath('completed', true);

        $this->postJson(self::BASE."/lessons/{$this->lessonB->id}/progress", ['time_spent_seconds_delta' => 10])
            ->assertOk()
            ->assertJsonPath('completed', false)
            ->assertJsonPath('time_spent_seconds', 10);

        $this->assertSame(75, (int) LessonProgress::where('lesson_id', $this->lessonA->id)->where('user_id', $user->id)->value('time_spent_seconds'));
    }

    public function test_time_delta_is_clamped_and_negative_rejected(): void
    {
        $this->participant();

        $this->putJson(self::BASE."/lessons/{$this->lessonA->id}/progress", ['time_spent_seconds_delta' => 99999])
            ->assertOk()
            ->assertJsonPath('time_spent_seconds_added', 3600)
            ->assertJsonPath('time_spent_seconds', 3600);

        $this->putJson(self::BASE."/lessons/{$this->lessonA->id}/progress", ['time_spent_seconds_delta' => -5])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['time_spent_seconds_delta']);

        // Delta wins over the legacy field (no double counting).
        $this->putJson(self::BASE."/lessons/{$this->lessonA->id}/progress", ['time_spent_seconds_delta' => 5, 'time_spent_seconds' => 500])
            ->assertOk()
            ->assertJsonPath('time_spent_seconds', 3605);

        // Legacy additive field still works on its own.
        $this->putJson(self::BASE."/lessons/{$this->lessonA->id}/progress", ['completed' => false, 'time_spent_seconds' => 5])
            ->assertOk()
            ->assertJsonPath('time_spent_seconds', 3610);
    }

    public function test_time_delta_is_idempotent_with_client_operation_id(): void
    {
        $this->participant();

        $body = ['time_spent_seconds_delta' => 60, 'client_operation_id' => 'flush-1'];

        $this->putJson(self::BASE."/lessons/{$this->lessonA->id}/progress", $body)->assertOk()->assertJsonPath('time_spent_seconds', 60);
        $this->putJson(self::BASE."/lessons/{$this->lessonA->id}/progress", $body)
            ->assertOk()
            ->assertJsonPath('duplicate', true)
            ->assertJsonPath('time_spent_seconds', 60);

        $this->assertSame(60, (int) LessonProgress::where('lesson_id', $this->lessonA->id)->value('time_spent_seconds'));
    }

    public function test_offline_lesson_progress_accepts_delta(): void
    {
        $user = $this->participant();

        $this->postJson(self::BASE.'/offline-actions', ['operations' => [
            ['client_operation_id' => 'op-1', 'type' => 'lesson_progress', 'payload' => ['lesson_id' => $this->lessonA->id, 'completed' => true, 'time_spent_seconds_delta' => 20]],
            ['client_operation_id' => 'op-2', 'type' => 'lesson_progress', 'payload' => ['lesson_id' => $this->lessonA->id, 'time_spent_seconds_delta' => 5000]],
            ['client_operation_id' => 'op-2', 'type' => 'lesson_progress', 'payload' => ['lesson_id' => $this->lessonA->id, 'time_spent_seconds_delta' => 5000]],
            ['client_operation_id' => 'op-3', 'type' => 'lesson_progress', 'payload' => ['lesson_id' => $this->lessonA->id, 'time_spent_seconds_delta' => -1]],
        ]])
            ->assertOk()
            ->assertJsonPath('results.0.status', 'processed')
            ->assertJsonPath('results.1.status', 'processed')
            ->assertJsonPath('results.1.result.completed', true)
            ->assertJsonPath('results.2.status', 'duplicate')
            ->assertJsonPath('results.3.status', 'failed')
            ->assertJsonPath('results.3.code', 422);

        $this->assertSame(3620, (int) LessonProgress::where('lesson_id', $this->lessonA->id)->where('user_id', $user->id)->value('time_spent_seconds'));
    }

    // ---------------------------------------------------------------- Mentorship

    public function test_mentorship_sessions_include_attendance_fields(): void
    {
        $user = $this->participant();
        $this->mentorSession($user);

        $this->getJson(self::BASE.'/mentorship')
            ->assertOk()
            ->assertJsonPath('sessions.0.title', 'Goal setting')
            ->assertJsonPath('sessions.0.mentor_name', 'Mentor Mary')
            ->assertJsonPath('sessions.0.duration_minutes', 60)
            ->assertJsonPath('sessions.0.meeting_link', 'https://meet.example/abc')
            ->assertJsonPath('sessions.0.mentee_attended', null)
            ->assertJsonPath('sessions.0.mentor_attended', null)
            ->assertJsonPath('sessions.0.can_confirm_attendance', true)
            ->assertJsonStructure(['sessions' => [['id', 'scheduled_at', 'status', 'venue']]]);
    }

    public function test_mentee_confirms_attendance_without_changing_status(): void
    {
        $user = $this->participant();
        $session = $this->mentorSession($user);

        $this->postJson(self::BASE."/mentorship/sessions/{$session->id}/attendance", ['attended' => true])
            ->assertOk()
            ->assertJsonPath('session.id', $session->id)
            ->assertJsonPath('session.mentee_attended', true)
            ->assertJsonPath('session.status', 'scheduled');

        $this->assertSame('scheduled', $session->fresh()->status);
        $this->assertTrue($session->fresh()->mentee_attended);

        $this->postJson(self::BASE."/mentorship/sessions/{$session->id}/attendance", ['attended' => false])
            ->assertOk()
            ->assertJsonPath('session.mentee_attended', false);

        $this->postJson(self::BASE."/mentorship/sessions/{$session->id}/attendance", [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['attended']);
    }

    public function test_attendance_rules(): void
    {
        $user = $this->participant();

        $future = $this->mentorSession($user, ['scheduled_at' => now()->addHour()]);
        $this->postJson(self::BASE."/mentorship/sessions/{$future->id}/attendance", ['attended' => true])
            ->assertStatus(422)->assertJsonPath('code', 'session_not_started');

        $old = $this->mentorSession($user, ['scheduled_at' => now()->subDays(15)]);
        $this->postJson(self::BASE."/mentorship/sessions/{$old->id}/attendance", ['attended' => true])
            ->assertStatus(422)->assertJsonPath('code', 'attendance_window_closed');

        $cancelled = $this->mentorSession($user, ['status' => 'cancelled']);
        $this->postJson(self::BASE."/mentorship/sessions/{$cancelled->id}/attendance", ['attended' => true])
            ->assertStatus(422)->assertJsonPath('code', 'session_cancelled');

        $someoneElse = User::factory()->create(['user_type' => 'participant', 'status' => 'active']);
        $other = $this->mentorSession($someoneElse);
        $this->postJson(self::BASE."/mentorship/sessions/{$other->id}/attendance", ['attended' => true])
            ->assertForbidden()->assertJsonPath('message', 'You are not the mentee for this session.');

        $this->postJson(self::BASE.'/mentorship/sessions/999999/attendance', ['attended' => true])
            ->assertNotFound()->assertJsonPath('message', 'Mentorship session not found.');
    }

    // ---------------------------------------------------------------- Overdue + extensions

    public function test_submission_blocked_when_overdue(): void
    {
        $this->participant();
        $assessment = $this->assessment(['due_at' => now()->subMinute()]);

        $this->postJson(self::BASE."/assignments/{$assessment->id}/submit", ['submission_text' => 'late'])
            ->assertStatus(422)
            ->assertJsonPath('code', 'overdue')
            ->assertJsonPath('message', 'This assignment is past its due date. Request an extension from your instructor.');

        $open = $this->assessment(['due_at' => now()->addDay()]);
        $this->postJson(self::BASE."/assignments/{$open->id}/submit", ['submission_text' => 'on time'])->assertCreated();
        $this->postJson(self::BASE."/assignments/{$open->id}/submit", ['submission_text' => 'again'])
            ->assertStatus(422)->assertJsonPath('code', 'max_attempts_reached');
    }

    public function test_offline_grace_window_on_submit_endpoint(): void
    {
        $this->participant();
        $assessment = $this->assessment(['due_at' => now()->subHours(10), 'max_attempts' => 5]);

        // Made offline before the deadline, received within 72h: accepted.
        $this->postJson(self::BASE."/assignments/{$assessment->id}/submit", [
            'submission_text' => 'queued', 'client_submission_id' => 'sub-1',
            'client_created_at' => now()->subHours(11)->toIso8601String(),
        ])->assertCreated();

        // Made after the deadline: rejected.
        $this->postJson(self::BASE."/assignments/{$assessment->id}/submit", [
            'submission_text' => 'queued', 'client_submission_id' => 'sub-2',
            'client_created_at' => now()->subHours(9)->toIso8601String(),
        ])->assertStatus(422)->assertJsonPath('code', 'overdue');

        // Client timestamp is ignored without a client_submission_id (not a queued submission).
        $this->postJson(self::BASE."/assignments/{$assessment->id}/submit", [
            'submission_text' => 'direct', 'client_created_at' => now()->subHours(11)->toIso8601String(),
        ])->assertStatus(422)->assertJsonPath('code', 'overdue');

        // Made before the deadline but received more than 72h later: rejected.
        $old = $this->assessment(['due_at' => now()->subDays(4)]);
        $this->postJson(self::BASE."/assignments/{$old->id}/submit", [
            'submission_text' => 'very late', 'client_submission_id' => 'sub-3',
            'client_created_at' => now()->subHours(73)->subDays(1)->toIso8601String(),
        ])->assertStatus(422)->assertJsonPath('code', 'overdue');
    }

    public function test_offline_actions_assignment_submission_uses_grace_window(): void
    {
        $user = $this->participant();
        $assessment = $this->assessment(['due_at' => now()->subHours(2)]);
        $late = $this->assessment(['title' => 'Late', 'due_at' => now()->subHours(2)]);

        $this->postJson(self::BASE.'/offline-actions', ['operations' => [
            ['client_operation_id' => 'sub-op-1', 'type' => 'assignment_submission', 'client_created_at' => now()->subHours(3)->toIso8601String(),
             'payload' => ['assessment_id' => $assessment->id, 'submission_text' => 'offline answer']],
            ['client_operation_id' => 'sub-op-2', 'type' => 'assignment_submission',
             'payload' => ['assessment_id' => $late->id, 'submission_text' => 'late', 'client_created_at' => now()->subHour()->toIso8601String()]],
        ]])
            ->assertOk()
            ->assertJsonPath('results.0.status', 'processed')
            ->assertJsonPath('results.0.result.message', 'Submission received.')
            ->assertJsonPath('results.1.status', 'failed')
            ->assertJsonPath('results.1.code', 422)
            ->assertJsonPath('results.1.error_code', 'overdue');

        $this->assertDatabaseHas('assessment_attempts', [
            'assessment_id' => $assessment->id, 'user_id' => $user->id, 'client_submission_id' => 'sub-op-1', 'submission_text' => 'offline answer',
        ]);
    }

    public function test_assignment_list_includes_deadline_fields(): void
    {
        $user = $this->participant();
        $overdue = $this->assessment(['title' => 'Overdue', 'due_at' => now()->subDay()]);
        $open = $this->assessment(['title' => 'Open', 'due_at' => now()->addDays(10)]);
        $request = AssignmentExtensionRequest::create(['assessment_id' => $overdue->id, 'user_id' => $user->id, 'reason' => 'Network was down all week']);

        $items = collect($this->getJson(self::BASE.'/assignments')->assertOk()->json('data'))->keyBy('id');

        $this->assertTrue($items[$overdue->id]['is_overdue']);
        $this->assertFalse($items[$overdue->id]['can_submit']);
        $this->assertFalse($items[$overdue->id]['can_request_extension'], 'A request is already pending.');
        $this->assertSame($request->id, $items[$overdue->id]['extension_request']['id']);
        $this->assertSame('pending', $items[$overdue->id]['extension_request']['status']);
        $this->assertSame(0, $items[$overdue->id]['submissions_count']);

        $this->assertFalse($items[$open->id]['is_overdue']);
        $this->assertTrue($items[$open->id]['can_submit']);
        $this->assertNull($items[$open->id]['extension_request']);
        $this->assertNotNull($items[$open->id]['effective_due_at']);

        $synced = collect($this->getJson(self::BASE.'/sync')->assertOk()->json('assignments'))->keyBy('id');
        $this->assertTrue($synced[$overdue->id]['is_overdue']);

        $course = collect($this->getJson(self::BASE."/courses/{$this->course->id}")->assertOk()->json('course.assessments'))->keyBy('id');
        $this->assertArrayHasKey('can_submit', $course[$open->id]);
    }

    public function test_extension_request_creation_rules(): void
    {
        $instructor = $this->staff('instructor');
        $this->course->instructors()->attach($instructor->id);
        $user = $this->participant();

        $farAway = $this->assessment(['due_at' => now()->addDays(5)]);
        $this->postJson(self::BASE."/assignments/{$farAway->id}/extension-requests", ['reason' => 'I need more time please'])
            ->assertStatus(422)->assertJsonPath('code', 'extension_not_needed');

        $overdue = $this->assessment(['due_at' => now()->subDay()]);
        $this->postJson(self::BASE."/assignments/{$overdue->id}/extension-requests", ['reason' => 'short'])
            ->assertStatus(422)->assertJsonValidationErrors(['reason']);
        $this->postJson(self::BASE."/assignments/{$overdue->id}/extension-requests", [
            'reason' => 'I was in hospital last week', 'requested_due_at' => now()->subDay()->toIso8601String(),
        ])->assertStatus(422)->assertJsonValidationErrors(['requested_due_at']);

        $requested = now()->addDays(2)->startOfMinute();
        $this->postJson(self::BASE."/assignments/{$overdue->id}/extension-requests", [
            'reason' => 'I was in hospital last week', 'requested_due_at' => $requested->toIso8601String(),
        ])
            ->assertCreated()
            ->assertJsonPath('extension_request.status', 'pending')
            ->assertJsonPath('extension_request.reason', 'I was in hospital last week')
            ->assertJsonPath('extension_request.requested_due_at', $requested->toIso8601String());

        $this->assertDatabaseHas('user_notifications', ['user_id' => $instructor->id, 'type' => 'assignment_extension_requested']);

        $this->postJson(self::BASE."/assignments/{$overdue->id}/extension-requests", ['reason' => 'Asking once more please'])
            ->assertStatus(422)->assertJsonPath('code', 'extension_pending');

        $dueSoon = $this->assessment(['due_at' => now()->addHours(20)]);
        $this->postJson(self::BASE."/assignments/{$dueSoon->id}/extension-requests", ['reason' => 'Due soon and I am travelling'])
            ->assertCreated();

        $usedUp = $this->assessment(['due_at' => now()->subDay()]);
        AssessmentAttempt::create(['assessment_id' => $usedUp->id, 'user_id' => $user->id, 'status' => 'submitted', 'submitted_at' => now()->subDays(2)]);
        $this->postJson(self::BASE."/assignments/{$usedUp->id}/extension-requests", ['reason' => 'I want to resubmit it'])
            ->assertStatus(422)->assertJsonPath('code', 'max_attempts_reached');

        $draft = $this->assessment(['due_at' => now()->subDay(), 'is_published' => false]);
        $this->postJson(self::BASE."/assignments/{$draft->id}/extension-requests", ['reason' => 'I need more time please'])
            ->assertNotFound();

        $otherCourse = Course::create(['title' => 'Other', 'status' => 'published']);
        $foreign = Assessment::create(['course_id' => $otherCourse->id, 'title' => 'X', 'type' => 'assignment', 'max_attempts' => 1, 'due_at' => now()->subDay(), 'is_published' => true]);
        $this->postJson(self::BASE."/assignments/{$foreign->id}/extension-requests", ['reason' => 'I need more time please'])
            ->assertForbidden();
    }

    // ---------------------------------------------------------------- Instructor review

    private function pendingRequest(User $participant, Assessment $assessment): AssignmentExtensionRequest
    {
        return AssignmentExtensionRequest::create([
            'assessment_id' => $assessment->id, 'user_id' => $participant->id,
            'reason' => 'My laptop was stolen', 'requested_due_at' => now()->addDays(2),
        ]);
    }

    public function test_instructor_approval_allows_submission_after_due_date(): void
    {
        $instructor = $this->staff('instructor');
        $this->course->instructors()->attach($instructor->id);
        $participant = $this->participant();
        $assessment = $this->assessment(['due_at' => now()->subDay()]);
        $request = $this->pendingRequest($participant, $assessment);

        $newDue = now()->addDays(3)->startOfMinute();

        $this->actingAs($instructor)
            ->post(route('instructor.courses.extension-requests.approve', [$this->course, $request]), [
                'approved_due_at' => $newDue->format('Y-m-d\TH:i'),
                'reviewer_note' => 'Get well soon',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $request->refresh();
        $this->assertSame('approved', $request->status);
        $this->assertSame($instructor->id, (int) $request->reviewed_by);
        $this->assertTrue($request->approved_due_at->equalTo($newDue));
        $this->assertDatabaseHas('user_notifications', ['user_id' => $participant->id, 'type' => 'assignment_extension_approved']);

        Sanctum::actingAs($participant, ['participant']);

        $item = collect($this->getJson(self::BASE.'/assignments')->json('data'))->firstWhere('id', $assessment->id);
        $this->assertFalse($item['is_overdue']);
        $this->assertTrue($item['can_submit']);
        $this->assertSame($newDue->toIso8601String(), $item['effective_due_at']);
        $this->assertSame('approved', $item['extension_request']['status']);

        $this->postJson(self::BASE."/assignments/{$assessment->id}/submit", ['submission_text' => 'finally'])->assertCreated();
    }

    public function test_approve_requires_future_date_and_pending_status(): void
    {
        $instructor = $this->staff('instructor');
        $this->course->instructors()->attach($instructor->id);
        $participant = User::factory()->create(['user_type' => 'participant', 'status' => 'active']);
        $request = $this->pendingRequest($participant, $this->assessment(['due_at' => now()->subDay()]));

        $this->actingAs($instructor)
            ->post(route('instructor.courses.extension-requests.approve', [$this->course, $request]), [
                'approved_due_at' => now()->subHour()->format('Y-m-d\TH:i'),
            ])
            ->assertSessionHasErrors(['approved_due_at']);

        $this->actingAs($instructor)
            ->post(route('instructor.courses.extension-requests.approve', [$this->course, $request]), [])
            ->assertSessionHasErrors(['approved_due_at']);

        $this->assertSame('pending', $request->fresh()->status);

        $request->update(['status' => 'rejected']);
        $this->actingAs($instructor)
            ->post(route('instructor.courses.extension-requests.approve', [$this->course, $request]), [
                'approved_due_at' => now()->addDay()->format('Y-m-d\TH:i'),
            ])
            ->assertSessionHas('error');
        $this->assertSame('rejected', $request->fresh()->status);
    }

    public function test_reject_with_note_notifies_participant(): void
    {
        $instructor = $this->staff('trainer');
        $this->course->instructors()->attach($instructor->id);
        $participant = User::factory()->create(['user_type' => 'participant', 'status' => 'active']);
        $assessment = $this->assessment(['due_at' => now()->subDay()]);
        $request = $this->pendingRequest($participant, $assessment);

        $this->actingAs($instructor)
            ->post(route('instructor.courses.extension-requests.reject', [$this->course, $request]), ['reviewer_note' => 'Too late, sorry'])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame('rejected', $request->fresh()->status);
        $this->assertSame('Too late, sorry', $request->fresh()->reviewer_note);
        $this->assertDatabaseHas('user_notifications', ['user_id' => $participant->id, 'type' => 'assignment_extension_rejected']);

        Sanctum::actingAs($participant, ['participant']);
        Enrolment::create(['course_id' => $this->course->id, 'user_id' => $participant->id, 'status' => 'enrolled']);
        $this->postJson(self::BASE."/assignments/{$assessment->id}/submit", ['submission_text' => 'x'])
            ->assertStatus(422)->assertJsonPath('code', 'overdue');
    }

    public function test_extension_review_authorisation(): void
    {
        $participant = User::factory()->create(['user_type' => 'participant', 'status' => 'active']);
        $request = $this->pendingRequest($participant, $this->assessment(['due_at' => now()->subDay()]));
        $body = ['approved_due_at' => now()->addDays(2)->format('Y-m-d\TH:i')];

        // Instructor of a different course.
        $outsider = $this->staff('instructor');
        $otherCourse = Course::create(['title' => 'Other', 'status' => 'published']);
        $otherCourse->instructors()->attach($outsider->id);
        $this->actingAs($outsider)
            ->post(route('instructor.courses.extension-requests.approve', [$this->course, $request]), $body)
            ->assertForbidden();

        // Request doesn't belong to the course in the URL.
        $this->actingAs($outsider)
            ->post(route('instructor.courses.extension-requests.reject', [$otherCourse, $request]))
            ->assertNotFound();

        // Participant accounts are not staff.
        $this->actingAs($participant)
            ->post(route('instructor.courses.extension-requests.approve', [$this->course, $request]), $body)
            ->assertForbidden();

        $this->assertSame('pending', $request->fresh()->status);

        // An administrator may review any course.
        $admin = $this->staff('administrator');
        $this->actingAs($admin)
            ->post(route('instructor.courses.extension-requests.approve', [$this->course, $request]), $body)
            ->assertRedirect();
        $this->assertSame('approved', $request->fresh()->status);
    }

    public function test_instructor_workspace_lists_extension_requests_with_badge(): void
    {
        $instructor = $this->staff('instructor');
        $this->course->instructors()->attach($instructor->id);
        $participant = User::factory()->create(['user_type' => 'participant', 'status' => 'active', 'name' => 'Grace Participant']);
        $this->pendingRequest($participant, $this->assessment(['title' => 'Business Plan', 'due_at' => now()->subDay()]));

        $this->actingAs($instructor)
            ->get(route('instructor.courses.manage', ['course' => $this->course, 'tab' => 'extensions']))
            ->assertOk()
            ->assertSee('Extension Requests')
            ->assertSee('Grace Participant')
            ->assertSee('Business Plan')
            ->assertSee('My laptop was stolen')
            ->assertSee('icm-badge', false);

        $this->actingAs($instructor)
            ->get(route('instructor.courses.manage', ['course' => $this->course, 'tab' => 'submissions']))
            ->assertOk()
            ->assertSee('pending extension request', false);
    }

    public function test_assignments_report_graded_state_and_latest_submission(): void
    {
        $user = $this->participant();
        $graded = $this->assessment(['title' => 'Graded brief']);
        AssessmentAttempt::create([
            'assessment_id' => $graded->id, 'user_id' => $user->id, 'status' => 'graded',
            'score' => 18, 'percentage' => 90, 'submitted_at' => now()->subDay(), 'graded_at' => now(),
        ]);
        $submitted = $this->assessment(['title' => 'Submitted brief']);
        AssessmentAttempt::create([
            'assessment_id' => $submitted->id, 'user_id' => $user->id, 'status' => 'submitted',
            'score' => 5, 'submitted_at' => now()->subHour(),
        ]);
        $untouched = $this->assessment(['title' => 'Not started']);

        $items = collect($this->getJson(self::BASE.'/assignments')->assertOk()->json('data'))->keyBy('id');

        $this->assertTrue($items[$graded->id]['is_graded']);
        $this->assertSame('graded', $items[$graded->id]['latest_submission']['status']);
        $this->assertEquals(90, $items[$graded->id]['latest_submission']['percentage']);

        // A score saved before grading must not leak to the participant.
        $this->assertFalse($items[$submitted->id]['is_graded']);
        $this->assertNull($items[$submitted->id]['latest_submission']['score']);

        $this->assertFalse($items[$untouched->id]['is_graded']);
        $this->assertNull($items[$untouched->id]['latest_submission']);
    }
}
