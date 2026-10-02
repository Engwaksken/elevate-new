<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function () {
            $rows = DB::table('enrolments')->whereNotNull('enrolment_code')->orderBy('id')->get();
            $sequences = DB::table('enrolment_identity_sequences')->pluck('last_number', 'prefix')->all();
            $counters = []; $reserved = []; $occupied = []; $changes = []; $mapping = [];
            foreach (DB::table('participant_id_aliases')->get() as $alias) $reserved[$alias->alias] = $alias->user_id;
            foreach (DB::table('users')->whereNotNull('participant_code')->get(['id','participant_code']) as $user) $reserved[$user->participant_code] = $user->id;
            foreach ($rows as $row) {
                $parts = explode('/', $row->enrolment_code);
                if (count($parts) !== 5 || ! ctype_digit($parts[4])) continue;
                $reserved[$row->enrolment_code] = $row->user_id;
                $oldPrefix = implode('/', array_slice($parts, 0, 4));
                $parts[2] = 'C'.$row->course_id;
                $newPrefix = implode('/', array_slice($parts, 0, 4));
                $counters[$newPrefix] = max($counters[$newPrefix] ?? 0, $sequences[$oldPrefix] ?? 0, $sequences[$newPrefix] ?? 0, (int) $parts[4]);
                $candidate = $newPrefix.'/'.str_pad($parts[4], 3, '0', STR_PAD_LEFT);
                if ($candidate === $row->enrolment_code) $occupied[$candidate] = $row->id;
                $changes[$row->id] = ['old'=>$row->enrolment_code,'candidate'=>$candidate,'prefix'=>$newPrefix,'user_id'=>$row->user_id,'course_id'=>$row->course_id];
            }
            foreach ($changes as $id => &$change) {
                $candidate = $change['candidate'];
                while ((isset($occupied[$candidate]) && $occupied[$candidate] !== $id)
                    || (isset($reserved[$candidate]) && $reserved[$candidate] !== $change['user_id'])) {
                    $candidate = $change['prefix'].'/'.str_pad((string) ++$counters[$change['prefix']], 3, '0', STR_PAD_LEFT);
                }
                $occupied[$candidate] = $id;
                $change['new'] = $candidate;
                $mapping[$change['old']] = $candidate;
                if ($candidate !== $change['old']) DB::table('participant_id_aliases')->insertOrIgnore(['user_id'=>$change['user_id'],'alias'=>$change['old']]);
            }
            unset($change);
            foreach ($changes as $id=>$change) DB::table('enrolments')->where('id',$id)->update(['enrolment_code'=>'COURSE-ID-MIGRATION-'.$id]);
            foreach ($changes as $id=>$change) {
                DB::table('enrolments')->where('id',$id)->update(['enrolment_code'=>$change['new']]);
                if ($change['old'] !== $change['new']) {
                    // Re-render issued course PDFs on next access with the corrected ID.
                    DB::table('certificates')->where('user_id',$change['user_id'])->where('course_id',$change['course_id'])->update(['pdf_path'=>null]);
                }
            }
            $users = DB::table('users')->where('user_type','participant')->whereIn('participant_code',array_keys($mapping))->get(['id','participant_code']);
            foreach ($users as $user) DB::table('users')->where('id',$user->id)->update(['participant_code'=>'COURSE-PID-MIGRATION-'.$user->id]);
            foreach ($users as $user) DB::table('users')->where('id',$user->id)->update(['participant_code'=>$mapping[$user->participant_code]]);
            foreach ($counters as $prefix=>$last) DB::table('enrolment_identity_sequences')->updateOrInsert(['prefix'=>$prefix],['last_number'=>$last]);
            // Existing server-generated PDFs previously printed the account's ID,
            // which could refer to another course. Preserve imported/custom files.
            foreach (DB::table('certificates')->whereNotNull('course_id')->whereNotNull('pdf_path')->get(['id','certificate_number','pdf_path']) as $certificate) {
                if ($certificate->pdf_path === 'certificates/'.$certificate->certificate_number.'.pdf') DB::table('certificates')->where('id',$certificate->id)->update(['pdf_path'=>null]);
            }
        });
    }
    public function down(): void { /* Participant IDs and aliases remain stable. */ }
};
