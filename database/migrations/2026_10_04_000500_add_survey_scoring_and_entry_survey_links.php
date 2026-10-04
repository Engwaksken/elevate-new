<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('surveys', function (Blueprint $table) {
            if (! Schema::hasColumn('surveys', 'is_scored')) {
                $table->boolean('is_scored')->default(false)->after('anonymous_allowed');
            }
            if (! Schema::hasColumn('surveys', 'pass_mark')) {
                $table->decimal('pass_mark', 5, 2)->nullable()->after('is_scored');
            }
        });

        Schema::table('survey_questions', function (Blueprint $table) {
            if (! Schema::hasColumn('survey_questions', 'marks')) {
                $table->decimal('marks', 6, 2)->default(1)->after('hint');
            }
            if (! Schema::hasColumn('survey_questions', 'correct_answer')) {
                $table->json('correct_answer')->nullable()->after('marks');
            }
        });

        Schema::table('survey_responses', function (Blueprint $table) {
            if (! Schema::hasColumn('survey_responses', 'score')) {
                $table->decimal('score', 8, 2)->nullable()->after('submitted_at');
            }
            if (! Schema::hasColumn('survey_responses', 'percentage')) {
                $table->decimal('percentage', 5, 2)->nullable()->after('score');
            }
            if (! Schema::hasColumn('survey_responses', 'passed')) {
                $table->boolean('passed')->nullable()->after('percentage');
            }
            if (! Schema::hasColumn('survey_responses', 'graded_at')) {
                $table->timestamp('graded_at')->nullable()->after('passed');
            }
        });

        Schema::table('courses', function (Blueprint $table) {
            if (! Schema::hasColumn('courses', 'entry_survey_id')) {
                $table->unsignedBigInteger('entry_survey_id')->nullable()->after('entry_assessment_id');
            }
        });

        Schema::table('course_calls', function (Blueprint $table) {
            if (! Schema::hasColumn('course_calls', 'entry_survey_id')) {
                $table->unsignedBigInteger('entry_survey_id')->nullable()->after('entry_assessment_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('surveys', function (Blueprint $table) {
            $table->dropColumn(['is_scored', 'pass_mark']);
        });

        Schema::table('survey_questions', function (Blueprint $table) {
            $table->dropColumn(['marks', 'correct_answer']);
        });

        Schema::table('survey_responses', function (Blueprint $table) {
            $table->dropColumn(['score', 'percentage', 'passed', 'graded_at']);
        });

        Schema::table('courses', function (Blueprint $table) {
            $table->dropColumn('entry_survey_id');
        });

        Schema::table('course_calls', function (Blueprint $table) {
            $table->dropColumn('entry_survey_id');
        });
    }
};
