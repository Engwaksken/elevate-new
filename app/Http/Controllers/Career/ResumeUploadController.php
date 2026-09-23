<?php

namespace App\Http\Controllers\Career;

use App\Http\Controllers\Controller;
use App\Jobs\ParseResumeUpload;
use App\Models\Resume;
use App\Models\ResumeUpload;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ResumeUploadController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'resume_file'=>['required','file','mimes:pdf,doc,docx','max:10240'],
        ]);

        $upload = $this->saveUpload($request->file('resume_file'));

        ParseResumeUpload::dispatch($upload->id);

        return redirect()->route('career.resume.upload.review',$upload)
            ->with('success','Resume uploaded. Analysis has started.');
    }

    public function review(ResumeUpload $upload)
    {
        $this->authorise($upload);
        return view('career.resume.upload-review',compact('upload'));
    }

    public function status(ResumeUpload $upload)
    {
        $this->authorise($upload);

        return response()->json([
            'status'=>$upload->status,
            'processed_at'=>$upload->processed_at,
        ]);
    }

    public function import(ResumeUpload $upload)
    {
        $this->authorise($upload);
        abort_unless($upload->status === 'ready',422);

        $d = $upload->parsed_data ?? [];

        $resume = DB::transaction(function() use ($d) {
            $resume = Resume::create([
                'user_id'=>auth()->id(),
                'title'=>$d['title'] ?? 'Imported Resume',
                'template'=>'modern',
                'professional_summary'=>$d['professional_summary'] ?? null,
                'source'=>'uploaded',
            ]);

            foreach($d['experiences'] ?? [] as $i=>$x) {
                $resume->experiences()->create([
                    'job_title'=>$x['job_title'] ?? 'Experience',
                    'organisation'=>$x['organisation'] ?? 'Organisation',
                    'location'=>$x['location'] ?? null,
                    'description'=>$x['description'] ?? null,
                    'position'=>$i+1,
                ]);
            }

            foreach($d['education'] ?? [] as $i=>$x) {
                $resume->education()->create([
                    'institution'=>$x['institution'] ?? 'Institution',
                    'qualification'=>$x['qualification'] ?? 'Qualification',
                    'field_of_study'=>$x['field_of_study'] ?? null,
                    'description'=>$x['description'] ?? null,
                    'position'=>$i+1,
                ]);
            }

            foreach($d['skills'] ?? [] as $i=>$x) {
                $resume->skills()->create([
                    'skill'=>is_array($x) ? ($x['skill'] ?? 'Skill') : $x,
                    'level'=>is_array($x) ? ($x['level'] ?? null) : null,
                    'position'=>$i+1,
                ]);
            }

            return $resume;
        });

        $upload->update(['resume_id'=>$resume->id]);

        return redirect()->route('career.resume.edit',$resume)
            ->with('success','Extracted information imported. Please review every section.');
    }

    public function original(ResumeUpload $upload)
    {
        $this->authorise($upload);
        return Storage::disk('local')->download($upload->path,$upload->original_name);
    }

    public function replace(Request $request, ResumeUpload $upload)
    {
        $this->authorise($upload);

        $request->validate([
            'resume_file'=>['required','file','mimes:pdf,doc,docx','max:10240'],
        ]);

        if ($upload->path && Storage::disk('local')->exists($upload->path)) {
            Storage::disk('local')->delete($upload->path);
        }

        $file = $request->file('resume_file');
        $stored = Str::uuid().'.'.$file->getClientOriginalExtension();
        $path = $file->storeAs('private/resume-uploads/'.auth()->id(),$stored,'local');

        $upload->update([
            'original_name'=>$file->getClientOriginalName(),
            'stored_name'=>$stored,
            'mime_type'=>$file->getMimeType() ?: 'application/octet-stream',
            'file_size'=>$file->getSize(),
            'path'=>$path,
            'status'=>'uploaded',
            'extracted_text'=>null,
            'parsed_data'=>null,
            'parsing_error'=>null,
            'processed_at'=>null,
        ]);

        ParseResumeUpload::dispatch($upload->id);

        return redirect()->route('career.resume.upload.review',$upload)
            ->with('success','Resume file replaced. The new file is being analysed.');
    }

    public function retry(ResumeUpload $upload)
    {
        $this->authorise($upload);
        $upload->update(['status'=>'uploaded','parsing_error'=>null]);
        ParseResumeUpload::dispatch($upload->id);

        return back()->with('success','Resume analysis restarted.');
    }

    public function destroy(ResumeUpload $upload)
    {
        $this->authorise($upload);

        if ($upload->path && Storage::disk('local')->exists($upload->path)) {
            Storage::disk('local')->delete($upload->path);
        }

        $upload->delete();

        return back()->with('success','Uploaded resume deleted.');
    }

    private function saveUpload($file): ResumeUpload
    {
        $stored = Str::uuid().'.'.$file->getClientOriginalExtension();
        $path = $file->storeAs('private/resume-uploads/'.auth()->id(),$stored,'local');

        return ResumeUpload::create([
            'user_id'=>auth()->id(),
            'original_name'=>$file->getClientOriginalName(),
            'stored_name'=>$stored,
            'mime_type'=>$file->getMimeType() ?: 'application/octet-stream',
            'file_size'=>$file->getSize(),
            'path'=>$path,
            'status'=>'uploaded',
        ]);
    }

    private function authorise(ResumeUpload $upload): void
    {
        abort_unless($upload->user_id === auth()->id(),403);
    }
}
