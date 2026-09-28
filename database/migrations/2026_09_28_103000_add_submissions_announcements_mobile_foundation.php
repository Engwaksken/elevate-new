<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('assessment_attempts')) {
            Schema::table('assessment_attempts', function (Blueprint $table) {
                if (! Schema::hasColumn('assessment_attempts', 'client_submission_id')) {
                    $table->string('client_submission_id', 190)->nullable()->unique()->after('attempt_number');
                }
                if (! Schema::hasColumn('assessment_attempts', 'submission_text')) {
                    $table->longText('submission_text')->nullable()->after('client_submission_id');
                }
                if (! Schema::hasColumn('assessment_attempts', 'submission_file_path')) {
                    $table->string('submission_file_path')->nullable()->after('submission_text');
                }
                if (! Schema::hasColumn('assessment_attempts', 'instructor_feedback')) {
                    $table->text('instructor_feedback')->nullable()->after('percentage');
                }
            });
        }

        if (! Schema::hasTable('course_announcements')) {
            Schema::create('course_announcements', function (Blueprint $table) {
                $table->id();
                $table->foreignId('course_id')->constrained()->cascadeOnDelete();
                $table->string('title');
                $table->text('body');
                $table->timestamp('published_at')->nullable();
                $table->timestamp('expires_at')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->softDeletes();

                $table->index(['course_id', 'published_at']);
            });
        }

        if (! Schema::hasTable('participant_device_tokens')) {
            Schema::create('participant_device_tokens', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->string('device_id', 190);
                $table->text('token');
                $table->string('platform', 30)->default('android');
                $table->string('app_version', 50)->nullable();
                $table->timestamp('last_seen_at')->nullable();
                $table->timestamps();

                $table->unique(['user_id', 'device_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('participant_device_tokens');
        Schema::dropIfExists('course_announcements');

        if (Schema::hasTable('assessment_attempts')) {
            Schema::table('assessment_attempts', function (Blueprint $table) {
                foreach (['client_submission_id','submission_text','submission_file_path','instructor_feedback'] as $column) {
                    if (Schema::hasColumn('assessment_attempts', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};
