{{-- What participants must hand in for an assignment. Expects optional $assessment. --}}
@php
    $assessment = $assessment ?? null;
    // Old input is only reused on the create form; each edit form shows its own saved values.
    $chosenFormats = $assessment ? $assessment->allowedSubmissionFormats() : old('submission_formats', ['document']);
    $maxFiles = $assessment ? $assessment->maxSubmissionFiles() : old('max_submission_files', 1);
@endphp
<fieldset class="full icm-submission-settings">
    <legend>Participant submissions</legend>
    <p class="icm-muted">Participants must attach at least one file. Choose the formats they may upload and how many files one submission may contain.</p>
    <div class="icm-formats" role="group" aria-label="Accepted file formats">
        @foreach(\App\Models\Assessment::SUBMISSION_FORMATS as $key => $format)
            <label class="icm-format">
                <input type="checkbox" name="submission_formats[]" value="{{ $key }}" @checked(in_array($key, (array) $chosenFormats, true))>
                <span><strong>{{ $format['label'] }}</strong><small>{{ $format['hint'] }}</small></span>
            </label>
        @endforeach
    </div>
    <div class="icm-maxfiles">
        <label>Maximum files per submission</label>
        <input type="number" name="max_submission_files" min="1" max="{{ \App\Models\Assessment::MAX_SUBMISSION_FILES_LIMIT }}" value="{{ $maxFiles }}" required>
        <small class="icm-muted">1 to {{ \App\Models\Assessment::MAX_SUBMISSION_FILES_LIMIT }} files, up to {{ (int) round(((int) config('elearning.max_file_kb', 51200)) / 1024) }} MB each.</small>
    </div>
</fieldset>
