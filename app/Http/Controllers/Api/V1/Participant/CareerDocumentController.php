<?php

namespace App\Http\Controllers\Api\V1\Participant;

use App\Http\Controllers\Controller;
use App\Models\CoverLetter;
use App\Models\CoverLetterUpload;
use App\Models\Resume;
use App\Models\ResumeUpload;
use App\Services\CareerDocumentEditorService;
use App\Services\CareerDocumentService;
use App\Services\CareerUploadService;
use Illuminate\Http\Request;

class CareerDocumentController extends Controller
{
    public const TEMPLATES = CareerDocumentEditorService::TEMPLATES;

    public function index(Request $request, CareerUploadService $uploads)
    {
        return response()->json([
            'resumes' => Resume::where('user_id', $request->user()->id)->with(['experiences', 'education', 'skills', 'projects', 'referees', 'portfolioFiles'])->latest()->get(),
            'cover_letters' => CoverLetter::where('user_id', $request->user()->id)->latest()->get(),
            'resume_uploads' => ResumeUpload::where('user_id', $request->user()->id)->latest()->get()->map(fn ($upload) => $uploads->present($upload)),
            'cover_letter_uploads' => CoverLetterUpload::where('user_id', $request->user()->id)->latest()->get()->map(fn ($upload) => $uploads->present($upload)),
            'templates' => self::TEMPLATES,
        ]);
    }

    public function storeResume(Request $request, CareerDocumentEditorService $editor)
    {
        return response()->json(['resume' => $editor->save($request->user(), true, $request->all())], 201);
    }

    public function updateResume(Request $request, Resume $resume, CareerDocumentEditorService $editor)
    {
        $this->owner($request, $resume);
        return response()->json(['resume' => $editor->save($request->user(), true, $request->all(), $resume)]);
    }

    public function destroyResume(Request $request, Resume $resume)
    {
        $this->owner($request, $resume);
        app(CareerDocumentService::class)->deleteResume($resume);
        return response()->noContent();
    }

    public function downloadResume(Request $request, Resume $resume, CareerDocumentService $service)
    {
        $this->owner($request, $resume);
        return $service->download($resume);
    }

    public function storeLetter(Request $request, CareerDocumentEditorService $editor)
    {
        return response()->json(['cover_letter' => $editor->save($request->user(), false, $request->all())], 201);
    }

    public function updateLetter(Request $request, CoverLetter $letter, CareerDocumentEditorService $editor)
    {
        $this->owner($request, $letter);
        return response()->json(['cover_letter' => $editor->save($request->user(), false, $request->all(), $letter)]);
    }

    public function destroyLetter(Request $request, CoverLetter $letter)
    {
        $this->owner($request, $letter);
        $letter->delete();
        return response()->noContent();
    }

    public function downloadLetter(Request $request, CoverLetter $letter, CareerDocumentService $service)
    {
        $this->owner($request, $letter);
        return $service->download($letter);
    }

    private function owner(Request $request, Resume|CoverLetter $document): void
    {
        abort_unless($document->user_id === $request->user()->id, 403);
    }
}
