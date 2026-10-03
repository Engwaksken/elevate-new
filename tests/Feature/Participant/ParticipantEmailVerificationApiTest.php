<?php

namespace Tests\Feature\Participant;

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ParticipantEmailVerificationApiTest extends TestCase
{
    use RefreshDatabase;

    private const LOGIN = '/api/v1/participant/login';
    private const ME = '/api/v1/participant/me';
    private const RESEND = '/api/v1/participant/email/verification-notification';

    protected function setUp(): void
    {
        parent::setUp();

        // Sanctum's token table is present in production but not in the
        // migration set used by the test database.
        if (! Schema::hasTable('personal_access_tokens')) {
            Schema::create('personal_access_tokens', function (Blueprint $table) {
                $table->id();
                $table->morphs('tokenable');
                $table->string('name');
                $table->string('token', 64)->unique();
                $table->text('abilities')->nullable();
                $table->timestamp('last_used_at')->nullable();
                $table->timestamp('expires_at')->nullable();
                $table->timestamps();
            });
        }
    }

    private function user(bool $verified, string $email = 'app.participant@example.com'): User
    {
        return User::factory()->create([
            'name' => 'App Participant',
            'email' => $email,
            'password' => Hash::make('Password1'),
            'user_type' => 'participant',
            'status' => 'active',
            'email_verified_at' => $verified ? now() : null,
        ]);
    }

    public function test_login_reports_email_verification_status(): void
    {
        $this->user(false, 'unverified@example.com');

        $this->postJson(self::LOGIN, ['email' => 'unverified@example.com', 'password' => 'Password1'])
            ->assertOk()
            ->assertJsonPath('user.email_verified', false);

        $this->user(true, 'verified@example.com');

        $this->postJson(self::LOGIN, ['email' => 'verified@example.com', 'password' => 'Password1'])
            ->assertOk()
            ->assertJsonPath('user.email_verified', true);
    }

    public function test_me_reports_email_verification_status(): void
    {
        $user = $this->user(false);
        Sanctum::actingAs($user, ['participant']);

        $this->getJson(self::ME)
            ->assertOk()
            ->assertJsonPath('user.email_verified', false);
    }

    public function test_unverified_participant_can_resend_the_verification_email(): void
    {
        Notification::fake();

        $user = $this->user(false);
        Sanctum::actingAs($user, ['participant']);

        $this->postJson(self::RESEND)
            ->assertOk()
            ->assertJsonPath('message', 'Verification link sent. Please check your inbox.');

        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_verified_participant_gets_no_resend(): void
    {
        Notification::fake();

        $user = $this->user(true);
        Sanctum::actingAs($user, ['participant']);

        $this->postJson(self::RESEND)
            ->assertOk()
            ->assertJsonPath('message', 'Your email is already verified.');

        Notification::assertNothingSent();
    }
}
