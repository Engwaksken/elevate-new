<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'participant_code')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('participant_code', 20)->nullable()->unique()->after('id');
            });
        }

        // Backfill existing participants: EH + registration year + zero-padded account id.
        DB::table('users')
            ->where('user_type', 'participant')
            ->whereNull('participant_code')
            ->orderBy('id')
            ->select(['id', 'created_at'])
            ->chunkById(500, function ($users) {
                foreach ($users as $user) {
                    $year = $user->created_at ? date('y', strtotime($user->created_at)) : date('y');

                    DB::table('users')->where('id', $user->id)->update([
                        'participant_code' => sprintf('EH%s-%06d', $year, $user->id),
                    ]);
                }
            });

        if (! Schema::hasTable('branch_user')) {
            Schema::create('branch_user', function (Blueprint $table) {
                $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->timestamps();
                $table->primary(['branch_id', 'user_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('branch_user');

        if (Schema::hasColumn('users', 'participant_code')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropUnique(['participant_code']);
                $table->dropColumn('participant_code');
            });
        }
    }
};
