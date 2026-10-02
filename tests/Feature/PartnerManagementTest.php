<?php

namespace Tests\Feature;

use App\Models\Employer;
use App\Models\MentorProfile;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PartnerManagementTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $user = User::factory()->create(['user_type' => 'staff', 'status' => 'active']);
        $user->roles()->attach(Role::create(['name' => 'Administrator', 'slug' => 'administrator']));
        $this->actingAs($user);
        return $user;
    }

    public function test_admin_can_create_and_edit_mentor_details(): void
    {
        $this->admin();
        $this->get(route('admin.mentorship.mentors.create'))->assertOk();
        $this->post(route('admin.mentorship.mentors.store'), ['name' => 'Mentor Mary', 'email' => 'mary@example.test', 'status' => 'approved', 'organisation' => 'Example', 'skills_text' => 'Dart, Coaching'])->assertSessionHasNoErrors();
        $mentor = MentorProfile::sole();
        $this->assertSame(['Dart', 'Coaching'], $mentor->skills);
        $this->assertTrue($mentor->user->hasRole('mentor'));
        $this->get(route('admin.mentorship.mentors.edit', $mentor))->assertOk()->assertSee('Mentor Mary');
        $this->put(route('admin.mentorship.mentors.update', $mentor), ['name' => 'Mary Updated', 'email' => 'mary@example.test', 'status' => 'approved', 'professional_bio' => 'Experienced mentor'])->assertSessionHasNoErrors();
        $this->assertSame('Mary Updated', $mentor->user->fresh()->name);
        $this->assertSame('Experienced mentor', $mentor->fresh()->professional_bio);
    }

    public function test_admin_can_add_an_employer_using_an_existing_account_without_changing_its_password(): void
    {
        $this->admin();
        $owner = User::factory()->create(['user_type' => 'participant', 'status' => 'active']);
        $hash = $owner->password;
        $this->post(route('admin.jobs.employers.store'), ['user_id' => $owner->id, 'company_name' => 'Example Company', 'status' => 'approved'])->assertSessionHasNoErrors();
        $employer = Employer::sole();
        $this->assertSame($owner->id, $employer->owner_user_id);
        $this->assertSame($hash, $owner->fresh()->password);
        $this->get(route('admin.jobs.employers.edit', $employer))->assertOk()->assertSee('Example Company');
        $this->put(route('admin.jobs.employers.update', $employer), ['name' => $owner->name, 'email' => $owner->email, 'company_name' => 'Updated Company', 'status' => 'approved', 'location' => 'Kampala'])->assertSessionHasNoErrors();
        $this->assertSame('Updated Company', $employer->fresh()->company_name);
        $this->assertTrue($owner->hasRole('employer'));
        $this->post(route('admin.jobs.employers.store'), ['user_id' => $owner->id, 'company_name' => 'Duplicate', 'status' => 'approved'])->assertSessionHasErrors('user_id');
    }

    public function test_public_signup_creates_pending_accounts_and_admin_approval_activates_them(): void
    {
        $this->get(route('public.partners.mentor'))->assertOk()->assertSee('Become a Mentor');
        $this->post(route('public.partners.mentor.store'), [
            'name' => 'New Mentor', 'email' => 'NewMentor@example.test', 'password' => 'SecurePassword123', 'password_confirmation' => 'SecurePassword123',
            'consent' => 1, 'organisation' => 'Org', 'status' => 'approved', 'user_type' => 'staff',
        ])->assertSessionHasNoErrors()->assertRedirect(route('login'));
        $mentor = MentorProfile::sole();
        $this->assertSame('pending', $mentor->status);
        $this->assertSame('pending', $mentor->user->status);
        $this->assertSame('participant', $mentor->user->user_type);
        $this->assertTrue(Hash::check('SecurePassword123', $mentor->user->password));
        $this->assertDatabaseCount('consents', 2);
        $this->admin();
        $this->post(route('admin.mentorship.mentors.approve', $mentor))->assertSessionHas('success');
        $this->assertSame('active', $mentor->user->fresh()->status);
    }

    public function test_public_employer_signup_is_available_and_requires_consent(): void
    {
        $this->get(route('public.partners.employer'))->assertOk()->assertSee('Company name');
        $data = ['name' => 'Owner', 'email' => 'owner@example.test', 'company_name' => 'New Company', 'password' => 'SecurePassword123', 'password_confirmation' => 'SecurePassword123'];
        $this->post(route('public.partners.employer.store'), $data)->assertSessionHasErrors('consent');
        $this->assertDatabaseCount('employers', 0);
        $this->post(route('public.partners.employer.store'), $data + ['consent' => 1])->assertSessionHasNoErrors();
        $this->assertSame('pending', Employer::sole()->status);
    }

    public function test_ordinary_staff_and_participants_cannot_add_partner_details(): void
    {
        foreach (['staff', 'participant'] as $type) {
            $this->actingAs(User::factory()->create(['user_type' => $type, 'status' => 'active']))
                ->get(route('admin.mentorship.mentors.create'))->assertForbidden();
            $this->post(route('admin.jobs.employers.store'), ['company_name' => 'Bad'])->assertForbidden();
        }
    }

    public function test_public_signup_cannot_claim_an_existing_account(): void
    {
        $user = User::factory()->create(['user_type' => 'participant', 'status' => 'active']);
        $this->post(route('public.partners.mentor.store'), [
            'user_id' => $user->id, 'name' => 'Someone Else', 'email' => $user->email,
            'password' => 'SecurePassword123', 'password_confirmation' => 'SecurePassword123', 'consent' => 1,
        ])->assertSessionHasErrors('email');
        $this->assertDatabaseCount('mentor_profiles', 0);
        $this->assertSame($user->name, $user->fresh()->name);
    }
}
