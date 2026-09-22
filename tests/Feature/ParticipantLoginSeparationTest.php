<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ParticipantLoginSeparationTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_cannot_use_participant_login(): void
    {
        $user=User::factory()->create([
            'email'=>'staff@example.com',
            'user_type'=>'staff',
            'status'=>'active',
            'password'=>bcrypt('Password123'),
        ]);

        $response=$this->post('/login',[
            'email'=>$user->email,
            'password'=>'Password123',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }
}
