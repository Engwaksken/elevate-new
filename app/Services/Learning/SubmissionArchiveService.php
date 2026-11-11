<?php

namespace App\Services\Learning;

use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\AssessmentAttemptFile;
use App\Models\Course;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use ZipArchive;

/**
 * Builds a ZIP of participants' submissions for instructors.
 *
 * One assessment:
 *   <Participant name (code)>/<files of the latest attempt>
 *   <Participant name (code)>/response.txt              (typed response, if any)
 *   <Participant name (code)>/Earlier attempts/Attempt 1/...
 *
 * Whole course: the same layout inside one folder per assessment.
 */
class SubmissionArchiveService
{
    public function __construct(private readonly LearningFileService $files)
    {
    }

    /**
     * @return array{path: string, name: string, participants: int, files: int}
     */
    public function build(Course $course, ?Assessment $assessment = null): array
    {
        if (! class_exists(ZipArchive::class)) {
            throw new RuntimeException('ZIP downloads need the PHP zip extension, which is not enabled on this server.');
        }

        $attempts = AssessmentAttempt::query()
            ->with(['user:id,name,participant_code', 'assessment:id,title,type,course_id'])
            ->whereNotNull('submitted_at')
            ->when(
                $assessment,
                fn ($q) => $q->where('assessment_id', $assessment->id),
                fn ($q) => $q->whereHas('assessment', fn ($a) => $a->where('course_id', $course->id))
            )
            ->orderBy('assessment_id')->orderBy('user_id')->orderBy('attempt_number')->orderBy('id')
            ->get();

        $this->files->syncAttempts($attempts);
        $attempts->load('files');

        $path = tempnam(sys_get_temp_dir(), 'subs');
        $zip = new ZipArchive();

        if ($zip->open($path, ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Could not create the ZIP file.');
        }

        $fileCount = 0;
        $participants = [];
        $used = [];

        foreach ($attempts->groupBy('assessment_id') as $forAssessment) {
            $assessmentFolder = $assessment ? '' : $this->unique($used, '', $this->clean($forAssessment->first()->assessment?->title ?: 'Assessment')).'/';

            foreach ($forAssessment->groupBy('user_id') as $userId => $userAttempts) {
                $participants[$userId] = true;
                $user = $userAttempts->first()->user;
                $label = trim(($user?->name ?: 'Participant '.$userId).($user?->participant_code ? ' ('.$user->participant_code.')' : ''));
                $studentFolder = $assessmentFolder.$this->unique($used, $assessmentFolder, $this->clean($label)).'/';

                $latest = $userAttempts->last();

                foreach ($userAttempts as $attempt) {
                    $folder = $attempt->is($latest)
                        ? $studentFolder
                        : $studentFolder.'Earlier attempts/Attempt '.($attempt->attempt_number ?: $attempt->id).'/';

                    $fileCount += $this->addAttempt($zip, $used, $folder, $attempt);
                }
            }
        }

        if ($attempts->isEmpty()) {
            $zip->addFromString('No submissions.txt', 'There are no submissions to download yet.');
        }

        $zip->close();

        $name = $this->clean($course->title.' - '.($assessment ? $assessment->title : 'all').' submissions').'.zip';

        return ['path' => $path, 'name' => $name, 'participants' => count($participants), 'files' => $fileCount];
    }

    private function addAttempt(ZipArchive $zip, array &$used, string $folder, AssessmentAttempt $attempt): int
    {
        $added = 0;

        $text = trim((string) $attempt->submission_text);
        if ($text !== '') {
            $zip->addFromString($folder.$this->unique($used, $folder, 'response.txt'), $text."\n");
        }

        /** @var AssessmentAttemptFile $file */
        foreach ($attempt->files as $file) {
            $disk = Storage::disk($file->disk ?: 'local');

            if (! $file->path || ! $disk->exists($file->path)) {
                continue;
            }

            $entry = $folder.$this->unique($used, $folder, $this->clean($file->original_name ?: basename($file->path)));
            $local = $this->localPath($disk, $file->path);

            // Local files are streamed from disk when the ZIP is written; others are read into memory.
            $local ? $zip->addFile($local, $entry) : $zip->addFromString($entry, (string) $disk->get($file->path));
            $added++;
        }

        return $added;
    }

    private function localPath($disk, string $path): ?string
    {
        try {
            $full = $disk->path($path);
        } catch (\Throwable) {
            return null;
        }

        return is_file($full) ? $full : null;
    }

    /** A name that is unique inside $folder: "essay.pdf", "essay (2).pdf", ... */
    private function unique(array &$used, string $folder, string $name): string
    {
        $base = pathinfo($name, PATHINFO_FILENAME);
        $extension = pathinfo($name, PATHINFO_EXTENSION);
        $candidate = $name;

        for ($i = 2; isset($used[strtolower($folder.$candidate)]); $i++) {
            $candidate = $base.' ('.$i.')'.($extension !== '' ? '.'.$extension : '');
        }

        $used[strtolower($folder.$candidate)] = true;

        return $candidate;
    }

    /** Safe file/folder name for every operating system. */
    private function clean(string $name): string
    {
        $name = preg_replace('/[\\\\\/:*?"<>|\x00-\x1F]+/u', '-', $name);
        $name = trim(preg_replace('/\s+/u', ' ', (string) $name), " .-");

        return mb_substr($name !== '' ? $name : 'file', 0, 120);
    }

    /** @return Collection<int,Assessment> Assessments in the course that have submissions. */
    public function assessmentsWithSubmissions(Course $course): Collection
    {
        return $course->assessments()->whereHas('attempts')->withCount('attempts')->orderBy('title')->get();
    }
}
