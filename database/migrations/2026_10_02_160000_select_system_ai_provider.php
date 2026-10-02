<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $selected = DB::table('ai_integrations')->where('enabled', true)->orderByRaw("CASE WHEN feature = 'career_ai' THEN 0 ELSE 1 END")->orderBy('id')->first();
        if ($selected && ! DB::table('ai_integrations')->where('feature', 'system_ai')->exists()) {
            DB::table('ai_integrations')->insert(['feature' => 'system_ai', 'provider' => $selected->provider, 'model' => $selected->model, 'endpoint' => $selected->endpoint, 'encrypted_api_key' => $selected->encrypted_api_key, 'enabled' => (bool) ($selected->model && $selected->encrypted_api_key), 'settings' => $selected->settings, 'created_at' => now(), 'updated_at' => now()]);
        }
        if (! DB::table('ai_integrations')->where('feature', 'system_ai')->exists()) {
            DB::table('ai_integrations')->insert(['feature' => 'system_ai', 'provider' => 'openai', 'enabled' => false, 'created_at' => now(), 'updated_at' => now()]);
        }
        DB::table('ai_integrations')->where('feature', '!=', 'system_ai')->update(['enabled' => false]);
    }
    public function down(): void { /* Existing provider configurations are retained. */ }
};
