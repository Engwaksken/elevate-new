<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Move the single "system_ai" row to a per-provider row ("system_ai:{provider}")
     * so several providers can be saved and switched on or off from a table.
     */
    public function up(): void
    {
        $legacy = DB::table('ai_integrations')->where('feature', 'system_ai')->first();
        if (! $legacy) {
            return;
        }

        $feature = 'system_ai:'.($legacy->provider ?: 'openai');
        if (DB::table('ai_integrations')->where('feature', $feature)->exists()) {
            return;
        }

        DB::table('ai_integrations')->where('id', $legacy->id)->update(['feature' => $feature]);
    }

    public function down(): void
    {
        $active = DB::table('ai_integrations')->where('feature', 'like', 'system_ai:%')->orderByDesc('enabled')->orderByDesc('updated_at')->first();
        if ($active && ! DB::table('ai_integrations')->where('feature', 'system_ai')->exists()) {
            DB::table('ai_integrations')->where('id', $active->id)->update(['feature' => 'system_ai']);
        }
    }
};
