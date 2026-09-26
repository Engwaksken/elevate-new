<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('events')) {
            Schema::table('events', function (Blueprint $table) {
                if (!Schema::hasColumn('events','feedback_enabled')) {
                    $table->boolean('feedback_enabled')->default(false);
                }

                if (!Schema::hasColumn('events','certificate_enabled')) {
                    $table->boolean('certificate_enabled')->default(false);
                }

                if (!Schema::hasColumn('events','certificate_requires_feedback')) {
                    $table->boolean('certificate_requires_feedback')->default(false);
                }

                if (!Schema::hasColumn('events','certificate_title')) {
                    $table->string('certificate_title')->nullable();
                }

                if (!Schema::hasColumn('events','certificate_signatory_name')) {
                    $table->string('certificate_signatory_name')->nullable();
                }

                if (!Schema::hasColumn('events','certificate_signatory_title')) {
                    $table->string('certificate_signatory_title')->nullable();
                }
            });
        }

        if (!Schema::hasTable('event_feedback_responses')) {
            Schema::create('event_feedback_responses', function (Blueprint $table) {
                $table->id();
                $table->foreignId('event_id')->constrained()->cascadeOnDelete();
                $table->foreignId('event_registration_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
                $table->unsignedTinyInteger('overall_rating');
                $table->unsignedTinyInteger('relevance_rating');
                $table->unsignedTinyInteger('facilitation_rating');
                $table->unsignedTinyInteger('organisation_rating');
                $table->unsignedTinyInteger('recommend_rating');
                $table->text('key_learning')->nullable();
                $table->text('what_worked')->nullable();
                $table->text('what_to_improve')->nullable();
                $table->text('additional_comments')->nullable();
                $table->timestamp('submitted_at')->nullable();
                $table->timestamps();

                $table->unique(['event_id','user_id'],'event_feedback_user_unique');
            });
        }

        if (!Schema::hasTable('event_certificates')) {
            Schema::create('event_certificates', function (Blueprint $table) {
                $table->id();
                $table->foreignId('event_id')->constrained()->cascadeOnDelete();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->foreignId('event_attendance_record_id')->nullable()->constrained()->nullOnDelete();
                $table->uuid('certificate_code')->unique();
                $table->timestamp('issued_at');
                $table->foreignId('issued_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->unique(['event_id','user_id'],'event_certificate_unique');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('event_certificates');
        Schema::dropIfExists('event_feedback_responses');

        if (Schema::hasTable('events')) {
            Schema::table('events', function (Blueprint $table) {
                foreach ([
                    'feedback_enabled',
                    'certificate_enabled',
                    'certificate_requires_feedback',
                    'certificate_title',
                    'certificate_signatory_name',
                    'certificate_signatory_title',
                ] as $column) {
                    if (Schema::hasColumn('events',$column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};
