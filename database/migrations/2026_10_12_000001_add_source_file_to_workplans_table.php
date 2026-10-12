<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('workplans', function (Blueprint $table) {
            $table->string('source_file_path')->nullable();
            $table->string('source_file_name')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('workplans', function (Blueprint $table) {
            $table->dropColumn(['source_file_path', 'source_file_name']);
        });
    }
};
