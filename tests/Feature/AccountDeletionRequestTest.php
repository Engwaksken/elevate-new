<?php

namespace Tests\Feature;

use App\Models\AccountDeletionRequest;
use App\Models\CareerMentorMessage;
use App\Models\Course;
use App\Models\CourseFeedbackResponse;
use App\Models\Role;
use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AccountDeletionRequestTest extends TestCase
{
    use RefreshDatabase;

    private const API = '/api/v1/participant';

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        Storage::fake('local');
    }

    private function participant(): User
    {
        return User::factory()->create(['name' => 'Amina Nakato', 'email' => 'amina@example.com', 'phone' => '+256700000000', 'password' => Hash::make('secret-pass'),
            'user_type' => 'participant', 'status' => 'active', 'email_verified_at' => now()]);
    }

    private function admin(): User
    {
        $admin = User::factory()->create(['user_type' => 'staff', 'status' => 'active', 'email_verified_at' => now()]);
        $admin->roles()->attach(Role::firstOrCreate(['slug' => 'administrator'], ['name' => 'Administrator']));

        return $admin;
    }

    public function test_participant_requests_deletion_from_the_app_with_her_password(): void
    {
        $user = $this->participant();
        $admin = $this->admin();
        Sanctum::actingAs($user);

        $this->getJson(self::API.'/account/deletion-request')->assertOk()->assertJson(['request' => null, 'days_to_process' => 30]);
        $this->postJson(self::API.'/account/deletion-request', ['password' => 'wrong'])->assertJsonValidationErrors('password');

        $this->postJson(self::API.'/account/deletion-request', ['password' => 'secret-pass', 'reason' => 'Moving abroad'])
            ->assertCreated()->assertJsonPath('request.status', 'pending');
        $this->postJson(self::API.'/account/deletion-request', ['password' => 'secret-pass'])->assertCreated();

        $this->assertSame(1, AccountDeletionRequest::count());
        $request = AccountDeletionRequest::sole();
        $this->assertSame(['Amina Nakato', 'amina@example.com', 'app', 'Moving abroad'], [$request->name, $request->email, $request->source, $request->reason]);
        $this->assertTrue(UserNotification::where('user_id', $admin->id)->where('type', 'account_deletion_request')->exists());
        $this->getJson(self::API.'/account/deletion-request')->assertJsonPath('request.status', 'pending');
    }

    public function test_public_page_explains_deletion_and_signed_in_users_can_request_there(): void
    {
        $this->get(route('account-deletion.show'))->assertOk()
            ->assertSee('Delete your ElevateHer360 account')->assertSee('Delete my account')->assertSee('Sign in to continue');
        $this->post(route('account-deletion.store'), ['password' => 'secret-pass', 'confirm' => 1])->assertRedirect(route('login'));

        $user = $this->participant();
        $this->actingAs($user)->post(route('account-deletion.store'), ['password' => 'secret-pass'])->assertSessionHasErrors('confirm');
        $this->actingAs($user)->post(route('account-deletion.store'), ['password' => 'secret-pass', 'confirm' => 1])
            ->assertRedirect(route('account-deletion.show'));
        $this->assertSame('web', AccountDeletionRequest::sole()->source);
        $this->actingAs($user)->get(route('account-deletion.show'))->assertSee('Your request is being processed');
    }

    public function test_completing_a_request_removes_personal_data_and_keeps_anonymised_records(): void
    {
        $user = $this->participant();
        $user->profile()->create(['district' => 'Kampala', 'photo_path' => 'profile-photos/'.$user->id.'/me.jpg']);
        Storage::disk('local')->put('profile-photos/'.$user->id.'/me.jpg', 'img');
        CareerMentorMessage::create(['user_id' => $user->id, 'role' => 'user', 'content' => 'private question']);
        UserNotification::create(['user_id' => $user->id, 'type' => 'general', 'title' => 'Hello']);
        $course = Course::create(['title' => 'Web Basics', 'status' => 'published']);
        $enrolment = $user->enrolments()->create(['course_id' => $course->id, 'status' => 'completed', 'enrolled_at' => now()]);
        CourseFeedbackResponse::create(['course_id' => $course->id, 'enrolment_id' => $enrolment->id, 'user_id' => $user->id, 'overall_rating' => 5,
            'content_rating' => 5, 'instructor_rating' => 5, 'recommend_rating' => 5, 'submitted_at' => now()]);

        $request = app(\App\Services\AccountDeletionService::class)->request($user, null, 'app');
        $admin = $this->admin();

        $this->actingAs($admin)->get(route('admin.account-deletions.index'))->assertOk()->assertSee('amina@example.com');
        $this->post(route('admin.account-deletions.complete', $request))->assertSessionHasErrors('confirm');
        $this->post(route('admin.account-deletions.complete', $request), ['confirm' => 1])->assertRedirect();

        $user->refresh();
        $this->assertSame('Deleted participant', $user->name);
        $this->assertStringEndsWith('@deleted.invalid', $user->email);
        $this->assertNull($user->phone);
        $this->assertSame('inactive', $user->status);
        $this->assertFalse(Hash::check('secret-pass', $user->password));
        $this->assertNull($user->profile()->first());
        $this->assertSame(0, CareerMentorMessage::where('user_id', $user->id)->count());
        $this->assertSame(0, UserNotification::where('user_id', $user->id)->count());
        Storage::disk('local')->assertMissing('profile-photos/'.$user->id.'/me.jpg');

        $this->assertSame(1, $user->enrolments()->count(), 'Programme records are kept, anonymised.');
        $this->assertSame(1, CourseFeedbackResponse::where('user_id', $user->id)->count());
        $this->assertSame('completed', $request->fresh()->status);
        $this->assertSame('amina@example.com', $request->fresh()->email, 'The request keeps the original contact for the audit trail.');

        $this->post(route('admin.account-deletions.complete', $request), ['confirm' => 1])->assertStatus(422);
    }

    public function test_rejecting_tells_the_participant_and_non_admins_cannot_process(): void
    {
        $user = $this->participant();
        $request = app(\App\Services\AccountDeletionService::class)->request($user, 'test', 'web');

        $staff = User::factory()->create(['user_type' => 'staff', 'status' => 'active']);
        $this->actingAs($staff)->get(route('admin.account-deletions.index'))->assertForbidden();

        $this->actingAs($this->admin())->post(route('admin.account-deletions.reject', $request))->assertSessionHasErrors('note');
        $this->post(route('admin.account-deletions.reject', $request), ['note' => 'You have an open certificate appeal'])->assertRedirect();

        $this->assertSame('rejected', $request->fresh()->status);
        $this->assertSame('active', $user->fresh()->status);
        $this->assertStringContainsString('open certificate appeal', UserNotification::where('user_id', $user->id)->where('type', 'account_deletion_rejected')->sole()->message);
    }
}
