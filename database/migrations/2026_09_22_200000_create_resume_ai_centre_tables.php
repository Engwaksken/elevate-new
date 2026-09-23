<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('resumes', function (Blueprint $table) {
            if (!Schema::hasColumn('resumes','source')) $table->string('source')->default('manual');
            if (!Schema::hasColumn('resumes','ai_enhanced')) $table->boolean('ai_enhanced')->default(false);
            if (!Schema::hasColumn('resumes','completion_percent')) $table->unsignedTinyInteger('completion_percent')->default(0);
        });

        Schema::create('resume_uploads', function (Blueprint $table) {
            $table->id(); $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('resume_id')->nullable()->constrained()->nullOnDelete();
            $table->string('original_name'); $table->string('stored_name'); $table->string('mime_type',120);
            $table->unsignedBigInteger('file_size'); $table->string('path');
            $table->string('status')->default('uploaded'); $table->longText('extracted_text')->nullable();
            $table->json('parsed_data')->nullable(); $table->text('parsing_error')->nullable();
            $table->timestamp('processed_at')->nullable(); $table->timestamps();
        });

        Schema::create('cover_letters', function (Blueprint $table) {
            $table->id(); $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('resume_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('job_id')->nullable()->constrained('jobs')->nullOnDelete();
            $table->string('title'); $table->string('employer_name')->nullable(); $table->string('job_title')->nullable();
            $table->string('recipient_name')->nullable(); $table->longText('body')->nullable();
            $table->string('source')->default('manual'); $table->boolean('ai_generated')->default(false); $table->timestamps();
        });

        Schema::create('resume_versions', function (Blueprint $table) {
            $table->id(); $table->foreignId('resume_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('version_number'); $table->string('source')->default('participant_edit');
            $table->json('snapshot'); $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps(); $table->unique(['resume_id','version_number']);
        });

        Schema::create('ai_integrations', function (Blueprint $table) {
            $table->id(); $table->string('feature')->unique(); $table->string('provider')->default('openai');
            $table->string('model')->nullable(); $table->string('endpoint')->nullable(); $table->text('encrypted_api_key')->nullable();
            $table->boolean('enabled')->default(false); $table->json('settings')->nullable(); $table->timestamps();
        });

        Schema::create('ai_usage_logs', function (Blueprint $table) {
            $table->id(); $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('feature'); $table->string('provider')->nullable(); $table->string('model')->nullable();
            $table->string('status')->default('success'); $table->unsignedInteger('input_tokens')->default(0);
            $table->unsignedInteger('output_tokens')->default(0); $table->unsignedInteger('duration_ms')->default(0);
            $table->string('error_code')->nullable(); $table->json('metadata')->nullable(); $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('ai_usage_logs'); Schema::dropIfExists('ai_integrations');
        Schema::dropIfExists('resume_versions'); Schema::dropIfExists('cover_letters'); Schema::dropIfExists('resume_uploads');
    }
};
