<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Multiple files per lesson, assignment and submission.
 *
 * - learning_files (already one-to-many for lessons) gains assessment_id so
 *   assignment brief/material files live in the same table.
 * - assessment_attempt_files holds the files a participant submits.
 * - Existing single-file values (lessons.file_path, assessments.attachment_path,
 *   assessment_attempts.submission_file_path) are copied into the new rows.
 *   The old columns are kept (readable) for backwards compatibility.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('learning_files', function (Blueprint $table) {
            if (! Schema::hasColumn('learning_files', 'assessment_id')) {
                $table->foreignId('assessment_id')->nullable()->after('lesson_id')
                    ->constrained('assessments')->cascadeOnDelete();
            }

            if (! Schema::hasColumn('learning_files', 'migrated_from')) {
                $table->string('migrated_from', 40)->nullable()->after('uploaded_by');
            }
        });

        if (! Schema::hasTable('assessment_attempt_files')) {
            Schema::create('assessment_attempt_files', function (Blueprint $table) {
                $table->id();
                $table->foreignId('assessment_attempt_id')->constrained('assessment_attempts')->cascadeOnDelete();
                $table->string('original_name');
                $table->string('disk')->default('local');
                $table->string('path');
                $table->string('mime_type')->nullable();
                $table->unsignedBigInteger('size_bytes')->default(0);
                $table->string('migrated_from', 40)->nullable();
                $table->timestamps();
            });
        }

        $this->migrateLessonFiles();
        $this->migrateAssessmentAttachments();
        $this->migrateSubmissionFiles();
    }

    public function down(): void
    {
        Schema::dropIfExists('assessment_attempt_files');

        if (Schema::hasColumn('learning_files', 'migrated_from')) {
            DB::table('learning_files')->whereNotNull('migrated_from')->delete();
        }

        // Rows attached only to an assessment cannot survive without the column.
        if (Schema::hasColumn('learning_files', 'assessment_id')) {
            DB::table('learning_files')->whereNotNull('assessment_id')->whereNull('lesson_id')->delete();

            Schema::table('learning_files', function (Blueprint $table) {
                $table->dropConstrainedForeignId('assessment_id');
            });
        }

        if (Schema::hasColumn('learning_files', 'migrated_from')) {
            Schema::table('learning_files', function (Blueprint $table) {
                $table->dropColumn('migrated_from');
            });
        }
    }

    private function migrateLessonFiles(): void
    {
        DB::table('lessons')
            ->join('course_modules', 'course_modules.id', '=', 'lessons.course_module_id')
            ->whereNotNull('lessons.file_path')
            ->where('lessons.file_path', '!=', '')
            ->select('lessons.id', 'lessons.title', 'lessons.file_path', 'course_modules.course_id')
            ->orderBy('lessons.id')
            ->chunkById(200, function ($lessons) {
                foreach ($lessons as $lesson) {
                    $path = $this->normalise((string) $lesson->file_path);

                    if ($path === null || DB::table('learning_files')->where('lesson_id', $lesson->id)->where('path', $path)->exists()) {
                        continue;
                    }

                    $extension = pathinfo($path, PATHINFO_EXTENSION);
                    $base = Str::slug((string) $lesson->title) ?: 'lesson-'.$lesson->id;

                    DB::table('learning_files')->insert($this->fileRow($path, $extension ? "{$base}.{$extension}" : $base) + [
                        'course_id' => $lesson->course_id,
                        'lesson_id' => $lesson->id,
                        'stored_name' => basename($path),
                        'migrated_from' => 'lessons.file_path',
                    ]);
                }
            }, 'lessons.id', 'id');
    }

    private function migrateAssessmentAttachments(): void
    {
        if (! Schema::hasColumn('assessments', 'attachment_path')) {
            return;
        }

        DB::table('assessments')
            ->whereNotNull('attachment_path')
            ->where('attachment_path', '!=', '')
            ->select('id', 'course_id', 'attachment_path')
            ->orderBy('id')
            ->chunkById(200, function ($assessments) {
                foreach ($assessments as $assessment) {
                    $path = $this->normalise((string) $assessment->attachment_path);

                    if ($path === null || DB::table('learning_files')->where('assessment_id', $assessment->id)->where('path', $path)->exists()) {
                        continue;
                    }

                    DB::table('learning_files')->insert($this->fileRow($path, basename($path)) + [
                        'course_id' => $assessment->course_id,
                        'assessment_id' => $assessment->id,
                        'stored_name' => basename($path),
                        'migrated_from' => 'assessments.attachment_path',
                    ]);
                }
            });
    }

    private function migrateSubmissionFiles(): void
    {
        if (! Schema::hasColumn('assessment_attempts', 'submission_file_path')) {
            return;
        }

        DB::table('assessment_attempts')
            ->whereNotNull('submission_file_path')
            ->where('submission_file_path', '!=', '')
            ->select('id', 'submission_file_path')
            ->orderBy('id')
            ->chunkById(200, function ($attempts) {
                foreach ($attempts as $attempt) {
                    $path = $this->normalise((string) $attempt->submission_file_path);

                    if ($path === null || DB::table('assessment_attempt_files')->where('assessment_attempt_id', $attempt->id)->where('path', $path)->exists()) {
                        continue;
                    }

                    DB::table('assessment_attempt_files')->insert($this->fileRow($path, basename($path), 'local') + [
                        'assessment_attempt_id' => $attempt->id,
                        'migrated_from' => 'assessment_attempts.submission_file_path',
                    ]);
                }
            });
    }

    private function normalise(string $path): ?string
    {
        $path = trim($path);

        if ($path === '' || preg_match('#^https?://#i', $path)) {
            return null;
        }

        return ltrim((string) preg_replace('#^/?storage/#', '', $path), '/');
    }

    /** Common columns; detects which disk holds the file (legacy uploads used "public"). */
    private function fileRow(string $path, string $name, string $preferredDisk = 'public'): array
    {
        $disks = array_unique([$preferredDisk, 'public', 'local']);
        $disk = $preferredDisk;
        $exists = false;

        foreach ($disks as $candidate) {
            try {
                if (Storage::disk($candidate)->exists($path)) {
                    $disk = $candidate;
                    $exists = true;
                    break;
                }
            } catch (\Throwable) {
                // Disk misconfigured on this host; keep the preferred disk.
            }
        }

        $mime = null;
        $size = 0;

        if ($exists) {
            try {
                $mime = Storage::disk($disk)->mimeType($path) ?: null;
                $size = (int) Storage::disk($disk)->size($path);
            } catch (\Throwable) {
                // Metadata is optional.
            }
        }

        return [
            'original_name' => Str::limit($name, 250, ''),
            'disk' => $disk,
            'path' => $path,
            'mime_type' => $mime,
            'size_bytes' => $size,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
};
