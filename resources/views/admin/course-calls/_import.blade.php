@if(auth()->user()->isSuperAdmin() && auth()->user()->hasPermission('course_calls.manage'))
<section class="admin-panel">
    <div class="admin-panel-head">
        <div>
            <h2>Import application responses</h2>
            <p>Download this call’s template and copy responses from Microsoft Forms, Google Forms, KoboToolbox, or another survey export into the matching columns. Save as CSV UTF-8 before uploading. Maximum 1,000 applicants (5 MB) per upload.</p>
        </div>
        <a href="{{ route('admin.course-calls.import-template', $courseCall) }}" class="btn btn-outline"><i class="fas fa-download"></i> Download application template</a>
    </div>
    <p>Name and email are required. Phone is optional. Submitted date is optional (YYYY-MM-DD HH:MM:SS); leave blank to use the import time. Existing accounts are matched by email without changing their details. Existing applications are skipped. New accounts can set a password using the password-reset page and must verify their email.</p>
    @if($courseCall->questions->isNotEmpty())
        <ul>
            @foreach($courseCall->questions as $question)
                <li><code>question_{{ $question->id }}</code>: {{ $question->question_text }}{{ $question->is_required ? ' (required)' : '' }}
                    @if($question->options) Options: {{ implode(' | ', $question->options) }}.@endif
                    @if($question->question_type === 'multiple_choice') Separate selected options with |.@endif
                    @if($question->question_type === 'yes_no') Use Yes or No.@endif
                    @if($question->question_type === 'date') Use YYYY-MM-DD.@endif
                </li>
            @endforeach
        </ul>
    @endif
    <p>Imports are submitted for review only. Approval, entry assessments, and enrolment requirements still apply. If any row is invalid, nothing is imported.</p>
    <form method="POST" action="{{ route('admin.course-calls.import-applications', $courseCall) }}" enctype="multipart/form-data">
        @csrf
        <div class="form-group">
            <label for="application-import-file">Completed application CSV</label>
            <input id="application-import-file" type="file" name="file" accept=".csv,text/csv" required>
            @error('file') <p role="alert">{{ $message }}</p> @enderror
        </div>
        <button type="submit" class="btn btn-primary"><i class="fas fa-upload"></i> Upload applicants</button>
    </form>
</section>
@endif
