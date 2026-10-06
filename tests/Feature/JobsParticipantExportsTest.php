<?php

namespace Tests\Feature;

use App\Models\Employer;
use App\Models\ItSupportTicket;
use App\Models\Job;
use App\Models\JobApplication;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JobsParticipantExportsTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $user = User::factory()->create(['user_type' => 'staff', 'status' => 'active']);
        $user->roles()->attach(Role::firstOrCreate(['slug' => 'super-administrator'], ['name' => 'Super Administrator']));

        return $user;
    }

    private function employerOwner(string $company): User
    {
        $user = User::factory()->create(['user_type' => 'employer', 'status' => 'active', 'email_verified_at' => now()]);
        $user->roles()->attach(Role::firstOrCreate(['slug' => 'employer'], ['name' => 'Employer']));
        Employer::create(['owner_user_id' => $user->id, 'company_name' => $company, 'status' => 'approved', 'email' => $user->email]);

        return $user;
    }

    private function participant(string $name): User
    {
        return User::factory()->create(['name' => $name, 'user_type' => 'participant', 'status' => 'active', 'email_verified_at' => now()]);
    }

    private function jobFor(User $owner, string $title): Job
    {
        $employer = Employer::where('owner_user_id', $owner->id)->firstOrFail();

        return Job::create(['employer_id' => $employer->id, 'title' => $title, 'status' => 'published']);
    }

    private function apply(User $user, Job $job): JobApplication
    {
        return JobApplication::create(['job_id' => $job->id, 'user_id' => $user->id, 'status' => 'submitted', 'applied_at' => now()]);
    }

    public function test_employer_applicants_csv_contains_only_their_own_applicants(): void
    {
        $mine = $this->employerOwner('Acme');
        $other = $this->employerOwner('Globex');
        $this->apply($this->participant('Alice Mine'), $this->jobFor($mine, 'Acme Analyst'));
        $this->apply($this->participant('Bob Other'), $this->jobFor($other, 'Globex Engineer'));

        $response = $this->actingAs($mine)->get(route('employer.applicants.index', ['export' => 'csv']));

        $response->assertOk();
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));
        $csv = $response->streamedContent();
        $this->assertStringContainsString('Alice Mine', $csv);
        $this->assertStringContainsString('Acme Analyst', $csv);
        $this->assertStringNotContainsString('Bob Other', $csv);
        $this->assertStringNotContainsString('Globex Engineer', $csv);
    }

    public function test_participant_my_applications_csv_contains_only_own_rows(): void
    {
        $owner = $this->employerOwner('Acme');
        $me = $this->participant('Me Participant');
        $someoneElse = $this->participant('Someone Else');
        $this->apply($me, $this->jobFor($owner, 'Job For Me'));
        $this->apply($someoneElse, $this->jobFor($owner, 'Job For Them'));

        $response = $this->actingAs($me)->get(route('jobs.applications', ['export' => 'csv']));

        $response->assertOk();
        $csv = $response->streamedContent();
        $this->assertStringContainsString('Job,Employer,Location,Status', $csv);
        $this->assertStringContainsString('Job For Me', $csv);
        $this->assertStringNotContainsString('Job For Them', $csv);
    }

    public function test_admin_jobs_pdf_export_returns_a_pdf(): void
    {
        $this->jobFor($this->employerOwner('Acme'), 'Admin Visible Job');

        $response = $this->actingAs($this->admin())->get(route('admin.jobs.index', ['export' => 'pdf']));

        $response->assertOk();
        $this->assertSame('application/pdf', $response->headers->get('Content-Type'));
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    public function test_admin_jobs_csv_respects_the_status_filter(): void
    {
        $owner = $this->employerOwner('Acme');
        $this->jobFor($owner, 'Published Role');
        Job::create(['employer_id' => Employer::first()->id, 'title' => 'Draft Role', 'status' => 'draft']);

        $csv = $this->actingAs($this->admin())
            ->get(route('admin.jobs.index', ['status' => 'published', 'export' => 'csv']))
            ->assertOk()
            ->streamedContent();

        $this->assertStringContainsString('Published Role', $csv);
        $this->assertStringNotContainsString('Draft Role', $csv);
    }

    public function test_participants_cannot_export_admin_jobs(): void
    {
        $response = $this->actingAs($this->participant('Curious'))->get(route('admin.jobs.index', ['export' => 'csv']));

        $this->assertNotSame(200, $response->getStatusCode());
    }

    public function test_support_ticket_export_only_includes_the_requesters_tickets(): void
    {
        $me = $this->participant('Ticket Owner');
        $other = $this->participant('Other Owner');
        ItSupportTicket::factory()->create(['requester_id' => $me->id, 'subject' => 'My printer is broken']);
        ItSupportTicket::factory()->create(['requester_id' => $other->id, 'subject' => 'Their laptop is slow']);

        $response = $this->actingAs($me)->get(route('participant.support-tickets.index', ['export' => 'csv']));

        $response->assertOk();
        $csv = $response->streamedContent();
        $this->assertStringContainsString('My printer is broken', $csv);
        $this->assertStringNotContainsString('Their laptop is slow', $csv);
    }

    public function test_every_wired_page_exports_csv_and_pdf(): void
    {
        $owner = $this->employerOwner('Acme');
        $participant = $this->participant('Smoke Participant');
        $this->apply($participant, $this->jobFor($owner, 'Smoke Job'));
        $admin = $this->admin();

        $pages = [
            [$admin, 'admin.jobs.employers.index'],
            [$admin, 'admin.jobs.index'],
            [$admin, 'admin.hr.jobs.index'],
            [$admin, 'admin.jobs.applications.index'],
            [$admin, 'admin.jobs.outcomes.index'],
            [$owner, 'employer.jobs.index'],
            [$owner, 'employer.applicants.index'],
            [$participant, 'jobs.applications'],
            [$participant, 'jobs.saved'],
            [$participant, 'participant.course-calls.index', ['tab' => 'submissions']],
            [$participant, 'participant.support-tickets.index'],
            [$participant, 'notifications.index'],
        ];

        foreach ($pages as $page) {
            [$user, $route] = $page;
            $params = $page[2] ?? [];
            $this->actingAs($user)->get(route($route, $params))->assertOk()->assertSee('export=csv', false);

            $csv = $this->actingAs($user)->get(route($route, $params + ['export' => 'csv']));
            $csv->assertOk();
            $this->assertStringContainsString('text/csv', $csv->headers->get('Content-Type'), $route);
            $csv->streamedContent();

            $pdf = $this->actingAs($user)->get(route($route, $params + ['export' => 'pdf']));
            $pdf->assertOk();
            $this->assertSame('application/pdf', $pdf->headers->get('Content-Type'), $route);
        }
    }

    public function test_list_pages_render_export_buttons(): void
    {
        $owner = $this->employerOwner('Acme');

        $this->actingAs($owner)->get(route('employer.applicants.index'))
            ->assertOk()
            ->assertSee('export=csv', false)
            ->assertSee('export=pdf', false);

        $this->actingAs($this->participant('Viewer'))->get(route('jobs.applications'))
            ->assertOk()
            ->assertSee('export=csv', false);
    }
}
