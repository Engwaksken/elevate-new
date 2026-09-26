<?php

namespace Tests\Unit;

use App\Models\User;
use Tests\TestCase;

class UserAccessHelpersTest extends TestCase
{
    public function test_inactive_user_is_not_active(): void
    {
        $user = new User([
            'user_type' => 'staff',
            'status' => 'inactive',
        ]);

        $this->assertFalse($user->isActive());
    }

    public function test_active_staff_user_is_active_staff(): void
    {
        $user = new User([
            'user_type' => 'staff',
            'status' => 'active',
        ]);

        $this->assertTrue($user->isActive());
        $this->assertTrue($user->isStaff());
        $this->assertFalse($user->isParticipant());
    }
}
