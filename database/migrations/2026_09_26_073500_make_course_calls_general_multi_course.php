<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('course_call_course')) {
            Schema::create('course_call_course', function (Blueprint $table) {
                $table->foreignId('course_call_id')
                    ->constrained('course_calls')
                    ->cascadeOnDelete();

                $table->foreignId('course_id')
                    ->constrained('courses')
                    ->cascadeOnDelete();

                $table->timestamps();

                $table->primary(
                    ['course_call_id', 'course_id'],
                    'course_call_course_primary'
                );
            });
        }

        /*
         * Backfill all existing Course Calls into the new pivot so historical
         * data remains visible after switching to multi-course calls.
         */
        if (
            Schema::hasTable('course_calls')
            && Schema::hasColumn('course_calls', 'course_id')
        ) {
            DB::table('course_calls')
                ->whereNotNull('course_id')
                ->orderBy('id')
                ->chunkById(200, function ($calls): void {
                    foreach ($calls as $call) {
                        DB::table('course_call_course')->updateOrInsert(
                            [
                                'course_call_id' => $call->id,
                                'course_id' => $call->course_id,
                            ],
                            [
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]
                        );
                    }
                });

            /*
             * course_id becomes a legacy/compatibility column. New Course Calls
             * use the pivot table as the source of truth.
             */
            Schema::table('course_calls', function (Blueprint $table) {
                $table->unsignedBigInteger('course_id')->nullable()->change();
            });
        }
    }

    public function down(): void
    {
        /*
         * Keep course_id nullable on rollback to avoid invalidating general
         * Course Calls that can contain multiple courses.
         */
        Schema::dropIfExists('course_call_course');
    }
};
