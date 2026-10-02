<?php

namespace Tests\Feature;

use App\Models\AiIntegration;
use App\Models\Resume;
use App\Models\Role;
use App\Models\User;
use App\Services\CareerAiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ResumePortfolioAndSystemAiTest extends TestCase
{
    use RefreshDatabase;

    private function participant(): User
    {
        $user = User::factory()->create(['user_type'=>'participant','status'=>'active']);
        Sanctum::actingAs($user, ['participant']);
        return $user;
    }

    private function admin(): User
    {
        $user = User::factory()->create(['user_type'=>'staff','status'=>'active']);
        $user->roles()->attach(Role::firstOrCreate(['slug'=>'administrator'], ['name'=>'Administrator']));
        $this->actingAs($user);
        return $user;
    }

    public function test_resume_projects_referees_and_portfolio_links_are_editable_and_printed(): void
    {
        Storage::fake('local');
        $user = $this->participant();
        $data = ['title'=>'Complete CV','template'=>'modern','portfolio_url'=>'https://example.test/portfolio',
            'projects'=>[['name'=>'Community app','url'=>'https://github.com/example/app','description'=>'Built a learning app']],
            'referees'=>[['name'=>'Mary Example','email'=>'mary@example.test','phone'=>'0700000001','organisation'=>'Example']]];
        $response = $this->postJson('/api/v1/participant/career/resumes', $data)->assertCreated()
            ->assertJsonPath('resume.projects.0.name', 'Community app')->assertJsonPath('resume.referees.0.name', 'Mary Example');
        $resume = Resume::findOrFail($response->json('resume.id'));
        $this->actingAs($user)->get(route('career.resume.edit', $resume))->assertOk()->assertSee('Referees')->assertSee('Portfolio files');
        $html = view('career.resume.pdf', ['resume'=>$resume->load(['user','experiences','education','skills','projects','referees','portfolioFiles'])])->render();
        $this->assertStringContainsString('Community app', $html);
        $this->assertStringContainsString('mary@example.test', $html);
        $this->putJson('/api/v1/participant/career/resumes/'.$resume->id, ['title'=>'Complete CV','template'=>'modern'])->assertOk();
        $this->assertSame(1, $resume->projects()->count());
        $this->assertSame(1, $resume->referees()->count());
        $this->putJson('/api/v1/participant/career/resumes/'.$resume->id, ['title'=>'Complete CV','template'=>'modern','projects'=>[],'referees'=>[]])->assertOk();
        $this->assertSame(0, $resume->referees()->count());
    }

    public function test_portfolio_files_are_private_signed_sharing_expires_and_nested_ownership_is_enforced(): void
    {
        Storage::fake('local');
        $owner = $this->participant();
        $resume = Resume::create(['user_id'=>$owner->id,'title'=>'CV','template'=>'classic']);
        $base = '/api/v1/participant/career/resumes/'.$resume->id.'/portfolio-files';
        $response = $this->postJson($base, ['file'=>UploadedFile::fake()->create('project.pdf', 10, 'application/pdf')])
            ->assertCreated()->assertJsonMissingPath('file.path');
        $file = $resume->portfolioFiles()->firstOrFail();
        Storage::disk('local')->assertExists($file->path);
        $this->get($base.'/'.$file->id)->assertDownload('project.pdf');
        $url = $file->shareUrl();
        $this->get($url)->assertOk();
        $this->travel(8)->days();
        $this->get($url)->assertForbidden();
        $this->travelBack();
        $this->participant();
        $this->get($base.'/'.$file->id)->assertForbidden();
        $this->deleteJson($base.'/'.$file->id)->assertForbidden();
        Sanctum::actingAs($owner, ['participant']);
        $this->deleteJson($base.'/'.$file->id)->assertNoContent();
        Storage::disk('local')->assertMissing($file->path);
    }

    public function test_web_resume_extra_sections_can_be_added_edited_and_removed(): void
    {
        $user = $this->participant();
        $resume = Resume::create(['user_id'=>$user->id,'title'=>'CV','template'=>'classic']);
        $this->actingAs($user)->post(route('career.resume.extras.store', [$resume,'referees']), ['name'=>'Reference Person','email'=>'ref@example.test'])->assertSessionHasNoErrors();
        $referee = $resume->referees()->sole();
        $this->put(route('career.resume.extras.update', [$resume,'referees',$referee->id]), ['name'=>'Updated Person','phone'=>'123'])->assertSessionHasNoErrors();
        $this->assertSame('Updated Person', $referee->fresh()->name);
        $this->delete(route('career.resume.extras.destroy', [$resume,'referees',$referee->id]))->assertSessionHas('success');
        $this->assertDatabaseCount('resume_referees', 0);
    }

    public function test_system_ai_is_encrypted_single_active_and_used_across_features(): void
    {
        $admin = $this->admin();
        $legacy = AiIntegration::create(['feature'=>'old_feature','provider'=>'openai','model'=>'old','enabled'=>true]);
        $config = ['provider'=>'compatible','model'=>'test-model','endpoint'=>'https://ai.example.test/chat','api_key'=>'test-api-key','enabled'=>1];
        $this->put(route('admin.platform-settings.ai'), $config)->assertSessionHasNoErrors();
        $ai = AiIntegration::where('feature','system_ai')->sole();
        $this->assertSame('test-api-key', $ai->apiKey());
        $this->assertNotSame('test-api-key', $ai->encrypted_api_key);
        $this->assertFalse($legacy->fresh()->enabled);
        $this->assertSame(1, AiIntegration::where('enabled', true)->count());
        $this->get(route('admin.platform-settings.index'))->assertOk()->assertSee('System-wide AI Provider')->assertDontSee('test-api-key');
        Http::fake(['ai.example.test/*'=>Http::response(['choices'=>[['message'=>['content'=>'AI result']]]])]);
        foreach (['resume_improvement','resume_parsing','cover_letter_generation'] as $feature) {
            $this->assertSame('AI result', app(CareerAiService::class)->generate($feature, 'System', 'Data', $admin->id)['text']);
        }
        Http::assertSentCount(3);
        Http::assertSent(fn ($request) => $request['model'] === 'test-model' && $request->hasHeader('Authorization', 'Bearer test-api-key'));
        unset($config['api_key']);
        $this->put(route('admin.platform-settings.ai'), $config)->assertSessionHasNoErrors();
        $this->assertTrue($ai->fresh()->enabled);
        $this->assertSame('test-api-key', $ai->fresh()->apiKey());
        $this->put(route('admin.platform-settings.ai'), $config + ['api_key'=>'new-key'])->assertSessionHasNoErrors();
    }

    public function test_ai_provider_switches_require_new_credentials_and_non_admins_are_denied(): void
    {
        $this->admin();
        $this->put(route('admin.platform-settings.ai'), ['provider'=>'openai','model'=>'test-model','api_key'=>'test-key','enabled'=>1])->assertSessionHasNoErrors();
        $this->put(route('admin.platform-settings.ai'), ['provider'=>'gemini','model'=>'gemini-test','enabled'=>1])->assertSessionHasErrors('api_key');
        $this->assertSame('openai', AiIntegration::where('feature','system_ai')->sole()->provider);
        $this->put(route('admin.platform-settings.ai'), ['provider'=>'gemini','model'=>'gemini-test','enabled'=>1,'api_key'=>'gemini-key'])->assertSessionHasNoErrors();
        $this->actingAs(User::factory()->create(['user_type'=>'staff','status'=>'active']))->put(route('admin.platform-settings.ai'), ['provider'=>'openai'])->assertForbidden();
    }

    public function test_disabled_system_ai_does_not_fall_back_to_legacy_features(): void
    {
        $legacy = AiIntegration::create(['feature'=>'career_ai','provider'=>'openai','model'=>'legacy','enabled'=>true]);
        $legacy->setApiKey('legacy-test-key'); $legacy->save();
        Http::fake();
        try { app(CareerAiService::class)->generate('resume_improvement', 'System', 'Data'); $this->fail('Disabled AI should be unavailable.'); }
        catch (\RuntimeException $exception) { $this->assertSame('AI_UNAVAILABLE', $exception->getMessage()); }
        Http::assertNothingSent();
    }

    public function test_invalid_configuration_does_not_flash_api_credentials(): void
    {
        $this->admin();
        $this->put(route('admin.platform-settings.ai'), ['provider'=>'compatible','model'=>'test-model','endpoint'=>'http://example.test','api_key'=>'do-not-flash-test-key','enabled'=>1])
            ->assertSessionHasErrors('endpoint')->assertSessionHas('_old_input', fn ($input) => ($input['api_key'] ?? null) === null);
        $this->get(route('admin.platform-settings.index'))->assertOk()->assertDontSee('do-not-flash-test-key');
    }
}
