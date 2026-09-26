<?php

namespace Tests\Unit;

use App\Http\Middleware\EnsureParticipantUser;
use Tests\TestCase;

class Phase26ParticipantMiddlewareTest extends TestCase
{
    public function test_participant_middleware_class_is_available(): void
    {
        $this->assertTrue(class_exists(EnsureParticipantUser::class));
    }

    public function test_phase26_commands_are_available_as_classes(): void
    {
        $this->assertTrue(
            class_exists(\App\Console\Commands\Phase26ReconcilePermissions::class)
        );

        $this->assertTrue(
            class_exists(\App\Console\Commands\Phase26ProductionReadiness::class)
        );
    }
}
