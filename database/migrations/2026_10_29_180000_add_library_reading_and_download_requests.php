<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Per-book choice: "free" downloads (existing behaviour) or "request" (staff approve each download).
        Schema::table('library_resources', function (Blueprint $table) {
            $table->string('download_policy', 20)->default('free')->after('access_level');
        });

        // Where each reader is in each book, so she can resume and track her reading.
        Schema::create('library_reading_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('library_resource_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('current_page')->nullable();
            $table->unsignedInteger('total_pages')->nullable();
            $table->decimal('percent', 5, 2)->default(0);
            $table->unsignedInteger('opened_count')->default(0);
            $table->timestamp('first_opened_at')->nullable();
            $table->timestamp('last_read_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'library_resource_id']);
            $table->index(['user_id', 'last_read_at']);
        });

        Schema::create('library_download_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('library_resource_id')->constrained()->cascadeOnDelete();
            $table->text('reason')->nullable();
            $table->string('status', 20)->default('pending');
            $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('decided_at')->nullable();
            $table->text('decision_note')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
            $table->index(['status', 'created_at']);
            $table->index(['user_id', 'library_resource_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('library_download_requests');
        Schema::dropIfExists('library_reading_progress');
        Schema::table('library_resources', function (Blueprint $table) {
            $table->dropColumn('download_policy');
        });
    }
};
