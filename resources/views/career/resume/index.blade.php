@extends('layouts.app')
@section('title','Resume & Cover Letter Centre - ElevateHer360')
@section('content')
<div class="career-page">
    <div class="page-header">
        <div>
            <span class="eyebrow">Career Development</span>
            <h1>Resume & Cover Letter Centre</h1>
            <p>Create, upload, manage and improve professional career documents.</p>
        </div>
    </div>

    @if(session('success')) <div class="success-box">{{ session('success') }}</div> @endif
    @if(session('warning')) <div class="warning-box">{{ session('warning') }}</div> @endif
    @if(session('ai_suggestion'))
        <div class="ai-note">
            <strong><i class="fas fa-wand-magic-sparkles"></i> AI suggestion</strong>
            <p style="white-space:pre-wrap">{{ session('ai_suggestion') }}</p>
            <small>Confirm that every suggestion accurately represents your experience and qualifications.</small>
        </div>
    @endif

    <div class="career-workspace">
        <div class="eh-tabs" data-eh-tabs>
            <div class="eh-tab-nav" role="tablist">
                <button class="eh-tab-button active" data-eh-tab="resumes"><i class="fas fa-file-lines"></i> My Resumes</button>
                <button class="eh-tab-button" data-eh-tab="upload"><i class="fas fa-cloud-arrow-up"></i> Upload Resume</button>
                <button class="eh-tab-button" data-eh-tab="uploads"><i class="fas fa-folder-open"></i> Uploaded Files</button>
                <button class="eh-tab-button" data-eh-tab="cover"><i class="fas fa-envelope-open-text"></i> Cover Letters</button>
                <button class="eh-tab-button" data-eh-tab="ai"><i class="fas fa-wand-magic-sparkles"></i> AI Assistant</button>
                <button class="eh-tab-button" data-eh-tab="templates"><i class="fas fa-palette"></i> Templates</button>
                <button class="eh-tab-button" data-eh-tab="downloads"><i class="fas fa-download"></i> Downloads</button>
            </div>

            <div class="eh-tab-content">
                <section class="eh-tab-pane active" data-eh-pane="resumes">
                    <div class="eh-tab-section">
                        <div class="eh-tab-section-header">
                            <div>
                                <h2>My Resumes</h2>
                                <p>Maintain different resumes for different opportunities.</p>
                            </div>
                            <a href="{{ route('career.resume.create') }}" class="btn btn-primary"><i class="fas fa-plus"></i> Create Resume</a>
                        </div>

                        <div class="eh-data-list">
                            @forelse($resumes as $resume)
                                <div class="eh-data-row">
                                    <div class="eh-data-row-main">
                                        <span class="eh-data-row-icon"><i class="fas fa-file-lines"></i></span>
                                        <div class="eh-data-row-copy">
                                            <strong>{{ $resume->title }}</strong>
                                            <span>{{ ucfirst($resume->template) }} · {{ $resume->completion_percent ?? 0 }}% complete @if($resume->is_default) · Default @endif</span>
                                        </div>
                                    </div>
                                    <div class="eh-data-row-actions">
                                        <a href="{{ route('career.resume.edit',$resume) }}" class="btn btn-outline btn-sm"><i class="fas fa-pen"></i> Edit</a>
                                        <a href="{{ route('career.resume.download',$resume) }}" class="btn btn-primary btn-sm"><i class="fas fa-file-pdf"></i> PDF</a>
                                        <form method="POST" action="{{ route('career.resume.destroy',$resume) }}" onsubmit="return confirm('Delete this resume? This cannot be undone.')">@csrf @method('DELETE')
                                            <button class="btn btn-danger btn-sm"><i class="fas fa-trash"></i> Delete</button>
                                        </form>
                                    </div>
                                </div>
                            @empty
                                <div class="eh-empty"><i class="fas fa-file-circle-plus"></i><h3>No resumes yet</h3><p>Create one manually or upload an existing resume.</p></div>
                            @endforelse
                        </div>
                    </div>
                </section>

                <section class="eh-tab-pane" data-eh-pane="upload">
                    <div class="eh-tab-section">
                        <div class="eh-tab-section-header">
                            <div><h2>Upload Existing Resume</h2><p>Upload PDF, DOC or DOCX. Maximum file size: 10 MB.</p></div>
                        </div>
                        <div class="career-form-panel">
                            <form method="POST" action="{{ route('career.resume.upload.store') }}" enctype="multipart/form-data">@csrf
                                <div class="upload-dropzone">
                                    <i class="fas fa-cloud-arrow-up"></i>
                                    <h3>Choose your current resume</h3>
                                    <p>We will extract the content and let you review it before importing.</p>
                                    <div class="form-group">
                                        <label for="resume_file">Resume file <span class="required">*</span></label>
                                        <input id="resume_file" type="file" name="resume_file" accept=".pdf,.doc,.docx" required>
                                        <small class="form-hint">Supported formats: PDF, DOC, DOCX. Maximum 10 MB.</small>
                                    </div>
                                </div>
                                <div class="form-actions"><button class="btn btn-primary"><i class="fas fa-wand-magic-sparkles"></i> Upload and Analyse</button></div>
                            </form>
                        </div>
                    </div>
                </section>

                <section class="eh-tab-pane" data-eh-pane="uploads">
                    <div class="eh-tab-section">
                        <div class="eh-tab-section-header"><div><h2>Uploaded Files</h2><p>Track, replace, retry, download or delete your original files.</p></div></div>

                        <h3 class="subheading">Resume uploads</h3>
                        <div class="eh-data-list">
                            @forelse($resumeUploads as $upload)
                                <div class="eh-data-row">
                                    <div class="eh-data-row-main">
                                        <span class="eh-data-row-icon"><i class="fas fa-file-arrow-up"></i></span>
                                        <div class="eh-data-row-copy">
                                            <strong>{{ $upload->original_name }}</strong>
                                            <span>{{ strtoupper(pathinfo($upload->original_name,PATHINFO_EXTENSION)) }} · {{ number_format($upload->file_size/1024,1) }} KB · {{ ucfirst($upload->status) }} · {{ $upload->created_at->format('d M Y H:i') }}</span>
                                        </div>
                                    </div>
                                    <div class="eh-data-row-actions">
                                        <a class="btn btn-outline btn-sm" href="{{ route('career.resume.upload.review',$upload) }}"><i class="fas fa-eye"></i> Review</a>
                                        <a class="btn btn-outline btn-sm" href="{{ route('career.resume.upload.original',$upload) }}"><i class="fas fa-download"></i> Original</a>
                                        @if($upload->status==='failed')
                                            <form method="POST" action="{{ route('career.resume.upload.retry',$upload) }}">@csrf<button class="btn btn-outline btn-sm"><i class="fas fa-rotate"></i> Retry</button></form>
                                        @endif
                                        <details class="replace-details"><summary class="btn btn-outline btn-sm"><i class="fas fa-arrows-rotate"></i> Replace</summary>
                                            <form method="POST" action="{{ route('career.resume.upload.replace',$upload) }}" enctype="multipart/form-data" class="replace-form">@csrf
                                                <input type="file" name="resume_file" accept=".pdf,.doc,.docx" required>
                                                <button class="btn btn-primary btn-sm">Upload replacement</button>
                                            </form>
                                        </details>
                                        <form method="POST" action="{{ route('career.resume.upload.destroy',$upload) }}" onsubmit="return confirm('Delete this uploaded resume and its extracted data?')">@csrf @method('DELETE')
                                            <button class="btn btn-danger btn-sm"><i class="fas fa-trash"></i> Delete</button>
                                        </form>
                                    </div>
                                </div>
                            @empty
                                <div class="eh-empty"><p>No uploaded resumes yet.</p></div>
                            @endforelse
                        </div>

                        <h3 class="subheading">Cover letter uploads</h3>
                        <div class="eh-data-list">
                            @forelse($coverLetterUploads as $upload)
                                <div class="eh-data-row">
                                    <div class="eh-data-row-main">
                                        <span class="eh-data-row-icon"><i class="fas fa-envelope"></i></span>
                                        <div class="eh-data-row-copy">
                                            <strong>{{ $upload->original_name }}</strong>
                                            <span>{{ strtoupper(pathinfo($upload->original_name,PATHINFO_EXTENSION)) }} · {{ number_format($upload->file_size/1024,1) }} KB · {{ ucfirst($upload->status) }} · {{ $upload->created_at->format('d M Y H:i') }}</span>
                                        </div>
                                    </div>
                                    <div class="eh-data-row-actions">
                                        <a class="btn btn-outline btn-sm" href="{{ route('career.cover-letter.upload.review',$upload) }}"><i class="fas fa-eye"></i> Review</a>
                                        <a class="btn btn-outline btn-sm" href="{{ route('career.cover-letter.upload.original',$upload) }}"><i class="fas fa-download"></i> Original</a>
                                        @if($upload->status==='failed')
                                            <form method="POST" action="{{ route('career.cover-letter.upload.retry',$upload) }}">@csrf<button class="btn btn-outline btn-sm"><i class="fas fa-rotate"></i> Retry</button></form>
                                        @endif
                                        <details class="replace-details"><summary class="btn btn-outline btn-sm"><i class="fas fa-arrows-rotate"></i> Replace</summary>
                                            <form method="POST" action="{{ route('career.cover-letter.upload.replace',$upload) }}" enctype="multipart/form-data" class="replace-form">@csrf
                                                <input type="file" name="cover_letter_file" accept=".pdf,.doc,.docx" required>
                                                <button class="btn btn-primary btn-sm">Upload replacement</button>
                                            </form>
                                        </details>
                                        <form method="POST" action="{{ route('career.cover-letter.upload.destroy',$upload) }}" onsubmit="return confirm('Delete this uploaded cover letter and its extracted data?')">@csrf @method('DELETE')
                                            <button class="btn btn-danger btn-sm"><i class="fas fa-trash"></i> Delete</button>
                                        </form>
                                    </div>
                                </div>
                            @empty
                                <div class="eh-empty"><p>No uploaded cover letters yet.</p></div>
                            @endforelse
                        </div>
                    </div>
                </section>

                <section class="eh-tab-pane" data-eh-pane="cover">
                    <div class="eh-tab-section">
                        <div class="eh-tab-section-header"><div><h2>Cover Letters</h2><p>Create manually, upload an existing draft or generate a tailored draft with AI.</p></div></div>

                        <div class="two-column-panels">
                            <div class="career-form-panel">
                                <h3>Create Cover Letter</h3>
                                <form method="POST" action="{{ route('career.cover-letter.store') }}">@csrf
                                    <div class="form-group"><label>Title <span class="required">*</span></label><input name="title" required placeholder="e.g. Software Developer Cover Letter"><small class="form-hint">Use a title that helps you identify the target role.</small></div>
                                    <div class="form-grid">
                                        <div class="form-group"><label>Employer</label><input name="employer_name" placeholder="e.g. Women in Technology Uganda"></div>
                                        <div class="form-group"><label>Job title</label><input name="job_title" placeholder="e.g. Software Developer"></div>
                                    </div>
                                    <div class="form-group"><label>Body</label><textarea name="body" rows="10" placeholder="Write or paste your cover letter draft here..."></textarea><small class="form-hint">Keep the content accurate and tailored to the opportunity.</small></div>
                                    <button class="btn btn-primary"><i class="fas fa-floppy-disk"></i> Save Cover Letter</button>
                                </form>
                            </div>

                            <div class="career-form-panel">
                                <h3>Upload Existing Cover Letter</h3>
                                <form method="POST" action="{{ route('career.cover-letter.upload.store') }}" enctype="multipart/form-data">@csrf
                                    <div class="form-group"><label>Cover letter file <span class="required">*</span></label><input type="file" name="cover_letter_file" accept=".pdf,.doc,.docx" required><small class="form-hint">PDF, DOC or DOCX. Maximum 10 MB.</small></div>
                                    <button class="btn btn-primary"><i class="fas fa-cloud-arrow-up"></i> Upload and Analyse</button>
                                </form>
                            </div>
                        </div>

                        <div class="eh-data-list spacing-top">
                            @forelse($coverLetters as $letter)
                                <div class="eh-data-row">
                                    <div class="eh-data-row-main"><span class="eh-data-row-icon"><i class="fas fa-envelope-open-text"></i></span><div class="eh-data-row-copy"><strong>{{ $letter->title }}</strong><span>{{ $letter->employer_name ?: 'General' }} @if($letter->ai_generated) · AI generated @endif</span></div></div>
                                </div>
                            @empty
                                <div class="eh-empty"><p>No saved cover letters yet.</p></div>
                            @endforelse
                        </div>
                    </div>
                </section>

                <section class="eh-tab-pane" data-eh-pane="ai">
                    <div class="eh-tab-section">
                        <div class="eh-tab-section-header"><div><h2>AI Assistant</h2><p>AI provides suggestions only and does not invent qualifications or overwrite your content.</p></div></div>
                        <div class="career-form-panel">
                            @if($resumes->count())
                                <form method="POST" action="{{ route('career.cover-letter.generate') }}">@csrf
                                    <div class="form-grid">
                                        <div class="form-group"><label>Resume <span class="required">*</span></label><select name="resume_id" required>@foreach($resumes as $resume)<option value="{{ $resume->id }}">{{ $resume->title }}</option>@endforeach</select><small class="form-hint">Choose the resume that best matches the opportunity.</small></div>
                                        <div class="form-group"><label>Job title <span class="required">*</span></label><input name="job_title" required placeholder="e.g. Program Officer"></div>
                                        <div class="form-group"><label>Employer</label><input name="employer_name" placeholder="e.g. ABC Organisation"></div>
                                        <div class="form-group"><label>Tone</label><select name="tone"><option value="professional">Professional</option><option value="confident">Confident</option><option value="warm">Warm</option><option value="concise">Concise</option></select></div>
                                    </div>
                                    <div class="form-group"><label>Job description <span class="required">*</span></label><textarea name="job_description" rows="9" required placeholder="Paste the full job description here..."></textarea><small class="form-hint">AI compares this with your existing information and will not add qualifications you have not provided.</small></div>
                                    <button class="btn btn-primary"><i class="fas fa-wand-magic-sparkles"></i> Generate Cover Letter</button>
                                </form>
                            @else
                                <div class="eh-empty"><p>Create or upload a resume before using AI assistance.</p></div>
                            @endif
                        </div>
                    </div>
                </section>

                <section class="eh-tab-pane" data-eh-pane="templates">
                    <div class="eh-tab-section">
                        <div class="eh-tab-section-header"><div><h2>Resume Templates</h2><p>Select a resume first, then choose a presentation style. Template changes do not remove resume content.</p></div></div>
                        @if($resumes->count())
                            <div class="form-group template-resume-picker"><label>Select resume</label><select id="template-resume-select">@foreach($resumes as $resume)<option value="{{ $resume->id }}">{{ $resume->title }}</option>@endforeach</select></div>
                            <div class="resume-template-list">
                                @foreach([
                                    'classic'=>['Classic','Traditional and formal'],
                                    'modern'=>['Modern','Clean contemporary layout'],
                                    'minimal'=>['Minimal','Simple and highly readable'],
                                    'professional'=>['Professional','Balanced business layout'],
                                    'graduate'=>['Graduate','Ideal for early-career applicants'],
                                    'technology'=>['Technology','Strong fit for digital and technical roles'],
                                    'executive'=>['Executive','Leadership-focused presentation'],
                                ] as $key=>$meta)
                                    <form method="POST" action="{{ route('career.resume.template',$resumes->first()) }}" class="template-form" data-template-form data-template="{{ $key }}">@csrf @method('PUT')
                                        <input type="hidden" name="template" value="{{ $key }}">
                                        <button type="submit" class="resume-template-option">
                                            <span class="template-preview template-preview-{{ $key }}"><i class="fas fa-file-lines"></i></span>
                                            <strong>{{ $meta[0] }}</strong>
                                            <span>{{ $meta[1] }}</span>
                                            <em>Use template</em>
                                        </button>
                                    </form>
                                @endforeach
                            </div>
                        @else
                            <div class="eh-empty"><p>Create or import a resume before selecting a template.</p></div>
                        @endif
                    </div>
                </section>

                <section class="eh-tab-pane" data-eh-pane="downloads">
                    <div class="eh-tab-section">
                        <div class="eh-tab-section-header"><div><h2>Downloads</h2><p>Download current resume PDFs and original uploaded files.</p></div></div>
                        <div class="eh-data-list">
                            @foreach($resumes as $resume)
                                <div class="eh-data-row">
                                    <div class="eh-data-row-main"><span class="eh-data-row-icon"><i class="fas fa-file-pdf"></i></span><div class="eh-data-row-copy"><strong>{{ $resume->title }}</strong><span>Current generated resume</span></div></div>
                                    <div class="eh-data-row-actions"><a class="btn btn-primary btn-sm" href="{{ route('career.resume.download',$resume) }}"><i class="fas fa-download"></i> Download PDF</a></div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const select = document.getElementById('template-resume-select');
    if (!select) return;
    document.querySelectorAll('[data-template-form]').forEach(form => {
        form.addEventListener('submit', () => {
            const id = select.value;
            form.action = `{{ url('/career/resumes') }}/${id}/template`;
        });
    });
});
</script>
@endpush
@endsection
