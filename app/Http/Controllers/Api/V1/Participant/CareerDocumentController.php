<?php

namespace App\Http\Controllers\Api\V1\Participant;

use App\Http\Controllers\Controller;
use App\Models\CoverLetter;
use App\Models\Resume;
use App\Services\CareerDocumentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class CareerDocumentController extends Controller
{
    public const TEMPLATES = ['classic', 'modern', 'minimal', 'professional', 'graduate', 'technology', 'executive'];

    public function index(Request $request)
    {
        return response()->json([
            'resumes' => Resume::where('user_id', $request->user()->id)->with(['experiences', 'education', 'skills'])->latest()->get(),
            'cover_letters' => CoverLetter::where('user_id', $request->user()->id)->latest()->get(),
            'templates' => self::TEMPLATES,
        ]);
    }

    public function storeResume(Request $request)
    {
        $data = $this->resumeData($request);
        $resume = DB::transaction(function () use ($request, $data) {
            $resume = Resume::create(collect($data)->except(['experiences', 'education', 'skills'])->all() + [
                'user_id' => $request->user()->id, 'source' => 'manual',
            ]);
            $this->sections($resume, $data);

            return $resume;
        });

        return response()->json(['resume' => $resume->load(['experiences', 'education', 'skills'])], 201);
    }

    public function updateResume(Request $request, Resume $resume)
    {
        $this->owner($request, $resume);
        $data = $this->resumeData($request);
        DB::transaction(function () use ($resume, $data) {
            $resume->update(collect($data)->except(['experiences', 'education', 'skills'])->all());
            $this->sections($resume, $data);
        });

        return response()->json(['resume' => $resume->load(['experiences', 'education', 'skills'])]);
    }

    public function destroyResume(Request $request, Resume $resume)
    {
        $this->owner($request, $resume);
        $resume->delete();

        return response()->noContent();
    }

    public function downloadResume(Request $request, Resume $resume, CareerDocumentService $service)
    {
        $this->owner($request, $resume);

        return $service->download($resume);
    }

    public function storeLetter(Request $request)
    {
        $letter = CoverLetter::create($this->letterData($request) + ['user_id' => $request->user()->id, 'source' => 'manual']);

        return response()->json(['cover_letter' => $letter], 201);
    }

    public function updateLetter(Request $request, CoverLetter $letter)
    {
        $this->owner($request, $letter);
        $letter->update($this->letterData($request));

        return response()->json(['cover_letter' => $letter]);
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

    private function letterData(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:190'],
            'resume_id' => ['nullable', 'integer', Rule::exists('resumes', 'id')->where('user_id', $request->user()->id)],
            'employer_name' => ['nullable', 'string', 'max:190'],
            'job_title' => ['nullable', 'string', 'max:190'],
            'recipient_name' => ['nullable', 'string', 'max:190'],
            'body' => ['required', 'string', 'max:50000'],
        ]);
    }

    private function resumeData(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:190'],
            'template' => ['required', Rule::in(self::TEMPLATES)],
            'professional_summary' => ['nullable', 'string', 'max:10000'],
            'experiences' => ['sometimes', 'array', 'max:50'],
            'experiences.*.job_title' => ['required', 'string', 'max:190'],
            'experiences.*.organisation' => ['required', 'string', 'max:190'],
            'experiences.*.location' => ['nullable', 'string', 'max:190'],
            'experiences.*.start_date' => ['nullable', 'date'],
            'experiences.*.end_date' => ['nullable', 'date', 'after_or_equal:experiences.*.start_date'],
            'experiences.*.is_current' => ['nullable', 'boolean'],
            'experiences.*.description' => ['nullable', 'string', 'max:10000'],
            'education' => ['sometimes', 'array', 'max:50'],
            'education.*.institution' => ['required', 'string', 'max:190'],
            'education.*.qualification' => ['required', 'string', 'max:190'],
            'education.*.field_of_study' => ['nullable', 'string', 'max:190'],
            'education.*.start_date' => ['nullable', 'date'],
            'education.*.end_date' => ['nullable', 'date', 'after_or_equal:education.*.start_date'],
            'education.*.description' => ['nullable', 'string', 'max:10000'],
            'skills' => ['sometimes', 'array', 'max:100'],
            'skills.*.skill' => ['required', 'string', 'max:100'],
            'skills.*.level' => ['nullable', 'string', 'max:50'],
        ]);
    }

    private function sections(Resume $resume, array $data): void
    {
        $fields = [
            'experiences' => ['job_title', 'organisation', 'location', 'start_date', 'end_date', 'is_current', 'description'],
            'education' => ['institution', 'qualification', 'field_of_study', 'start_date', 'end_date', 'description'],
            'skills' => ['skill', 'level'],
        ];
        foreach ($fields as $relation => $allowed) {
            if (! array_key_exists($relation, $data)) continue;
            $resume->{$relation}()->delete();
            foreach ($data[$relation] as $position => $row) {
                $resume->{$relation}()->create(collect($row)->only($allowed)->all() + ['position' => $position]);
            }
        }
    }
}
