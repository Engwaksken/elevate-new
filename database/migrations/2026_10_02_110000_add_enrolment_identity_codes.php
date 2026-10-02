<?php

use App\Models\Enrolment;
use App\Services\EnrolmentIdentityService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // SQLite does not enforce VARCHAR lengths; rebuilding users there can
        // cascade-delete dependent rows. Other databases need the larger column.
        if (DB::getDriverName() !== 'sqlite') {
            Schema::table('users', function (Blueprint $table) {
                $table->string('participant_code', 190)->nullable()->change();
            });
        }
        Schema::create('enrolment_identity_sequences', function (Blueprint $table) {
            $table->string('prefix', 150)->primary();
            $table->unsignedBigInteger('last_number')->default(0);
        });
        Schema::table('enrolments', function (Blueprint $table) {
            $table->string('enrolment_code', 190)->nullable()->unique();
        });

        $service = app(EnrolmentIdentityService::class);
        Enrolment::whereNull('enrolment_code')->orderBy('id')->chunkById(200, function ($enrolments) use ($service) {
            foreach ($enrolments as $enrolment) {
                DB::transaction(function () use ($enrolment, $service) {
                    $enrolment->enrolment_code = $service->allocate($enrolment);
                    $enrolment->saveQuietly();
                    $service->assignParticipantCode($enrolment);
                });
            }
        });
    }

    public function down(): void
    {
        Schema::table('enrolments', function (Blueprint $table) {
            $table->dropUnique(['enrolment_code']);
            $table->dropColumn('enrolment_code');
        });
        Schema::dropIfExists('enrolment_identity_sequences');
    }
};
