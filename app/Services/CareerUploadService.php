<?php

namespace App\Services;

use App\Jobs\ParseCoverLetterUpload;
use App\Jobs\ParseResumeUpload;
use App\Models\CoverLetterUpload;
use App\Models\ResumeUpload;

class CareerUploadService
{
    public function dispatch(ResumeUpload|CoverLetterUpload $upload): void
    {
        if ($upload instanceof ResumeUpload) ParseResumeUpload::dispatch($upload->id);
        else ParseCoverLetterUpload::dispatch($upload->id);
    }

    public function present(ResumeUpload|CoverLetterUpload $upload, bool $details = false): array
    {
        $resume = $upload instanceof ResumeUpload;
        $result = $upload->only(['id', 'original_name', 'file_size', 'status', 'parsing_error', 'processed_at', 'created_at']);
        $result['document_id'] = $resume ? $upload->resume_id : $upload->cover_letter_id;
        $result['download_path'] = '/career/uploads/'.($resume ? 'resume' : 'cover-letter').'/'.$upload->id.'/original';
        if ($details) {
            $result['extracted_text'] = mb_substr($upload->extracted_text ?? '', 0, 50000);
            $result['text_truncated'] = mb_strlen($upload->extracted_text ?? '') > 50000;
            $result['draft'] = $this->draft($upload);
        }
        return $result;
    }

    private function draft(ResumeUpload|CoverLetterUpload $upload): array
    {
        $parsed = is_array($upload->parsed_data) ? $upload->parsed_data : [];
        $title = mb_substr(is_string($parsed['title'] ?? null) ? $parsed['title'] : pathinfo($upload->original_name, PATHINFO_FILENAME), 0, 190);
        if ($upload instanceof CoverLetterUpload) {
            $draft = ['title' => $title, 'body' => mb_substr(is_string($parsed['body'] ?? null) ? $parsed['body'] : ($upload->extracted_text ?? ''), 0, 50000)];
            foreach (['employer_name', 'job_title', 'recipient_name'] as $field) $draft[$field] = is_string($parsed[$field] ?? null) ? mb_substr($parsed[$field], 0, 190) : null;
            return $draft;
        }
        $draft = ['title' => $title, 'template' => 'modern', 'professional_summary' => mb_substr(is_string($parsed['professional_summary'] ?? null) ? $parsed['professional_summary'] : ($upload->extracted_text ?? ''), 0, 10000)];
        foreach (CareerDocumentEditorService::SECTIONS as $relation => $fields) {
            $rows = is_array($parsed[$relation] ?? null) ? $parsed[$relation] : [];
            if ($relation === 'skills') $rows = array_map(fn ($row) => is_string($row) ? ['skill' => $row] : $row, $rows);
            $draft[$relation] = array_values(array_map(fn ($row) => collect($row)->only($fields)->all(), array_filter($rows, 'is_array')));
        }
        return $draft;
    }
}
