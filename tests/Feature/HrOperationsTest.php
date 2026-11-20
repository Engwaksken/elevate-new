<?php

namespace Tests\Feature;

use App\Models\AiIntegration;
use App\Models\Employee;
use App\Models\StaffDocument;
use App\Models\HrDocumentRequirement;
use App\Models\HrPolicy;
use App\Models\HrPolicyAssignment;
use App\Models\Permission;
use App\Models\RecruitmentApplicant;
use App\Models\RecruitmentInterview;
use App\Models\RecruitmentInterviewPanelist;
use App\Models\RecruitmentVacancy;
use App\Models\Role;
use App\Models\User;
use App\Services\HR\RecruitmentScreeningService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class HrOperationsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        Storage::fake('local');
    }

    private function staff(string $name = 'Staff Member', array $permissions = []): User
    {
        $user = User::factory()->create(['name' => $name, 'user_type' => 'staff', 'status' => 'active', 'email_verified_at' => now()]);
        if ($permissions) {
            $role = Role::firstOrCreate(['slug' => 'hr'], ['name' => 'HR']);
            $role->permissions()->syncWithoutDetaching(collect($permissions)->map(fn ($slug) => Permission::firstOrCreate(
                ['slug' => $slug], ['name' => $slug, 'module' => 'hr'],
            )->id)->all());
            $user->roles()->attach($role);
        }

        return $user;
    }

    private function hr(): User
    {
        return $this->staff('Harriet HR', ['hr.view', 'hr.manage']);
    }

    private function vacancy(array $overrides = []): RecruitmentVacancy
    {
        return RecruitmentVacancy::create(array_merge([
            'title' => 'M&E Officer',
            'status' => 'open',
            'responsibilities' => 'Track indicators and write donor reports.',
            'requirements' => 'Degree in statistics; KoboToolbox; Excel.',
            'min_education' => 'bachelors',
            'min_experience_years' => 3,
            'skills' => ['KoboToolbox', 'Excel'],
            'criteria' => [['label' => 'Donor reporting experience', 'weight' => 3, 'must_have' => true]],
            'closing_date' => now()->addWeek()->toDateString(),
            'cv_required' => false,
        ], $overrides));
    }

    private function applicant(RecruitmentVacancy $vacancy, array $overrides = []): RecruitmentApplicant
    {
        static $n = 0;
        $n++;

        return RecruitmentApplicant::create(array_merge([
            'vacancy_id' => $vacancy->id,
            'name' => "Candidate {$n}",
            'email' => "candidate{$n}@example.org",
            'phone' => '0700000000',
            'education_level' => 'bachelors',
            'years_experience' => 3,
        ], $overrides));
    }

    public function test_hr_posts_a_vacancy_with_criteria_and_it_appears_on_careers_page_and_embed(): void
    {
        $this->actingAs($this->hr())->post(route('admin.hr.recruitment.store'), [
            'title' => 'Programme Officer',
            'status' => 'open',
            'skills' => 'Facilitation, Report writing',
            'criteria' => [
                ['label' => 'Community mobilisation experience', 'weight' => 4, 'must_have' => '1'],
                ['label' => '', 'weight' => 1],
            ],
            'application_questions' => "Why this role?\nNotice period?",
            'cv_required' => '1',
        ])->assertRedirect();

        $vacancy = RecruitmentVacancy::firstOrFail();
        $this->assertSame([['label' => 'Community mobilisation experience', 'weight' => 4, 'must_have' => true]], $vacancy->criteria);
        $this->assertSame(['Facilitation', 'Report writing'], $vacancy->skills);
        $this->assertNotNull($vacancy->published_at);

        $this->get(route('admin.hr.recruitment.show', [$vacancy, 'tab' => 'embed']))->assertOk()
            ->assertSee('data-elevateher-jobs', false)->assertSee($vacancy->slug);

        auth()->logout();
        $this->get(route('careers.index'))->assertOk()->assertSee('Programme Officer');
        $this->get(route('careers.show', $vacancy->slug))->assertOk()->assertSee('Why this role?');

        $embed = $this->get(route('careers.embed.show', $vacancy->slug))->assertOk();
        $this->assertNull($embed->headers->get('X-Frame-Options'));
        $this->assertStringContainsString('frame-ancestors *', $embed->headers->get('Content-Security-Policy'));
        $this->assertSame('SAMEORIGIN', $this->get(route('careers.index'))->headers->get('X-Frame-Options'));

        $this->get(route('careers.embed.script'))->assertOk()
            ->assertHeader('Content-Type', 'application/javascript; charset=UTF-8')
            ->assertSee('ehCareersHeight', false);
    }

    public function test_draft_vacancies_are_not_public(): void
    {
        $vacancy = $this->vacancy(['status' => 'draft']);

        $this->get(route('careers.index'))->assertDontSee('M&amp;E Officer', false);
        $this->get(route('careers.show', $vacancy->slug))->assertNotFound();
    }

    public function test_job_seeker_applies_from_embed_and_gets_a_confirmation(): void
    {
        $vacancy = $this->vacancy(['cv_required' => true, 'application_questions' => ['Notice period?']]);
        $payload = [
            'name' => 'Amina Nakato', 'email' => 'amina@example.org', 'phone' => '0772000000',
            'education_level' => 'masters', 'years_experience' => 5, 'consent' => '1',
            'answers' => ['One month'], 'embed' => '1', 'host' => 'partner.org', 'started' => time() - 60,
            'cv' => UploadedFile::fake()->create('cv.pdf', 20, 'application/pdf'),
        ];

        $this->post(route('careers.apply', $vacancy->slug), $payload)->assertOk()->assertSee('Thank you for applying');

        $applicant = RecruitmentApplicant::firstOrFail();
        $this->assertSame('embed', $applicant->source);
        $this->assertSame('partner.org', $applicant->source_site);
        $this->assertSame([['question' => 'Notice period?', 'answer' => 'One month']], $applicant->answers);
        Storage::disk('local')->assertExists($applicant->cv_path);
        Mail::assertSent(\App\Mail\SystemMail::class, fn ($m) => $m->hasTo('amina@example.org'));

        // Screened right after the response (rule-based here: no AI configured).
        $this->assertNotNull($applicant->fresh()->ai_screened_at);

        // A second application with the same email is refused, and the form is re-rendered with the error.
        $payload['cv'] = UploadedFile::fake()->create('cv.pdf', 20, 'application/pdf');
        $this->post(route('careers.apply', $vacancy->slug), $payload)->assertStatus(422)->assertSee('already applied');
        $this->assertSame(1, RecruitmentApplicant::count());
    }

    public function test_spam_submissions_and_closed_vacancies_create_no_applicants(): void
    {
        $vacancy = $this->vacancy();
        $base = ['name' => 'Bot', 'email' => 'bot@example.org', 'phone' => '1', 'education_level' => 'none', 'years_experience' => 1, 'consent' => '1', 'started' => time() - 60];

        $this->post(route('careers.apply', $vacancy->slug), $base + ['website' => 'http://spam'])->assertOk();
        $this->post(route('careers.apply', $vacancy->slug), array_merge($base, ['started' => time()]))->assertOk();
        $this->assertSame(0, RecruitmentApplicant::count());

        $vacancy->update(['closing_date' => now()->subDay()->toDateString()]);
        $this->post(route('careers.apply', $vacancy->slug), $base)->assertStatus(422);
        $this->assertSame(0, RecruitmentApplicant::count());
    }

    public function test_rule_based_screening_ranks_applicants_and_hr_shortlists_the_top(): void
    {
        $vacancy = $this->vacancy();
        $strong = $this->applicant($vacancy, ['years_experience' => 6, 'education_level' => 'masters', 'cv_text' => 'Led donor reporting for USAID. Expert in KoboToolbox and Excel.']);
        $weak = $this->applicant($vacancy, ['years_experience' => 0, 'education_level' => 'certificate', 'cv_text' => 'Retail cashier.']);

        $this->actingAs($this->hr())->post(route('admin.hr.recruitment.screen', $vacancy))->assertSessionHas('success');

        $strong->refresh();
        $weak->refresh();
        $this->assertSame('rules', $strong->ai_method);
        $this->assertGreaterThan((float) $weak->ai_score, (float) $strong->ai_score);
        $this->assertSame('weak', $weak->ai_recommendation); // must-have "donor reporting" not met
        $this->assertSame('screened', $strong->status);

        $this->post(route('admin.hr.recruitment.shortlist-top', $vacancy), ['top' => 1])->assertSessionHas('success');
        $this->assertSame('shortlisted', $strong->fresh()->status);
        $this->assertSame('screened', $weak->fresh()->status);

        $this->get(route('admin.hr.recruitment.show', $vacancy))->assertOk()->assertSee($strong->name)->assertSee('Strong match');
    }

    public function test_ai_screening_uses_the_active_provider_without_sending_identifiers(): void
    {
        $config = AiIntegration::forProvider('openai');
        $config->fill(['model' => 'gpt-4o-mini', 'enabled' => true]);
        $config->setApiKey('sk-test');
        $config->save();

        Http::fake(['*' => Http::response(['choices' => [['message' => ['content' => json_encode([
            'criteria' => [
                ['label' => 'Donor reporting experience', 'score' => 90, 'note' => 'Wrote quarterly USAID reports.'],
                ['label' => 'x', 'score' => 80, 'note' => 'Six years.'],
                ['label' => 'x', 'score' => 100, 'note' => 'Masters.'],
                ['label' => 'x', 'score' => 70, 'note' => 'Kobo.'],
            ],
            'strengths' => ['Donor reporting'], 'gaps' => [], 'summary' => 'Strong M&E profile.',
        ])], 'finish_reason' => 'stop']], 'usage' => ['prompt_tokens' => 10, 'completion_tokens' => 10]])]);

        $vacancy = $this->vacancy();
        $applicant = $this->applicant($vacancy, ['name' => 'Grace Achieng', 'email' => 'grace@example.org', 'cv_text' => 'Grace Achieng, grace@example.org, +256 772 123456. M&E officer.']);

        app(RecruitmentScreeningService::class)->screen($applicant->load('vacancy'));

        $applicant->refresh();
        $this->assertSame('ai', $applicant->ai_method);
        $this->assertSame('strong', $applicant->ai_recommendation);
        $this->assertSame('Strong M&E profile.', $applicant->ai_summary);
        // Weighted: (90*3 + 80*3 + 100*2 + 70*3) / 11
        $this->assertEqualsWithDelta(83.64, (float) $applicant->ai_score, 0.01);

        Http::assertSent(function ($request) {
            $body = json_encode($request->data());

            return ! str_contains($body, 'Grace') && ! str_contains($body, 'grace@example.org') && ! str_contains($body, '772 123456');
        });
    }

    public function test_hr_schedules_interviews_and_the_panel_scores_each_question(): void
    {
        $hr = $this->hr();
        $panel = $this->staff('Paul Panel');
        $outsider = $this->staff('Olive Other');
        $vacancy = $this->vacancy();
        $vacancy->questions()->create(['question' => 'Describe a report you wrote.', 'max_score' => 5, 'weight' => 2]);
        $vacancy->questions()->create(['question' => 'How do you clean data?', 'max_score' => 5, 'weight' => 1]);
        $applicant = $this->applicant($vacancy, ['status' => 'shortlisted']);

        $this->actingAs($hr)->post(route('admin.hr.recruitment.interviews.store', $vacancy), [
            'applicant_ids' => [$applicant->id],
            'scheduled_at' => now()->addDays(2)->format('Y-m-d H:i'),
            'duration_minutes' => 45, 'mode' => 'online', 'meeting_link' => 'https://meet.example.org/abc',
            'panel_ids' => [$panel->id], 'notify_candidate' => '1',
        ])->assertSessionHasNoErrors();

        $interview = RecruitmentInterview::firstOrFail();
        $this->assertSame('interview', $applicant->fresh()->status);
        $this->assertDatabaseHas('user_notifications', ['user_id' => $panel->id, 'type' => 'recruitment_panel_invite']);
        Mail::assertSent(\App\Mail\SystemMail::class, fn ($m) => $m->hasTo($applicant->email) && str_contains($m->subjectLine, 'Interview invitation'));

        $this->actingAs($outsider)->get(route('staff.interviews.show', $interview))->assertForbidden();

        $this->actingAs($panel)->get(route('staff.interviews.index'))->assertOk()->assertSee($applicant->name);
        $this->get(route('staff.interviews.show', $interview))->assertOk()->assertSee('Describe a report you wrote.');

        [$q1, $q2] = $vacancy->questions->all();
        $this->post(route('staff.interviews.score', $interview), ['scores' => [$q1->id => 4]])->assertSessionHasNoErrors();
        $this->post(route('staff.interviews.score', $interview), ['scores' => [$q1->id => 4], 'submit' => '1'])
            ->assertSessionHasErrors(['scores.'.$q2->id, 'recommendation']);

        $this->post(route('staff.interviews.score', $interview), [
            'scores' => [$q1->id => 4, $q2->id => 5], 'recommendation' => 'hire', 'submit' => '1',
        ])->assertSessionHasNoErrors();

        $panelist = RecruitmentInterviewPanelist::firstOrFail();
        // (4*2 + 5*1) / (5*2 + 5*1) = 13/15
        $this->assertEqualsWithDelta(86.67, (float) $panelist->total_score, 0.01);
        $this->assertNotNull($panelist->submitted_at);
        $interview->refresh();
        $this->assertSame('completed', $interview->status);
        $this->assertEqualsWithDelta(86.67, (float) $interview->average_score, 0.01);

        // Submitted scores are locked for the panellist.
        $this->post(route('staff.interviews.score', $interview), ['scores' => [$q1->id => 1, $q2->id => 1], 'recommendation' => 'no_hire', 'submit' => '1'])->assertStatus(422);

        $this->actingAs($hr)->get(route('admin.hr.recruitment.applicant', $applicant))->assertOk()->assertSee('86.7%');
    }

    public function test_policies_are_shared_acknowledged_and_new_staff_get_onboarding_policies(): void
    {
        $hr = $this->hr();
        $amy = $this->staff('Amy Staff');

        $this->actingAs($hr)->post(route('admin.hr.policies.store'), [
            'title' => 'Safeguarding Policy', 'body' => 'Protect children and vulnerable adults.',
            'requires_acknowledgement' => '1', 'assign_to_new_staff' => '1', 'due_days' => 5, 'audience' => 'all',
        ])->assertRedirect();

        $policy = HrPolicy::firstOrFail();
        $this->assertDatabaseHas('hr_policy_assignments', ['policy_id' => $policy->id, 'user_id' => $amy->id]);
        $this->assertDatabaseHas('user_notifications', ['user_id' => $amy->id, 'type' => 'hr_policy_shared']);

        $this->actingAs($amy)->get(route('staff.hr.index'))->assertOk()->assertSee('Safeguarding Policy')->assertSee('Read &amp; acknowledge', false);
        $this->get(route('staff.hr.policies.show', $policy))->assertOk()->assertSee('Protect children');
        $this->post(route('staff.hr.policies.acknowledge', $policy))->assertSessionHasErrors('confirm');
        $this->post(route('staff.hr.policies.acknowledge', $policy), ['confirm' => '1'])->assertRedirect(route('staff.hr.index'));
        $this->assertNotNull(HrPolicyAssignment::where('user_id', $amy->id)->value('acknowledged_at'));

        // A policy that was not shared cannot be opened by ordinary staff.
        $private = HrPolicy::create(['title' => 'Board Only', 'body' => 'x', 'requires_acknowledgement' => true]);
        $this->get(route('staff.hr.policies.show', $private))->assertForbidden();

        // New employee record: onboarding policies are shared automatically.
        $newcomer = $this->staff('Nora New');
        $this->actingAs($hr)->post(route('admin.hr.employees.store'), [
            'user_id' => $newcomer->id, 'employee_number' => 'EMP-100', 'status' => 'probation',
        ])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('hr_policy_assignments', ['policy_id' => $policy->id, 'user_id' => $newcomer->id]);
    }

    public function test_hr_reminds_staff_with_missing_documents_and_unacknowledged_policies(): void
    {
        $hr = $this->hr();
        $amy = $this->staff('Amy Staff');
        $ben = $this->staff('Ben Staff');
        $id = HrDocumentRequirement::create(['name' => 'National ID', 'is_mandatory' => true]);

        // Ben uploads his ID; Amy has not.
        $this->actingAs($ben)->post(route('staff.hr.documents.store'), [
            'requirement_id' => $id->id, 'file' => UploadedFile::fake()->create('id.pdf', 10, 'application/pdf'),
        ])->assertSessionHasNoErrors();
        $this->assertSame('submitted', StaffDocument::where('user_id', $ben->id)->value('status'));

        $this->actingAs($hr)->get(route('admin.hr.compliance.index'))->assertOk()->assertSee('Amy Staff')->assertSee('National ID');
        $this->post(route('admin.hr.compliance.remind'), ['scope' => 'all'])->assertSessionHas('success');

        $this->assertDatabaseHas('user_notifications', ['user_id' => $amy->id, 'type' => 'hr_compliance_reminder']);
        $this->assertDatabaseMissing('user_notifications', ['user_id' => $ben->id, 'type' => 'hr_compliance_reminder']);

        // The automatic run skips Amy: she was reminded less than 3 days ago.
        \App\Models\UserNotification::query()->delete();
        $this->artisan('hr:send-compliance-reminders')->assertSuccessful();
        $this->assertDatabaseMissing('user_notifications', ['user_id' => $amy->id, 'type' => 'hr_compliance_reminder']);

        $this->travel(4)->days();
        $this->artisan('hr:send-compliance-reminders')->assertSuccessful();
        $this->assertDatabaseHas('user_notifications', ['user_id' => $amy->id, 'type' => 'hr_compliance_reminder']);

        // HR rejects Ben's upload: it counts as missing again and Ben is told why.
        $doc = StaffDocument::where('user_id', $ben->id)->firstOrFail();
        $this->actingAs($hr)->post(route('admin.hr.compliance.documents.review', $doc), ['status' => 'rejected'])->assertSessionHasErrors('review_note');
        $this->post(route('admin.hr.compliance.documents.review', $doc), ['status' => 'rejected', 'review_note' => 'Blurred scan'])->assertSessionHas('success');
        $this->assertDatabaseHas('user_notifications', ['user_id' => $ben->id, 'type' => 'hr_document_reviewed']);
        $this->assertCount(1, app(\App\Services\HR\HrComplianceService::class)->missingDocuments($ben));
    }

    public function test_hr_pages_render_and_need_hr_permission(): void
    {
        $hr = $this->hr();
        $employee = Employee::create(['user_id' => $this->staff('Eve Employee')->id, 'employee_number' => 'E-1', 'status' => 'active']);
        $vacancy = $this->vacancy();
        $policy = HrPolicy::create(['title' => 'Code of Conduct', 'body' => 'Be kind.']);

        $this->actingAs($hr);
        foreach ([
            route('admin.hr.dashboard'), route('admin.hr.recruitment.index'), route('admin.hr.policies.index'),
            route('admin.hr.policies.show', $policy), route('admin.hr.compliance.index'),
            route('admin.hr.employees.index'), route('admin.hr.employees.show', $employee),
        ] as $url) {
            $this->get($url)->assertOk();
        }
        foreach (['applicants', 'interviews', 'questions', 'embed', 'details'] as $tab) {
            $this->get(route('admin.hr.recruitment.show', [$vacancy, 'tab' => $tab]))->assertOk();
        }

        $this->put(route('admin.hr.employees.update', $employee), [
            'user_id' => $employee->user_id, 'employee_number' => 'E-1', 'status' => 'on_leave', 'emergency_contact_name' => 'Mum',
        ])->assertSessionHasNoErrors();
        $this->assertSame('on_leave', $employee->fresh()->status);

        $plain = $this->staff('Plain Staff');
        $this->actingAs($plain)->get(route('admin.hr.dashboard'))->assertForbidden();
        $this->post(route('admin.hr.recruitment.store'), ['title' => 'X', 'status' => 'open'])->assertForbidden();
        $this->get(route('staff.hr.index'))->assertOk();
    }
}
