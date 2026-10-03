<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('participant_goals')) {
            return;
        }

        Schema::table('participant_goals', function (Blueprint $table) {
            if (! Schema::hasColumn('participant_goals', 'mentor_comment')) {
                $table->text('mentor_comment')->nullable()->after('created_by');
            }

            if (! Schema::hasColumn('participant_goals', 'mentor_reviewed_at')) {
                $table->timestamp('mentor_reviewed_at')->nullable()->after('mentor_comment');
            }

            if (! Schema::hasColumn('participant_goals', 'mentor_reviewed_by')) {
                $table->foreignId('mentor_reviewed_by')->nullable()->after('mentor_reviewed_at')
                    ->constrained('users')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('participant_goals')) {
            return;
        }

        Schema::table('participant_goals', function (Blueprint $table) {
            if (Schema::hasColumn('participant_goals', 'mentor_reviewed_by')) {
                $table->dropConstrainedForeignId('mentor_reviewed_by');
            }

            foreach (['mentor_comment', 'mentor_reviewed_at'] as $column) {
                if (Schema::hasColumn('participant_goals', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
