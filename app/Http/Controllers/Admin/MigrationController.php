<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MigrationBatch;
use App\Models\MigrationStagingRecord;
use App\Services\LegacyIdentityMatcher;
use Illuminate\Http\Request;

class MigrationController extends Controller
{
    public function index()
    {
        return view('admin.migrations.index', [
            'batches' => MigrationBatch::latest()->paginate(20),
        ]);
    }

    public function create()
    {
        return view('admin.migrations.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'source_system'=>['required','in:elearning,mentorship,jobs,library,other'],
            'batch_name'=>['required','string','max:190'],
            'payload'=>['required','file','mimes:csv,txt','max:10240'],
        ]);

        $batch = MigrationBatch::create([
            'source_system'=>$data['source_system'],
            'batch_name'=>$data['batch_name'],
            'source_file'=>$request->file('payload')->getClientOriginalName(),
            'created_by'=>auth()->id(),
            'status'=>'draft',
        ]);

        $handle = fopen($request->file('payload')->getRealPath(), 'r');
        $headers = fgetcsv($handle);
        $count = 0;

        while (($row = fgetcsv($handle)) !== false) {
            if (count($headers) !== count($row)) continue;

            MigrationStagingRecord::create([
                'migration_batch_id'=>$batch->id,
                'entity_type'=>'user',
                'source_payload'=>array_combine($headers,$row),
            ]);
            $count++;
        }
        fclose($handle);

        $batch->update(['total_rows'=>$count,'status'=>'validated']);

        return redirect()->route('admin.migrations.show',$batch)->with('success','Migration batch staged.');
    }

    public function show(MigrationBatch $migration, LegacyIdentityMatcher $matcher)
    {
        $records = $migration->records()->paginate(50);

        foreach ($records as $record) {
            if (!$record->match_status) {
                $result = $matcher->match($record->source_payload);
                $record->update([
                    'match_status'=>$result['status'],
                    'matched_user_id'=>$result['user']?->id,
                ]);
            }
        }

        return view('admin.migrations.show', [
            'batch'=>$migration->fresh(),
            'records'=>$records,
        ]);
    }
}
