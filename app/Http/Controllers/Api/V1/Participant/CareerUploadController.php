<?php

namespace App\Http\Controllers\Api\V1\Participant;

use App\Http\Controllers\Controller;
use App\Models\CoverLetterUpload;
use App\Models\ResumeUpload;
use App\Services\CareerDocumentEditorService;
use App\Services\CareerUploadService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class CareerUploadController extends Controller
{
    public function store(Request $request, string $type, CareerUploadService $service)
    {
        $request->validate(['file' => ['required', 'file', 'mimes:pdf,doc,docx', 'max:10240']]);
        $file = $request->file('file');
        $path = $file->store('private/career-uploads/'.$request->user()->id, 'local');
        $class = $this->model($type);
        try {
            $upload = $class::create([
                'user_id' => $request->user()->id, 'original_name' => $file->getClientOriginalName(),
                'stored_name' => basename($path), 'mime_type' => $file->getMimeType(), 'file_size' => $file->getSize(), 'path' => $path, 'status' => 'uploaded',
            ]);
        } catch (\Throwable $exception) {
            Storage::disk('local')->delete($path);
            throw $exception;
        }
        $service->dispatch($upload);
        return response()->json(['upload' => $service->present($upload->fresh(), true)], 201);
    }

    public function show(Request $request, string $type, int $id, CareerUploadService $service)
    {
        return response()->json(['upload' => $service->present($this->own($request, $type, $id), true)]);
    }

    public function import(Request $request, string $type, int $id, CareerDocumentEditorService $editor)
    {
        $upload = $this->own($request, $type, $id);
        $request->validate(['data' => ['required', 'array']]);
        $document = DB::transaction(function () use ($request, $upload, $editor, $type) {
            $upload = $upload->newQuery()->whereKey($upload->id)->lockForUpdate()->firstOrFail();
            abort_unless($upload->user_id === $request->user()->id, 403);
            abort_unless($upload->status === 'ready', 422, 'Wait until document analysis is ready before importing.');
            $relation = $type === 'resume' ? 'resume' : 'coverLetter';
            // Retries return the existing document rather than creating duplicates.
            if ($upload->{$relation}) return $upload->{$relation};
            $document = $editor->save($request->user(), $type === 'resume', $request->input('data'), source: 'uploaded');
            $upload->update([$type === 'resume' ? 'resume_id' : 'cover_letter_id' => $document->id]);
            return $document;
        });
        if ($type === 'resume') $document->load(['experiences', 'education', 'skills']);
        return response()->json(['document' => $document], 201);
    }

    public function retry(Request $request, string $type, int $id, CareerUploadService $service)
    {
        $upload = $this->own($request, $type, $id);
        abort_unless($upload->status === 'failed', 422, 'Only failed document analysis can be retried.');
        $upload->update(['status' => 'uploaded', 'parsing_error' => null]);
        $service->dispatch($upload);
        return response()->json(['upload' => $service->present($upload->fresh(), true)]);
    }

    public function original(Request $request, string $type, int $id)
    {
        $upload = $this->own($request, $type, $id);
        abort_unless(Storage::disk('local')->exists($upload->path), 404);
        return Storage::disk('local')->download($upload->path, $upload->original_name);
    }

    public function destroy(Request $request, string $type, int $id)
    {
        $upload = $this->own($request, $type, $id);
        abort_if(in_array($upload->status, ['uploaded', 'processing'], true), 422, 'Wait until analysis finishes before deleting this file.');
        Storage::disk('local')->delete($upload->path);
        $upload->delete();
        return response()->noContent();
    }

    private function own(Request $request, string $type, int $id): ResumeUpload|CoverLetterUpload
    {
        $class = $this->model($type);
        $upload = $class::findOrFail($id);
        abort_unless($upload->user_id === $request->user()->id, 403);
        return $upload;
    }

    private function model(string $type): string
    {
        abort_unless(in_array($type, ['resume', 'cover-letter'], true), 404);
        return $type === 'resume' ? ResumeUpload::class : CoverLetterUpload::class;
    }
}
