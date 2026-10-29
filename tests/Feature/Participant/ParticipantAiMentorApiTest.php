<?php

namespace Tests\Feature\Participant;

use App\Models\AiIntegration;
use App\Models\CalendarEvent;
use App\Models\CareerActionItem;
use App\Models\CareerMentorMessage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ParticipantAiMentorApiTest extends TestCase
{
    use RefreshDatabase;

    private const BASE = '/api/v1/participant/mentorship/assistant';

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-29 10:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function participant(): User
    {
        return User::factory()->create(['user_type' => 'participant', 'status' => 'active', 'email_verified_at' => now()]);
    }

    private function enableAi(): void
    {
        $ai = AiIntegration::forProvider('openai');
        $ai->fill(['provider' => 'openai', 'model' => 'test-model', 'endpoint' => 'https://ai.example.test/v1/chat/completions', 'enabled' => true]);
        $ai->setApiKey('key');
        $ai->save();
    }

    public function test_app_gets_history_with_steps_and_the_enabled_flag(): void
    {
        $user = $this->participant();
        Sanctum::actingAs($user);

        $this->getJson(self::BASE)->assertOk()->assertJson(['enabled' => false, 'messages' => []]);

        $this->enableAi();
        Http::fake(['ai.example.test/*' => Http::response(['choices' => [['message' => ['content' => "Apply this week.\nACTIONS: [{\"title\":\"Send two applications\",\"due\":\"2026-11-02\",\"goal\":\"\"}]"]]]])]);
        $reply = $this->postJson(self::BASE, ['message' => 'Help me'])->assertOk()->json();

        $this->getJson(self::BASE)->assertOk()
            ->assertJsonPath('enabled', true)
            ->assertJsonPath('messages.0.role', 'user')
            ->assertJsonPath('messages.1.id', $reply['id'])
            ->assertJsonPath('messages.1.actions.0.title', 'Send two applications')
            ->assertJsonPath('messages.1.actions.0.status', 'suggested');
    }

    public function test_app_saves_answers_approves_completes_and_lists_the_plan(): void
    {
        $user = $this->participant();
        Sanctum::actingAs($user);
        $answer = CareerMentorMessage::create(['user_id' => $user->id, 'role' => 'assistant', 'content' => 'Keep a learning diary.']);
        $step = $answer->actions()->create(['user_id' => $user->id, 'title' => 'Start a diary', 'due_on' => '2026-11-01', 'status' => 'suggested']);
        $other = $answer->actions()->create(['user_id' => $user->id, 'title' => 'Skip me', 'due_on' => '2026-11-01', 'status' => 'suggested']);

        $this->patchJson(self::BASE."/messages/{$answer->id}/save")->assertOk()->assertJson(['saved' => true]);
        $this->getJson(self::BASE.'/saved')->assertOk()->assertJsonPath('data.0.content', 'Keep a learning diary.')->assertJsonPath('meta.total', 1);

        $this->postJson(self::BASE."/actions/{$step->id}/approve", ['title' => 'Start a learning diary', 'due_on' => '2026-11-03'])
            ->assertOk()->assertJson(['status' => 'approved', 'due_on' => '2026-11-03']);
        $this->assertSame(1, CalendarEvent::where('responsible_user_id', $user->id)->count());
        $this->postJson(self::BASE."/actions/{$other->id}/dismiss")->assertOk()->assertJson(['status' => 'dismissed']);
        $this->postJson(self::BASE."/actions/{$step->id}/approve", ['title' => 'x', 'due_on' => '2026-10-01'])->assertStatus(422);

        $this->getJson(self::BASE.'/plan')->assertOk()->assertJsonPath('data.0.title', 'Start a learning diary')->assertJsonPath('meta.total', 1);
        $this->postJson(self::BASE."/actions/{$step->id}/complete")->assertOk()->assertJson(['status' => 'completed']);
        $this->getJson(self::BASE.'/plan?status=approved')->assertOk()->assertJsonPath('meta.total', 0);
        $this->getJson(self::BASE.'/plan?status=completed')->assertOk()->assertJsonPath('meta.total', 1);

        $this->deleteJson(self::BASE.'/history')->assertOk();
        $this->assertSame(1, CareerMentorMessage::count(), 'The saved answer is kept.');
    }

    public function test_participants_cannot_touch_each_others_items(): void
    {
        $owner = $this->participant();
        $answer = CareerMentorMessage::create(['user_id' => $owner->id, 'role' => 'assistant', 'content' => 'Private']);
        $step = CareerActionItem::create(['user_id' => $owner->id, 'career_mentor_message_id' => $answer->id, 'title' => 'Mine', 'due_on' => '2026-11-01', 'status' => 'suggested']);

        Sanctum::actingAs($this->participant());
        $this->patchJson(self::BASE."/messages/{$answer->id}/save")->assertNotFound();
        $this->postJson(self::BASE."/actions/{$step->id}/approve", ['title' => 'x', 'due_on' => '2026-11-02'])->assertNotFound();
        $this->getJson(self::BASE.'/plan')->assertOk()->assertJsonPath('meta.total', 0);
        $this->getJson(self::BASE)->assertOk()->assertJsonCount(0, 'messages');
    }
}
