<?php

namespace App\Http\Controllers\Career;

use App\Http\Controllers\Controller;
use App\Models\Resume;
use App\Models\ResumePortfolioFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ResumePortfolioController extends Controller
{
    public function store(Request $request, Resume $resume)
    {
        abort_unless($resume->user_id === $request->user()->id, 403);
        $data = $request->validate(['label' => ['nullable', 'string', 'max:190'], 'file' => ['required', 'file', 'mimes:pdf,doc,docx,jpg,jpeg,png,webp,zip,txt', 'extensions:pdf,doc,docx,jpg,jpeg,png,webp,zip,txt', 'max:10240']]);
        $file = $request->file('file');
        $path = $file->store('private/resume-portfolio/'.$resume->id, 'local');
        try {
            $attachment = $resume->portfolioFiles()->create(['label' => ($data['label'] ?? null) ?: mb_substr(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME), 0, 190), 'original_name' => $file->getClientOriginalName(), 'path' => $path, 'file_size' => $file->getSize()]);
        } catch (\Throwable $exception) { Storage::disk('local')->delete($path); throw $exception; }
        return $request->expectsJson() ? response()->json(['file' => $attachment], 201) : back()->with('success', 'Portfolio file added.');
    }

    public function download(Request $request, Resume $resume, ResumePortfolioFile $file)
    {
        $this->own($request, $resume, $file);
        return $this->response($file);
    }

    public function shared(ResumePortfolioFile $file) { return $this->response($file); }

    public function destroy(Request $request, Resume $resume, ResumePortfolioFile $file)
    {
        $this->own($request, $resume, $file);
        Storage::disk('local')->delete($file->path);
        $file->delete();
        return $request->expectsJson() ? response()->noContent() : back()->with('success', 'Portfolio file removed.');
    }

    private function own(Request $request, Resume $resume, ResumePortfolioFile $file): void
    {
        abort_unless($resume->user_id === $request->user()->id, 403);
        abort_unless($file->resume_id === $resume->id, 404);
    }

    private function response(ResumePortfolioFile $file)
    {
        abort_unless(Storage::disk('local')->exists($file->path), 404);
        return Storage::disk('local')->download($file->path, $file->original_name, ['X-Content-Type-Options' => 'nosniff', 'Cache-Control' => 'private, no-store']);
    }
}
