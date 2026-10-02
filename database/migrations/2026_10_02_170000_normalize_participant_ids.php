<?php

use App\Services\EnrolmentIdentityService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('participant_id_aliases', function (Blueprint $table) {
            $table->id(); $table->foreignId('user_id')->constrained()->cascadeOnDelete(); $table->string('alias', 190)->unique();
        });
        $service = app(EnrolmentIdentityService::class);
        DB::transaction(function () use ($service) {
            $counters = [];
            foreach (DB::table('enrolment_identity_sequences')->get() as $sequence) {
                $prefix = substr($service->normalizeCode($sequence->prefix.'/001'), 0, -4);
                $counters[$prefix] = max($counters[$prefix] ?? 0, $sequence->last_number);
            }
            $changes = []; $mapping = []; $used = [];
            $enrolments = DB::table('enrolments')->whereNotNull('enrolment_code')->orderBy('id')->get();
            foreach ($enrolments as $enrolment) {
                if ($service->normalizeCode($enrolment->enrolment_code) === $enrolment->enrolment_code) $used[$enrolment->enrolment_code] = $enrolment->id;
            }
            foreach ($enrolments as $enrolment) {
                $old = $enrolment->enrolment_code;
                $new = $service->normalizeCode($old);
                $parts = explode('/', $new);
                if (count($parts) === 5 && ctype_digit($parts[4])) {
                    $prefix = implode('/', array_slice($parts, 0, 4));
                    $counters[$prefix] = max($counters[$prefix] ?? 0, (int) $parts[4]);
                    while (isset($used[$new]) && $used[$new] !== $enrolment->id) $new = $prefix.'/'.str_pad((string) ++$counters[$prefix], 3, '0', STR_PAD_LEFT);
                }
                $used[$new] = $enrolment->id;
                $changes[$enrolment->id] = $new;
                $mapping[$old] = $new;
            }
            // Temporary unique values avoid collisions while legacy prefixes merge.
            foreach ($changes as $id => $code) DB::table('enrolments')->where('id', $id)->update(['enrolment_code' => 'ID-MIGRATION-'.$id]);
            foreach ($changes as $id => $code) DB::table('enrolments')->where('id', $id)->update(['enrolment_code' => $code]);
            $users = DB::table('users')->where('user_type', 'participant')->whereIn('participant_code', array_keys($mapping))->get();
            foreach ($users as $user) {
                if ($mapping[$user->participant_code] !== $user->participant_code) DB::table('participant_id_aliases')->insert(['user_id' => $user->id, 'alias' => $user->participant_code]);
                DB::table('users')->where('id', $user->id)->update(['participant_code' => 'PID-MIGRATION-'.$user->id]);
            }
            foreach ($users as $user) DB::table('users')->where('id', $user->id)->update(['participant_code' => $mapping[$user->participant_code]]);
            foreach ($counters as $prefix => $number) DB::table('enrolment_identity_sequences')->updateOrInsert(['prefix' => $prefix], ['last_number' => $number]);
        });
    }
    public function down(): void { Schema::dropIfExists('participant_id_aliases'); }
};
