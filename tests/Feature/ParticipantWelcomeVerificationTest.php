<?php

namespace Tests\Feature;

use App\Mail\SystemMail;
use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ParticipantWelcomeVerificationTest extends TestCase
{
    use RefreshDatabase;

    private function register(): User
    {
        $this->post(route('register.store'), [
            'surname' => 'Nansubuga',
            'given_name' => 'Sarah',
            'email' => 'sarah.welcome@gmail.com',
            'phone' => '+256700000000',
            'password' => 'StrongPass1',
            'password_confirmation' => 'StrongPass1',
            'terms' => '1',
        ])->assertRedirect(route('verification.notice'));

        return User::where('email', 'sarah.welcome@gmail.com')->firstOrFail();
    }

    public function test_the_welcome_email_has_a_verify_button(): void
    {
        Mail::fake();
        Notification::fake();

        $this->register();

        Mail::assertSent(SystemMail::class, function (SystemMail $mail) {
            return $mail->hasTo('sarah.welcome@gmail.com')
                && str_starts_with($mail->heading, 'Welcome to')
                && $mail->actionLabel === 'Verify email address'
                && str_contains((string) $mail->actionUrl, '/email/verify/');
        });

        // One welcome email only: Laravel's separate default verification email is not sent.
        Notification::assertNothingSent();
        Mail::assertSent(SystemMail::class, fn (SystemMail $mail) => $mail->hasTo('sarah.welcome@gmail.com'));
        $this->assertCount(1, Mail::sent(SystemMail::class, fn (SystemMail $mail) => $mail->hasTo('sarah.welcome@gmail.com')));
    }

    public function test_the_verify_link_works_without_being_signed_in(): void
    {
        Mail::fake();

        $user = $this->register();
        $url = null;
        Mail::assertSent(SystemMail::class, function (SystemMail $mail) use (&$url) {
            $url = $mail->actionUrl;
            return $mail->hasTo('sarah.welcome@gmail.com');
        });

        auth()->logout();

        $this->get($url)->assertRedirect();

        $this->assertTrue($user->fresh()->hasVerifiedEmail());
        $this->assertGuest();
    }

    public function test_a_tampered_verify_link_is_rejected(): void
    {
        $user = User::factory()->unverified()->create(['user_type' => 'participant']);

        $url = str_replace(sha1($user->email), sha1('someone@else.com'), $user->emailVerificationUrl());

        $this->get($url)->assertForbidden();
        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }

    public function test_resend_sends_the_branded_verify_email(): void
    {
        Mail::fake();
        Notification::fake();

        $user = User::factory()->unverified()->create(['user_type' => 'participant']);

        $this->actingAs($user)->post(route('verification.send'))->assertSessionHas('success');

        Notification::assertSentTo($user, VerifyEmail::class, function (VerifyEmail $notification) use ($user) {
            $mail = $notification->toMail($user);

            return $mail instanceof SystemMail
                && $mail->hasTo($user->email)
                && $mail->actionLabel === 'Verify email address'
                && str_contains((string) $mail->actionUrl, '/email/verify/');
        });
    }
}
