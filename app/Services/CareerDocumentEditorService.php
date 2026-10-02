<?php

namespace App\Services;

use App\Models\CoverLetter;
use App\Models\Resume;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class CareerDocumentEditorService
{
    public const TEMPLATES = ['classic', 'modern', 'minimal', 'professional', 'graduate', 'technology', 'executive'];
    public const SECTIONS = [
        'experiences' => ['job_title', 'organisation', 'location', 'start_date', 'end_date', 'is_current', 'description'],
        'education' => ['institution', 'qualification', 'field_of_study', 'start_date', 'end_date', 'description'],
        'skills' => ['skill', 'level'],
    ];

    public function validate(array $data, bool $resume, int $userId): array
    {
        $rules = ['title' => ['required', 'string', 'max:190']];
        if (! $resume) {
            $rules += [
                'resume_id' => ['nullable', 'integer', Rule::exists('resumes', 'id')->where('user_id', $userId)],
                'employer_name' => ['nullable', 'string', 'max:190'], 'job_title' => ['nullable', 'string', 'max:190'],
                'recipient_name' => ['nullable', 'string', 'max:190'], 'body' => ['required', 'string', 'max:50000'],
            ];
        } else {
            $rules += [
                'template' => ['required', Rule::in(self::TEMPLATES)], 'professional_summary' => ['nullable', 'string', 'max:10000'],
                'experiences' => ['sometimes', 'array', 'max:50'], 'education' => ['sometimes', 'array', 'max:50'], 'skills' => ['sometimes', 'array', 'max:100'],
                'experiences.*.job_title' => ['required', 'string', 'max:190'], 'experiences.*.organisation' => ['required', 'string', 'max:190'],
                'experiences.*.location' => ['nullable', 'string', 'max:190'], 'experiences.*.start_date' => ['nullable', 'date'],
                'experiences.*.end_date' => ['nullable', 'date', 'after_or_equal:experiences.*.start_date'],
                'experiences.*.is_current' => ['nullable', 'boolean'], 'experiences.*.description' => ['nullable', 'string', 'max:10000'],
                'education.*.institution' => ['required', 'string', 'max:190'], 'education.*.qualification' => ['required', 'string', 'max:190'],
                'education.*.field_of_study' => ['nullable', 'string', 'max:190'], 'education.*.start_date' => ['nullable', 'date'],
                'education.*.end_date' => ['nullable', 'date', 'after_or_equal:education.*.start_date'], 'education.*.description' => ['nullable', 'string', 'max:10000'],
                'skills.*.skill' => ['required', 'string', 'max:100'], 'skills.*.level' => ['nullable', 'string', 'max:50'],
            ];
        }
        $validated = Validator::make($data, $rules)->validate();
        if ($resume) {
            foreach (self::SECTIONS as $relation => $fields) {
                if (array_key_exists($relation, $validated)) {
                    $validated[$relation] = array_map(fn ($row) => collect($row)->only($fields)->all(), $validated[$relation]);
                }
            }
        }
        return $validated;
    }

    public function save(User $user, bool $resume, array $data, Resume|CoverLetter|null $document = null, string $source = 'manual'): Resume|CoverLetter
    {
        if ($document) abort_unless($document->user_id === $user->id, 403);
        $data = $this->validate($data, $resume, $user->id);
        return DB::transaction(function () use ($user, $resume, $data, $document, $source) {
            $attributes = collect($data)->except(array_keys(self::SECTIONS))->all();
            if ($document) {
                $document->update($attributes);
            } else {
                $attributes += ['user_id' => $user->id, 'source' => $source];
                $document = $resume ? Resume::create($attributes) : CoverLetter::create($attributes);
            }
            if ($resume) {
                foreach (self::SECTIONS as $relation => $fields) {
                    if (! array_key_exists($relation, $data)) continue;
                    $document->{$relation}()->delete();
                    foreach ($data[$relation] as $position => $row) {
                        $document->{$relation}()->create($row + ['position' => $position]);
                    }
                }
                $document->load(array_keys(self::SECTIONS));
            }
            return $document;
        });
    }
}
