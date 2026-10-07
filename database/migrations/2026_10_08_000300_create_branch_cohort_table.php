<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('branch_cohort', function (Blueprint $table) {
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('cohort_id')->constrained()->cascadeOnDelete();
            $table->primary(['branch_id', 'cohort_id']);
        });

        DB::table('cohorts')
            ->whereNotNull('branch_id')
            ->orderBy('id')
            ->get(['id', 'branch_id'])
            ->each(fn ($cohort) => DB::table('branch_cohort')->insert([
                'branch_id' => $cohort->branch_id,
                'cohort_id' => $cohort->id,
            ]));
    }

    public function down(): void
    {
        Schema::dropIfExists('branch_cohort');
    }
};
