<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('migration_batches', function (Blueprint $table) {
            $table->id();
            $table->string('source_system');
            $table->string('batch_name');
            $table->string('source_file')->nullable();
            $table->unsignedInteger('total_rows')->default(0);
            $table->unsignedInteger('processed_rows')->default(0);
            $table->unsignedInteger('successful_rows')->default(0);
            $table->unsignedInteger('failed_rows')->default(0);
            $table->enum('status', ['draft','validated','processing','completed','failed'])->default('draft');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('migration_staging_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('migration_batch_id')->constrained()->cascadeOnDelete();
            $table->string('source_table')->nullable();
            $table->string('source_record_id')->nullable();
            $table->string('entity_type')->index();
            $table->json('source_payload');
            $table->json('normalised_payload')->nullable();
            $table->string('match_status')->nullable()->index();
            $table->foreignId('matched_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->json('validation_errors')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->index(['migration_batch_id','entity_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('migration_staging_records');
        Schema::dropIfExists('migration_batches');
    }
};
