<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('login_audits') || Schema::hasColumn('login_audits', 'channel')) {
            return;
        }

        Schema::table('login_audits', function (Blueprint $table) {
            // Where the sign-in happened: "web" (browser portal) or "app" (participant mobile app).
            $table->string('channel', 20)->nullable()->after('successful');
            $table->index(['user_id', 'attempted_at']);
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('login_audits') || ! Schema::hasColumn('login_audits', 'channel')) {
            return;
        }

        Schema::table('login_audits', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'attempted_at']);
            $table->dropColumn('channel');
        });
    }
};
