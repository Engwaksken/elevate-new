<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MigrationBatch;
use App\Models\MigrationStagingRecord;
use App\Services\LegacyIdentityMatcher;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class MigrationController extends Controller
{
    public function index(Request $request)
    {
        $query=MigrationBatch::latest();

        if($search=trim((string)$request->get('search'))){
            $query->where(function($q) use($search){
                $q->where('batch_name','like',"%{$search}%")
                    ->orWhere('source_system','like',"%{$search}%")
                    ->orWhere('source_file','like',"%{$search}%");
            });
        }

        if($source=$request->get('source_system')){
            $query->where('source_system',$source);
        }

        if($status=$request->get('status')){
            $query->where('status',$status);
        }

        $perPage=in_array((int)$request->get('per_page'),[10,20,25,50,100],true)
            ? (int)$request->get('per_page')
            : 20;

        return view('admin.migrations.index',[
            'batches'=>$query->paginate($perPage)->withQueryString(),
            'stats'=>[
                'total'=>MigrationBatch::count(),
                'validated'=>MigrationBatch::where('status','validated')->count(),
                'completed'=>MigrationBatch::where('status','completed')->count(),
                'failed'=>MigrationBatch::where('status','failed')->count(),
            ],
        ]);
    }

    public function create()
    {
        return redirect()
            ->route('admin.migrations.index')
            ->with('open_migration_modal',true);
    }

    public function store(Request $request)
    {
        $data=$request->validate([
            'source_system'=>['required','in:elearning,mentorship,jobs,library,other'],
            'batch_name'=>['required','string','max:190'],
            'payload'=>['required','file','mimes:csv,txt','max:10240'],
        ]);

        $handle=fopen($request->file('payload')->getRealPath(),'r');

        if($handle===false){
            throw ValidationException::withMessages([
                'payload'=>'The uploaded CSV could not be opened.',
            ]);
        }

        $headers=fgetcsv($handle);

        if(!is_array($headers) || $headers===[]){
            fclose($handle);
            throw ValidationException::withMessages([
                'payload'=>'The CSV file does not contain a valid header row.',
            ]);
        }

        $headers=array_map(function($header,$index){
            $header=(string)$header;

            if($index===0){
                $header=preg_replace('/^\xEF\xBB\xBF/','',$header) ?? $header;
            }

            return Str::of($header)
                ->trim()
                ->lower()
                ->replace([' ','-','.'],'_')
                ->replaceMatches('/[^a-z0-9_]/','')
                ->toString();
        },$headers,array_keys($headers));

        if(in_array('', $headers, true)){
            fclose($handle);
            throw ValidationException::withMessages([
                'payload'=>'Every CSV column must have a header.',
            ]);
        }

        if(count($headers)!==count(array_unique($headers))){
            fclose($handle);
            throw ValidationException::withMessages([
                'payload'=>'CSV headers must be unique after normalisation.',
            ]);
        }

        $batch=MigrationBatch::create([
            'source_system'=>$data['source_system'],
            'batch_name'=>$data['batch_name'],
            'source_file'=>$request->file('payload')->getClientOriginalName(),
            'created_by'=>auth()->id(),
            'status'=>'draft',
        ]);

        $count=0;
        $failed=0;

        while(($row=fgetcsv($handle))!==false){
            if($row===[null] || $row===[]){
                continue;
            }

            if(count($headers)!==count($row)){
                $failed++;
                continue;
            }

            $payload=array_combine($headers,array_map(
                fn($value)=>is_string($value) ? trim($value) : $value,
                $row
            ));

            if($payload===false){
                $failed++;
                continue;
            }

            $sourceId=$payload['source_record_id']
                ?? $payload['source_id']
                ?? $payload['unique_id']
                ?? $payload['id']
                ?? null;

            MigrationStagingRecord::create([
                'migration_batch_id'=>$batch->id,
                'source_record_id'=>$sourceId ? (string)$sourceId : null,
                'entity_type'=>'user',
                'source_payload'=>$payload,
                'normalised_payload'=>$payload,
                'validation_errors'=>null,
            ]);

            $count++;
        }

        fclose($handle);

        $batch->update([
            'total_rows'=>$count,
            'failed_rows'=>$failed,
            'status'=>'validated',
        ]);

        return redirect()
            ->route('admin.migrations.show',$batch)
            ->with('success',
                $failed>0
                    ? "Migration batch staged. {$failed} malformed row(s) were skipped."
                    : 'Migration batch staged successfully.'
            );
    }

    public function show(
        Request $request,
        MigrationBatch $migration,
        LegacyIdentityMatcher $matcher
    ) {
        $matchingQuery=$migration->records()->whereNull('match_status');

        foreach($matchingQuery->cursor() as $record){
            $result=$matcher->match($record->source_payload);

            $record->update([
                'match_status'=>$result['status'],
                'matched_user_id'=>$result['user']?->id,
            ]);
        }

        $query=$migration->records()->with('matchedUser')->orderBy('id');

        if($status=$request->get('match_status')){
            $query->where('match_status',$status);
        }

        if($search=trim((string)$request->get('search'))){
            $query->where(function($q) use($search){
                $q->where('source_record_id','like',"%{$search}%")
                    ->orWhere('source_payload->email','like',"%{$search}%")
                    ->orWhere('source_payload->phone','like',"%{$search}%")
                    ->orWhere('source_payload->name','like',"%{$search}%");
            });
        }

        $perPage=in_array((int)$request->get('per_page'),[25,50,100,200],true)
            ? (int)$request->get('per_page')
            : 50;

        $records=$query->paginate($perPage)->withQueryString();

        return view('admin.migrations.show',[
            'batch'=>$migration->fresh(),
            'records'=>$records,
            'stats'=>[
                'total'=>$migration->records()->count(),
                'matched'=>$migration->records()->where('match_status','matched')->count(),
                'ambiguous'=>$migration->records()->where('match_status','ambiguous')->count(),
                'unmatched'=>$migration->records()->where('match_status','unmatched')->count(),
            ],
        ]);
    }
}
