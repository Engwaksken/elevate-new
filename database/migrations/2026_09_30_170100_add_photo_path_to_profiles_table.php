<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('profiles') && ! Schema::hasColumn('profiles', 'photo_path')) {
            Schema::table('profiles', function (Blueprint $table) {
                // Private-disk path of the participant's profile photo (served via the authenticated API).
                $table->string('photo_path')->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('profiles') && Schema::hasColumn('profiles', 'photo_path')) {
            Schema::table('profiles', function (Blueprint $table) {
                $table->dropColumn('photo_path');
            });
        }
    }
};
