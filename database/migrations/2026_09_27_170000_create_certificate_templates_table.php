<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('certificate_templates')) {
            Schema::create('certificate_templates', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('context_type')->default('course');
                $table->foreignId('course_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('event_id')->nullable()->constrained()->nullOnDelete();
                $table->string('background_path');
                $table->string('orientation')->default('landscape');
                $table->boolean('is_active')->default(true);
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('certificate_templates');
    }
};