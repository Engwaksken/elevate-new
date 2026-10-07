<?php

namespace Tests\Feature;

use App\Mail\SystemMail;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class StaffOnboardingNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_staff_receives_login_details_and_a_signed_email_verification_link(): void
    {
        Mail::fake();

        $admin = User::factory()->create(['user_type' => 'staff', 'status' => 'active']);
        $admin->roles()->attach(Role::firstOrCreate(
            ['slug' => 'super-administrator'],
            ['name' => 'Super Administrator']
        ));

        $this->actingAs($admin)->post(route('admin.users.store'), [
            'name' => 'New Instructor',
            'email' => 'new.instructor@example.test',
            'user_type' => 'staff',
            'status' => 'active',
            'password' => 'InitialPass123',
            'password_confirmation' => 'InitialPass123',
            'roles' => [],
        ])->assertRedirect(route('admin.users.index'))->assertSessionHasNoErrors();

        $staff = User::where('email', 'new.instructor@example.test')->firstOrFail();
        $this->assertNull($staff->email_verified_at);

        $verificationUrl = null;
        Mail::assertSent(SystemMail::class, function (SystemMail $mail) use ($staff, &$verificationUrl): bool {
            $this->assertSame($staff->email, $mail->to[0]['address']);
            $this->assertStringContainsString($staff->email, implode(' ', $mail->lines));
            $this->assertStringContainsString('InitialPass123', implode(' ', $mail->lines));
            $this->assertTrue(str_contains($mail->actionUrl ?? '', '/email/verify/'));
            $this->assertTrue(URL::hasValidSignature(\Illuminate\Http\Request::create($mail->actionUrl)));
            $verificationUrl = $mail->actionUrl;

            return true;
        });

        $this->actingAs($staff)->get(route('admin.dashboard'))
            ->assertRedirect(route('verification.notice'));
        $this->actingAs($staff)->get($verificationUrl)
            ->assertRedirect(route('admin.login'));
        $this->assertNotNull($staff->fresh()->email_verified_at);
    }
}
