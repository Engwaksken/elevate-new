<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Event;
use App\Models\CourseFeedbackResponse;
use App\Models\EventFeedbackResponse;
use App\Models\Role;
use App\Models\User;
use App\Models\Workplan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FeedbackAndWorkplanFilesTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $type = 'participant'): User
    {
        return User::factory()->create(['user_type'=>$type, 'status'=>'active', 'email_verified_at'=>now()]);
    }

    private function role(User $user, string $slug): void
    {
        $role = Role::create(['name'=>$slug, 'slug'=>$slug, 'is_system'=>false]);
        $user->roles()->attach($role);
    }

    private function course(): Course
    {
        return Course::create(['title'=>'Feedback course', 'status'=>'published']);
    }

    private function event(bool $published = true): Event
    {
        return Event::create(['title'=>'Feedback event', 'starts_at'=>now()->addDay(), 'is_published'=>$published, 'feedback_enabled'=>true]);
    }

    private function workplan(User $owner, string $status = 'draft', ?string $path = null): Workplan
    {
        return Workplan::create(['title'=>'Plan', 'period_type'=>'annual', 'status'=>$status, 'created_by'=>$owner->id, 'source_file_path'=>$path, 'source_file_name'=>'plan.csv']);
    }

    public function test_feedback_staff_role_allowlist_includes_canonical_and_legacy_roles_and_denies_unlisted(): void
    {
        foreach (['administrator','super-administrator','programs-lead','program-officer','operations-lead','communications-lead','admin','communication-lead','comms-lead','program-lead','programs-officer','operations'] as $slug) {
            $user = $this->user('staff'); $this->role($user, $slug);
            $this->actingAs($user)->get(route('admin.participant-feedback.index'))->assertOk();
        }
        $user = $this->user('staff'); $this->role($user, 'unlisted-role');
        $this->actingAs($user)->get(route('admin.participant-feedback.index'))->assertForbidden();
    }

    public function test_feedback_admin_page_rejects_guest(): void
    {
        $this->get(route('admin.participant-feedback.index'))->assertRedirect(route('login'));
    }

    public function test_course_feedback_is_enrolment_gated_and_submission_can_be_edited(): void
    {
        $participant = $this->user(); $course = $this->course();
        $this->actingAs($participant)->get(route('courses.feedback', $course))->assertNotFound();
        $enrolment = $participant->enrolments()->create(['course_id'=>$course->id, 'status'=>'enrolled', 'enrolled_at'=>now()]);
        $payload = ['overall_rating'=>4,'content_rating'=>4,'instructor_rating'=>5,'recommend_rating'=>4,'key_learning'=>'First'];
        $this->actingAs($participant)->get(route('courses.feedback', $course))->assertOk();
        $this->actingAs($participant)->post(route('courses.feedback.store', $course), $payload)->assertRedirect(route('participant.feedback.index'));
        $payload['key_learning'] = 'Updated';
        $this->actingAs($participant)->post(route('courses.feedback.store', $course), $payload)->assertRedirect(route('participant.feedback.index'));
        $this->assertDatabaseHas('course_feedback_responses', ['course_id'=>$course->id,'user_id'=>$participant->id,'enrolment_id'=>$enrolment->id,'key_learning'=>'Updated']);
        $this->assertDatabaseCount('course_feedback_responses', 1);
    }

    public function test_event_feedback_requires_published_event_and_registration_and_saves_feedback(): void
    {
        $participant = $this->user(); $event = $this->event(false);
        $this->actingAs($participant)->get(route('events.feedback', $event))->assertNotFound();
        $event->update(['is_published'=>true]);
        $this->actingAs($participant)->get(route('events.feedback', $event))->assertNotFound();
        $registration = $event->registrations()->create(['user_id'=>$participant->id,'status'=>'registered','registered_at'=>now()]);
        $this->actingAs($participant)->get(route('events.feedback', $event))->assertOk();
        $this->actingAs($participant)->post(route('events.feedback.store', $event), ['overall_rating'=>5,'relevance_rating'=>4,'facilitation_rating'=>5,'organisation_rating'=>4,'recommend_rating'=>5,'key_learning'=>'Useful'])->assertRedirect(route('participant.feedback.index'));
        $this->assertDatabaseHas('event_feedback_responses', ['event_id'=>$event->id,'user_id'=>$participant->id,'event_registration_id'=>$registration->id,'key_learning'=>'Useful']);
    }

    public function test_participant_feedback_index_renders_saved_course_and_event_feedback_in_edit_state(): void
    {
        $participant = $this->user();
        $course = $this->course();
        $enrolment = $participant->enrolments()->create([
            'course_id' => $course->id,
            'status' => 'enrolled',
            'enrolled_at' => now(),
        ]);
        $courseFeedback = CourseFeedbackResponse::create([
            'course_id' => $course->id,
            'enrolment_id' => $enrolment->id,
            'user_id' => $participant->id,
            'overall_rating' => 4,
            'content_rating' => 3,
            'instructor_rating' => 5,
            'recommend_rating' => 4,
            'key_learning' => 'Course learning saved',
            'submitted_at' => now(),
        ]);

        $event = $this->event();
        $registration = $event->registrations()->create([
            'user_id' => $participant->id,
            'status' => 'registered',
            'registered_at' => now(),
        ]);
        $eventFeedback = EventFeedbackResponse::create([
            'event_id' => $event->id,
            'event_registration_id' => $registration->id,
            'user_id' => $participant->id,
            'overall_rating' => 5,
            'relevance_rating' => 4,
            'facilitation_rating' => 3,
            'organisation_rating' => 5,
            'recommend_rating' => 4,
            'key_learning' => 'Event learning saved',
            'submitted_at' => now(),
        ]);

        $response = $this->actingAs($participant)->get(route('participant.feedback.index'));

        $response->assertOk()
            ->assertSee($course->title)
            ->assertSee('Feedback submitted')
            ->assertSee('Edit feedback')
            ->assertSee('Course learning saved')
            ->assertSee('Event learning saved')
            ->assertSee('Save changes')
            ->assertSee('value="'.$courseFeedback->overall_rating.'" selected', false)
            ->assertSee('value="'.$eventFeedback->overall_rating.'" selected', false);
    }

    public function test_workplan_upload_and_owner_can_download_source_but_other_staff_cannot(): void
    {
        Storage::fake('local'); $owner = $this->user('staff');
        foreach (['workplans.create','workplans.view'] as $permissionName) {
            $permission = \App\Models\Permission::create(['name'=>$permissionName,'slug'=>$permissionName]);
            $role = Role::create(['name'=>'role-'.$permissionName,'slug'=>'role-'.$permissionName,'is_system'=>false]);
            $role->permissions()->attach($permission);
            $owner->roles()->attach($role);
        }
        $this->actingAs($owner)->post(route('admin.workplans.store'), ['title'=>'Uploaded','period_type'=>'annual','workplan_file'=>UploadedFile::fake()->create('plan.csv',2,'text/csv')])->assertSessionHasNoErrors();
        $plan = Workplan::where('created_by',$owner->id)->firstOrFail();
        $this->assertNotNull($plan->source_file_path);
        $this->actingAs($owner)->get(route('admin.workplans.download-source',$plan))->assertOk();
        $other = $this->user('staff'); $this->role($other,'unlisted');
        $this->actingAs($other)->get(route('admin.workplans.download-source',$plan))->assertForbidden();
        $plan->update(['source_file_path'=>'../secret.csv']);
        $this->actingAs($owner)->get(route('admin.workplans.download-source',$plan))->assertNotFound();
    }

    public function test_workplan_upload_validates_source_file_type(): void
    {
        $staff = $this->user('staff'); $this->role($staff,'administrator');
        $this->actingAs($staff)->post(route('admin.workplans.store'), ['title'=>'Plan','period_type'=>'annual','workplan_file'=>UploadedFile::fake()->createWithContent('malware.exe','x')])->assertSessionHasErrors('workplan_file');
    }

    public function test_non_owner_cannot_download_draft_even_with_view_permission(): void
    {
        Storage::fake('local'); Storage::disk('local')->put('workplans/draft.csv','file');
        $owner = $this->user('staff'); $plan = $this->workplan($owner,'draft','workplans/draft.csv');
        $staff = $this->user('staff');
        $permission = \App\Models\Permission::create(['name'=>'workplans.view','slug'=>'workplans.view']);
        $role = Role::create(['name'=>'viewer','slug'=>'viewer','is_system'=>false]); $role->permissions()->attach($permission); $staff->roles()->attach($role);
        $this->actingAs($staff)->get(route('admin.workplans.download-source',$plan))->assertForbidden();
    }

    public function test_non_staff_admin_with_view_permission_cannot_download_workplan_source(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('workplans/annual.csv', 'file');
        $owner = $this->user('staff');
        $plan = $this->workplan($owner, 'draft', 'workplans/annual.csv');
        $view = \App\Models\Permission::create(['name'=>'workplans.view','slug'=>'workplans.view']);
        $ownerRole = Role::create(['name'=>'owner-viewer','slug'=>'owner-viewer','is_system'=>false]);
        $ownerRole->permissions()->attach($view);
        $owner->roles()->attach($ownerRole);
        $user = $this->user('participant');

        $adminRole = Role::create(['name'=>'administrator','slug'=>'administrator','is_system'=>false]);
        $adminRole->permissions()->attach($view);
        $user->roles()->attach($adminRole);

        $this->actingAs($user)->get(route('admin.workplans.download-source',$plan))->assertForbidden();
    }

    public function test_download_content_disposition_uses_safe_source_filename(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('workplans/source.csv', 'file');
        $owner = $this->user('staff');
        $plan = $this->workplan($owner, 'draft', 'workplans/source.csv');
        $permission = \App\Models\Permission::create(['name'=>'workplans.view','slug'=>'workplans.view']);
        $role = Role::create(['name'=>'owner-viewer','slug'=>'owner-viewer','is_system'=>false]);
        $role->permissions()->attach($permission);
        $owner->roles()->attach($role);
        $plan->update(['source_file_name' => 'Quarterly plan.csv']);

        $response = $this->actingAs($owner)->get(route('admin.workplans.download-source',$plan));

        $response->assertOk();
        $this->assertStringContainsString('Quarterly plan.csv', $response->headers->get('Content-Disposition'));
    }

    public function test_malicious_persisted_source_filename_cannot_inject_or_escape_download_header(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('workplans/source.csv', 'file');
        $owner = $this->user('staff');
        $plan = $this->workplan($owner, 'draft', 'workplans/source.csv');
        $permission = \App\Models\Permission::create(['name'=>'workplans.view','slug'=>'workplans.view']);
        $role = Role::create(['name'=>'owner-viewer','slug'=>'owner-viewer','is_system'=>false]);
        $role->permissions()->attach($permission);
        $owner->roles()->attach($role);
        $plan->update(['source_file_name' => "..\\outside.csv\r\nX-Injected: yes"]);

        $response = $this->actingAs($owner)->get(route('admin.workplans.download-source',$plan));

        $this->assertContains($response->getStatusCode(), [200, 404]);
        if ($response->getStatusCode() === 200) {
            $disposition = $response->headers->get('Content-Disposition');
            $this->assertStringNotContainsString("\r", $disposition);
            $this->assertStringNotContainsString("\n", $disposition);
            $this->assertStringNotContainsString('outside.csv', $disposition);
            $this->assertStringNotContainsString('X-Injected', $disposition);
        }
    }
}
