<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('course_calls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->foreignId('programme_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('cohort_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('entry_assessment_id')->nullable()->constrained('assessments')->nullOnDelete();
            $table->string('title');
            $table->longText('description')->nullable();
            $table->longText('eligibility_criteria')->nullable();
            $table->unsignedInteger('available_slots')->nullable();
            $table->timestamp('opens_at')->nullable();
            $table->timestamp('closes_at')->nullable();
            $table->enum('status', ['draft','published','closed','archived'])->default('draft');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['status','opens_at','closes_at']);
        });

        Schema::create('course_call_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_call_id')->constrained()->cascadeOnDelete();
            $table->string('question_type')->default('short_text');
            $table->text('question_text');
            $table->json('options')->nullable();
            $table->boolean('is_required')->default(false);
            $table->unsignedInteger('position')->default(1);
            $table->timestamps();
        });

        Schema::create('course_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_call_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('assessment_attempt_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('status', ['draft','submitted','shortlisted','approved','waitlisted','rejected'])
                ->default('draft');
            $table->decimal('application_score', 6, 2)->nullable();
            $table->decimal('entry_assessment_score', 6, 2)->nullable();
            $table->text('reviewer_comments')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('enrolled_at')->nullable();
            $table->timestamps();
            $table->unique(['course_call_id','user_id']);
        });

        Schema::create('course_application_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_application_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_call_question_id')->constrained()->cascadeOnDelete();
            $table->longText('answer_text')->nullable();
            $table->json('answer_json')->nullable();
            $table->timestamps();
            $table->unique(['course_application_id','course_call_question_id'], 'course_application_answer_unique');
        });

        if (! Schema::hasColumn('enrolments', 'source_type')) {
            Schema::table('enrolments', function (Blueprint $table) {
                $table->string('source_type')->nullable()->after('cohort_id')->index();
                $table->unsignedBigInteger('source_id')->nullable()->after('source_type');
            });
        }

        Schema::create('surveys', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('slug')->unique();
            $table->enum('access_type', ['public','authenticated','course','cohort','selected'])->default('authenticated');
            $table->foreignId('course_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('cohort_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('programme_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('event_id')->nullable()->constrained('events')->nullOnDelete();
            $table->boolean('allow_draft')->default(true);
            $table->boolean('anonymous_allowed')->default(false);
            $table->unsignedInteger('response_limit')->nullable();
            $table->timestamp('opens_at')->nullable();
            $table->timestamp('closes_at')->nullable();
            $table->enum('status', ['draft','published','closed','archived'])->default('draft');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['status','opens_at','closes_at']);
        });

        Schema::create('survey_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('survey_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->unsignedInteger('position')->default(1);
            $table->timestamps();
        });

        Schema::create('survey_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('survey_id')->constrained()->cascadeOnDelete();
            $table->foreignId('survey_section_id')->nullable()->constrained()->nullOnDelete();
            $table->string('question_type');
            $table->text('question_text');
            $table->text('hint')->nullable();
            $table->json('options')->nullable();
            $table->json('validation_rules')->nullable();
            $table->json('conditional_logic')->nullable();
            $table->boolean('is_required')->default(false);
            $table->unsignedInteger('position')->default(1);
            $table->timestamps();
        });

        Schema::create('survey_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('survey_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['survey_id','user_id']);
        });

        Schema::create('survey_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('survey_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('respondent_token')->nullable()->index();
            $table->enum('status', ['draft','submitted'])->default('draft');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();
            $table->index(['survey_id','user_id','status']);
        });

        Schema::create('survey_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('survey_response_id')->constrained()->cascadeOnDelete();
            $table->foreignId('survey_question_id')->constrained()->cascadeOnDelete();
            $table->longText('answer_text')->nullable();
            $table->json('answer_json')->nullable();
            $table->timestamps();
            $table->unique(['survey_response_id','survey_question_id'], 'survey_answer_unique');
        });

        $permissions = [
            ['module'=>'course_calls','slug'=>'course_calls.view','name'=>'Course Calls View'],
            ['module'=>'course_calls','slug'=>'course_calls.manage','name'=>'Course Calls Manage'],
            ['module'=>'course_calls','slug'=>'applications.review','name'=>'Applications Review'],
            ['module'=>'course_calls','slug'=>'entry_assessments.review','name'=>'Entry Assessments Review'],
            ['module'=>'surveys','slug'=>'surveys.view','name'=>'Surveys View'],
            ['module'=>'surveys','slug'=>'surveys.manage','name'=>'Surveys Manage'],
            ['module'=>'surveys','slug'=>'survey_responses.view','name'=>'Survey Responses View'],
            ['module'=>'surveys','slug'=>'survey_responses.export','name'=>'Survey Responses Export'],
            ['module'=>'settings','slug'=>'settings.branding','name'=>'Settings Branding'],
            ['module'=>'settings','slug'=>'settings.backups','name'=>'Settings Backups'],
            ['module'=>'settings','slug'=>'settings.maintenance','name'=>'Settings Maintenance'],
        ];

        foreach ($permissions as $permission) {
            DB::table('permissions')->updateOrInsert(
                ['slug'=>$permission['slug']],
                array_merge($permission, ['description'=>null,'created_at'=>now(),'updated_at'=>now()])
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('survey_answers');
        Schema::dropIfExists('survey_responses');
        Schema::dropIfExists('survey_assignments');
        Schema::dropIfExists('survey_questions');
        Schema::dropIfExists('survey_sections');
        Schema::dropIfExists('surveys');

        if (Schema::hasColumn('enrolments','source_type')) {
            Schema::table('enrolments', function (Blueprint $table) {
                $table->dropColumn(['source_type','source_id']);
            });
        }

        Schema::dropIfExists('course_application_answers');
        Schema::dropIfExists('course_applications');
        Schema::dropIfExists('course_call_questions');
        Schema::dropIfExists('course_calls');
    }
};
