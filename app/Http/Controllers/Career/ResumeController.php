<?php

namespace App\Http\Controllers\Career;

use App\Http\Controllers\Controller;
use App\Models\CoverLetter;
use App\Models\CoverLetterUpload;
use App\Models\Resume;
use App\Models\ResumeUpload;
use App\Services\ResumePdfService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ResumeController extends Controller
{
    public function index()
    {
        return view('career.resume.index', [
            'resumes' => Resume::where('user_id',auth()->id())->latest()->get(),
            'coverLetters' => CoverLetter::where('user_id',auth()->id())->latest()->get(),
            'resumeUploads' => ResumeUpload::where('user_id',auth()->id())->latest()->get(),
            'coverLetterUploads' => CoverLetterUpload::where('user_id',auth()->id())->latest()->get(),
        ]);
    }

    public function create()
    {
        return view('career.resume.form', ['resume'=>new Resume()]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title'=>['required','string','max:190'],
            'template'=>['required',Rule::in($this->templates())],
            'professional_summary'=>['nullable','string'],
        ]);

        $resume = Resume::create($data + [
            'user_id'=>auth()->id(),
            'source'=>'manual',
        ]);

        return redirect()->route('career.resume.edit',$resume)->with('success','Resume created.');
    }

    public function edit(Resume $resume)
    {
        $this->authorise($resume);
        $resume->load(['experiences','education','skills','certifications','languages','projects']);
        return view('career.resume.form', compact('resume'));
    }

    public function update(Request $request, Resume $resume)
    {
        $this->authorise($resume);

        $resume->update($request->validate([
            'title'=>['required','string','max:190'],
            'template'=>['required',Rule::in($this->templates())],
            'professional_summary'=>['nullable','string'],
        ]));

        return back()->with('success','Resume updated.');
    }

    public function updateTemplate(Request $request, Resume $resume)
    {
        $this->authorise($resume);

        $resume->update($request->validate([
            'template'=>['required',Rule::in($this->templates())],
        ]));

        return back()->with('success','Resume template updated.');
    }

    public function makeDefault(Resume $resume)
    {
        $this->authorise($resume);

        DB::transaction(function() use ($resume) {
            Resume::where('user_id',auth()->id())->update(['is_default'=>false]);
            $resume->update(['is_default'=>true]);
        });

        return back()->with('success','Default resume updated.');
    }

    public function destroy(Resume $resume)
    {
        $this->authorise($resume);
        $resume->delete();
        return redirect()->route('career.resume.index')->with('success','Resume deleted.');
    }

    public function download(Resume $resume, ResumePdfService $service)
    {
        $this->authorise($resume);
        $path = $service->render($resume);
        return Storage::disk('local')->download($path,$this->safeFilename($resume->title).'.pdf');
    }

    private function authorise(Resume $resume): void
    {
        abort_unless($resume->user_id === auth()->id(),403);
    }

    private function templates(): array
    {
        return ['classic','modern','minimal','professional','graduate','technology','executive'];
    }

    private function safeFilename(string $value): string
    {
        return trim(preg_replace('/[^A-Za-z0-9_-]+/','_', $value),'_') ?: 'Resume';
    }
}
