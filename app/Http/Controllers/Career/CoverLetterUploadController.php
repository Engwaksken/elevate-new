<?php

namespace App\Http\Controllers\Career;

use App\Http\Controllers\Controller;
use App\Jobs\ParseCoverLetterUpload;
use App\Models\CoverLetter;
use App\Models\CoverLetterUpload;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CoverLetterUploadController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'cover_letter_file'=>['required','file','mimes:pdf,doc,docx','max:10240'],
        ]);

        $upload = $this->saveUpload($request->file('cover_letter_file'));
        ParseCoverLetterUpload::dispatch($upload->id);

        return redirect()->route('career.cover-letter.upload.review',$upload)
            ->with('success','Cover letter uploaded. Analysis has started.');
    }

    public function review(CoverLetterUpload $upload)
    {
        $this->authorise($upload);
        return view('career.resume.cover-letter-upload-review',compact('upload'));
    }

    public function status(CoverLetterUpload $upload)
    {
        $this->authorise($upload);
        return response()->json([
            'status'=>$upload->status,
            'processed_at'=>$upload->processed_at,
        ]);
    }

    public function import(CoverLetterUpload $upload)
    {
        $this->authorise($upload);
        abort_unless($upload->status === 'ready',422);

        $d = $upload->parsed_data ?? [];

        $letter = CoverLetter::create([
            'user_id'=>auth()->id(),
            'title'=>$d['title'] ?? pathinfo($upload->original_name,PATHINFO_FILENAME),
            'employer_name'=>$d['employer_name'] ?? null,
            'job_title'=>$d['job_title'] ?? null,
            'recipient_name'=>$d['recipient_name'] ?? null,
            'body'=>$d['body'] ?? $upload->extracted_text,
            'source'=>'uploaded',
            'ai_generated'=>false,
        ]);

        $upload->update(['cover_letter_id'=>$letter->id]);

        return redirect()->route('career.resume.index')
            ->with('success','Cover letter imported. Review it before use.');
    }

    public function original(CoverLetterUpload $upload)
    {
        $this->authorise($upload);
        return Storage::disk('local')->download($upload->path,$upload->original_name);
    }

    public function replace(Request $request, CoverLetterUpload $upload)
    {
        $this->authorise($upload);

        $request->validate([
            'cover_letter_file'=>['required','file','mimes:pdf,doc,docx','max:10240'],
        ]);

        if ($upload->path && Storage::disk('local')->exists($upload->path)) {
            Storage::disk('local')->delete($upload->path);
        }

        $file = $request->file('cover_letter_file');
        $stored = Str::uuid().'.'.$file->getClientOriginalExtension();
        $path = $file->storeAs('private/cover-letter-uploads/'.auth()->id(),$stored,'local');

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

        ParseCoverLetterUpload::dispatch($upload->id);

        return redirect()->route('career.cover-letter.upload.review',$upload)
            ->with('success','Cover letter file replaced. Analysis restarted.');
    }

    public function retry(CoverLetterUpload $upload)
    {
        $this->authorise($upload);
        $upload->update(['status'=>'uploaded','parsing_error'=>null]);
        ParseCoverLetterUpload::dispatch($upload->id);

        return back()->with('success','Cover letter analysis restarted.');
    }

    public function destroy(CoverLetterUpload $upload)
    {
        $this->authorise($upload);

        if ($upload->path && Storage::disk('local')->exists($upload->path)) {
            Storage::disk('local')->delete($upload->path);
        }

        $upload->delete();

        return back()->with('success','Uploaded cover letter deleted.');
    }

    private function saveUpload($file): CoverLetterUpload
    {
        $stored = Str::uuid().'.'.$file->getClientOriginalExtension();
        $path = $file->storeAs('private/cover-letter-uploads/'.auth()->id(),$stored,'local');

        return CoverLetterUpload::create([
            'user_id'=>auth()->id(),
            'original_name'=>$file->getClientOriginalName(),
            'stored_name'=>$stored,
            'mime_type'=>$file->getMimeType() ?: 'application/octet-stream',
            'file_size'=>$file->getSize(),
            'path'=>$path,
            'status'=>'uploaded',
        ]);
    }

    private function authorise(CoverLetterUpload $upload): void
    {
        abort_unless($upload->user_id === auth()->id(),403);
    }
}
