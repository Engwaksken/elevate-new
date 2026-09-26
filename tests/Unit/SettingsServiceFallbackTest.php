<?php

namespace Tests\Unit;

use App\Services\SettingsService;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SettingsServiceFallbackTest extends TestCase
{
    public function test_get_returns_default_when_settings_table_does_not_exist(): void
    {
        Schema::dropIfExists('system_settings');

        $value = app(SettingsService::class)->get(
            'maintenance.enabled',
            false
        );

        $this->assertFalse($value);
    }
}
