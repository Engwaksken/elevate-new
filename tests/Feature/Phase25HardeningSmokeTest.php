<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase25HardeningSmokeTest extends TestCase
{
    public function test_phase25_hardening_files_are_loadable(): void
    {
        $this->assertTrue(class_exists(\App\Models\PlatformBackup::class));
        $this->assertTrue(class_exists(\App\Console\Commands\PlatformBackupCommand::class));
        $this->assertTrue(class_exists(\App\Http\Controllers\Admin\SurveyController::class));
    }
}
