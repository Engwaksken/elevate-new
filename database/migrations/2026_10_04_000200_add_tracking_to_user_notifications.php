<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_notifications', function (Blueprint $table) {
            if (! Schema::hasColumn('user_notifications', 'tracking_token')) {
                $table->string('tracking_token', 64)->nullable()->unique()->after('action_url');
            }
            if (! Schema::hasColumn('user_notifications', 'opened_at')) {
                $table->timestamp('opened_at')->nullable()->after('read_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('user_notifications', function (Blueprint $table) {
            $table->dropColumn(['tracking_token', 'opened_at']);
        });
    }
};
