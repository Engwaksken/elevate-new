<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ForgotPasswordPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_participant_forgot_password_page_renders_in_the_participant_shell(): void
    {
        $this->get(route('password.request'))
            ->assertOk()
            ->assertSee('Participant Portal')
            ->assertSee('Back to participant sign in');
    }

    public function test_staff_forgot_password_page_renders_in_the_staff_shell(): void
    {
        $this->get(route('admin.password.request'))
            ->assertOk()
            ->assertSee('WITU STAFF PORTAL')
            ->assertSee('Back to staff sign in');
    }

    public function test_staff_can_request_a_reset_link(): void
    {
        User::factory()->create(['email' => 'staff@witu.org', 'user_type' => 'staff', 'status' => 'active']);

        $this->post(route('admin.password.email'), ['email' => 'staff@witu.org'])
            ->assertRedirect()
            ->assertSessionHas('success');
    }
}
