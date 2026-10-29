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
            <strong><i class="fas fa-wand-magic-sparkles"></i> Suggestions for you</strong>
            <p style="white-space:pre-wrap">{{ session('ai_suggestion') }}</p>
            <small>Only use suggestions that are true for you.</small>
        </div>
    @endif
    @if($draft = session('ai_letter_draft'))
        <div class="ai-note">
            <strong><i class="fas fa-wand-magic-sparkles"></i> Improved version of "{{ $draft['title'] }}"</strong>
            <form method="POST" action="{{ route('career.cover-letter.ai.apply', $draft['id']) }}" style="margin-top:8px">@csrf
                <label for="ai-letter-draft" class="sr-only">Improved cover letter</label>
                <textarea id="ai-letter-draft" name="body" rows="10" maxlength="50000">{{ $draft['body'] }}</textarea>
                <details style="margin-top:8px"><summary style="cursor:pointer">Show my original letter</summary><p style="white-space:pre-wrap;margin-top:6px">{{ $draft['original'] }}</p></details>
                <small class="form-hint">Read it and change any words you like. Your original is only replaced when you click "Use this version".</small>
                <div style="display:flex;gap:8px;margin-top:8px;flex-wrap:wrap">
                    <button class="btn btn-primary btn-sm"><i class="fas fa-check"></i> Use this version</button>
                    <a class="btn btn-outline btn-sm" href="{{ route('career.resume.index') }}#cover">Keep my original</a>
                </div>
            </form>
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

                        <div class="admin-panel"><div class="admin-table-wrap"><table class="admin-table">
                            <thead><tr><th>Resume</th><th>Template</th><th>Completion</th><th class="table-actions">Actions</th></tr></thead>
                            <tbody>
                            @forelse($resumes as $resume)
                                <tr>
                                    <td><strong>{{ $resume->title }}</strong>@if($resume->is_default) <span class="status-chip active">Default</span>@endif</td>
                                    <td>{{ ucfirst($resume->template) }}</td>
                                    <td>{{ $resume->completion_percent ?? 0 }}% complete</td>
                                    <td class="table-actions"><div class="action-group">
                                        <a href="{{ route('career.resume.edit',$resume) }}" class="btn btn-outline btn-sm"><i class="fas fa-pen"></i> Edit</a>
                                        <a href="{{ route('career.resume.download',$resume) }}" class="btn btn-primary btn-sm"><i class="fas fa-file-pdf"></i> PDF</a>
                                        <button type="button" class="btn btn-outline btn-sm" data-document-share="{{ route('career.documents.share',['type'=>'resume','id'=>$resume->id]) }}"><i class="fas fa-share-nodes"></i> Share</button>
                                        <form method="POST" action="{{ route('career.resume.destroy',$resume) }}" onsubmit="return confirm('Delete this resume? This cannot be undone.')">@csrf @method('DELETE')
                                            <button class="btn btn-danger btn-sm"><i class="fas fa-trash"></i> Delete</button>
                                        </form>
                                    </div></td>
                                </tr>
                            @empty
                                <tr><td colspan="4"><div class="admin-empty"><i class="fas fa-file-circle-plus"></i><strong>No resumes yet</strong><span>Create one manually or upload an existing resume.</span></div></td></tr>
                            @endforelse
                            </tbody>
                        </table></div></div>
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
                        <div class="admin-panel"><div class="admin-table-wrap"><table class="admin-table">
                            <thead><tr><th>File</th><th>Type</th><th>Size</th><th>Status</th><th>Uploaded</th><th class="table-actions">Actions</th></tr></thead>
                            <tbody>
                            @forelse($resumeUploads as $upload)
                                <tr>
                                    <td><strong>{{ $upload->original_name }}</strong></td>
                                    <td>{{ strtoupper(pathinfo($upload->original_name,PATHINFO_EXTENSION)) }}</td>
                                    <td>{{ number_format($upload->file_size/1024,1) }} KB</td>
                                    <td><span class="status-chip {{ $upload->status }}">{{ ucfirst($upload->status) }}</span></td>
                                    <td>{{ $upload->created_at->format('d M Y H:i') }}</td>
                                    <td class="table-actions"><div class="action-group">
                                        <a class="btn btn-outline btn-sm" href="{{ route('career.resume.upload.review',$upload) }}"><i class="fas fa-eye"></i> Review</a>
                                        <a class="btn btn-outline btn-sm" href="{{ route('career.resume.upload.original',[$upload,'preview'=>1]) }}" target="_blank" rel="noopener" data-file-preview data-file-preview-title="{{ $upload->original_name }}"><i class="fas fa-eye"></i> Preview</a>
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
                                    </div></td>
                                </tr>
                            @empty
                                <tr><td colspan="6"><div class="admin-empty">No uploaded resumes yet.</div></td></tr>
                            @endforelse
                            </tbody>
                        </table></div></div>

                        <h3 class="subheading">Cover letter uploads</h3>
                        <div class="admin-panel"><div class="admin-table-wrap"><table class="admin-table">
                            <thead><tr><th>File</th><th>Type</th><th>Size</th><th>Status</th><th>Uploaded</th><th class="table-actions">Actions</th></tr></thead>
                            <tbody>
                            @forelse($coverLetterUploads as $upload)
                                <tr>
                                    <td><strong>{{ $upload->original_name }}</strong></td>
                                    <td>{{ strtoupper(pathinfo($upload->original_name,PATHINFO_EXTENSION)) }}</td>
                                    <td>{{ number_format($upload->file_size/1024,1) }} KB</td>
                                    <td><span class="status-chip {{ $upload->status }}">{{ ucfirst($upload->status) }}</span></td>
                                    <td>{{ $upload->created_at->format('d M Y H:i') }}</td>
                                    <td class="table-actions"><div class="action-group">
                                        <a class="btn btn-outline btn-sm" href="{{ route('career.cover-letter.upload.review',$upload) }}"><i class="fas fa-eye"></i> Review</a>
                                        <a class="btn btn-outline btn-sm" href="{{ route('career.cover-letter.upload.original',[$upload,'preview'=>1]) }}" target="_blank" rel="noopener" data-file-preview data-file-preview-title="{{ $upload->original_name }}"><i class="fas fa-eye"></i> Preview</a>
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
                                    </div></td>
                                </tr>
                            @empty
                                <tr><td colspan="6"><div class="admin-empty">No uploaded cover letters yet.</div></td></tr>
                            @endforelse
                            </tbody>
                        </table></div></div>
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

                        <div class="admin-panel spacing-top"><div class="admin-table-wrap"><table class="admin-table">
                            <thead><tr><th>Cover letter</th><th>Employer</th><th>Source</th><th class="table-actions">Actions</th></tr></thead>
                            <tbody>
                            @forelse($coverLetters as $letter)
                                <tr>
                                    <td><strong>{{ $letter->title }}</strong></td>
                                    <td>{{ $letter->employer_name ?: 'General' }}</td>
                                    <td>@if($letter->ai_generated)<span class="status-chip active">AI generated</span>@else Written @endif</td>
                                    <td class="table-actions"><div class="action-group">
                                        <a class="btn btn-primary btn-sm" href="{{ route('career.cover-letter.download',$letter) }}"><i class="fas fa-file-pdf"></i> PDF</a>
                                        <button type="button" class="btn btn-outline btn-sm" data-document-share="{{ route('career.documents.share',['type'=>'cover-letter','id'=>$letter->id]) }}"><i class="fas fa-share-nodes"></i> Share</button>
                                        <form method="POST" action="{{ route('career.cover-letter.ai.improve',$letter) }}">@csrf<button class="btn btn-outline btn-sm" title="AI rewrites your letter in clear, natural language. You choose whether to use it."><i class="fas fa-wand-magic-sparkles"></i> Improve with AI</button></form>
                                        <button type="button" class="btn btn-outline btn-sm" data-modal-open="cover-letter-edit-{{ $letter->id }}"><i class="fas fa-pen"></i> Edit</button>
                                        <form method="POST" action="{{ route('career.cover-letter.destroy',$letter) }}" onsubmit="return confirm('Delete this cover letter?')">@csrf @method('DELETE')<button class="btn btn-danger btn-sm"><i class="fas fa-trash"></i> Delete</button></form>
                                    </div></td>
                                </tr>
                            @empty
                                <tr><td colspan="4"><div class="admin-empty">No saved cover letters yet.</div></td></tr>
                            @endforelse
                            </tbody>
                        </table></div></div>

                        @foreach($coverLetters as $letter)
                        <div class="eh-modal" id="cover-letter-edit-{{ $letter->id }}" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="cover-letter-edit-{{ $letter->id }}-title">
                        <div class="eh-modal-dialog">
                            <form method="POST" action="{{ route('career.cover-letter.update',$letter) }}">@csrf @method('PUT')
                                <div class="eh-modal-header">
                                    <div><h2 id="cover-letter-edit-{{ $letter->id }}-title">Edit cover letter</h2><p>{{ $letter->title }}</p></div>
                                    <button type="button" class="eh-modal-close" data-modal-close aria-label="Close"><i class="fas fa-xmark"></i></button>
                                </div>
                                <div class="eh-modal-body">
                                    <div class="form-group"><label>Title</label><input name="title" value="{{ $letter->title }}" required maxlength="190"></div>
                                    <div class="form-group"><label>Employer</label><input name="employer_name" value="{{ $letter->employer_name }}" maxlength="190"></div>
                                    <div class="form-group"><label>Job title</label><input name="job_title" value="{{ $letter->job_title }}" maxlength="190"></div>
                                    <div class="form-group"><label>Recipient</label><input name="recipient_name" value="{{ $letter->recipient_name }}" maxlength="190"></div>
                                    <div class="form-group"><label>Body</label><textarea name="body" rows="10" required>{{ $letter->body }}</textarea></div>
                                </div>
                                <div class="eh-modal-footer">
                                    <button type="button" class="btn btn-outline" data-modal-close>Cancel</button>
                                    <button class="btn btn-primary">Save changes</button>
                                </div>
                            </form>
                        </div>
                        </div>
                        @endforeach
                    </div>
                </section>

                <section class="eh-tab-pane" data-eh-pane="ai">
                    <div class="eh-tab-section">
                        <div class="eh-tab-section-header"><div><h2>Write a cover letter with AI</h2><p>Tell us the job you want. AI writes a letter from your resume in clear, natural language. You can read it and change anything before you send it.</p></div></div>
                        <div class="career-form-panel">
                            @if($resumes->count())
                                <ol class="form-hint" style="margin:0 0 14px;padding-left:18px;line-height:1.7">
                                    <li>Choose your resume and type the job title.</li>
                                    <li>Add the employer and paste the job advert if you have them. This makes the letter better, but you can skip it.</li>
                                    <li>Click "Write my cover letter". It appears in the Cover Letters tab, where you can improve, edit or download it.</li>
                                </ol>
                                <form method="POST" action="{{ route('career.cover-letter.generate') }}">@csrf
                                    <div class="form-grid">
                                        <div class="form-group"><label for="gen-resume">Which resume? <span class="required">*</span></label><select id="gen-resume" name="resume_id" required>@foreach($resumes as $resume)<option value="{{ $resume->id }}">{{ $resume->title }}</option>@endforeach</select></div>
                                        <div class="form-group"><label for="gen-job">Job title <span class="required">*</span></label><input id="gen-job" name="job_title" required placeholder="For example: Program Officer"></div>
                                        <div class="form-group"><label for="gen-employer">Employer (optional)</label><input id="gen-employer" name="employer_name" placeholder="For example: ABC Organisation"></div>
                                        <div class="form-group"><label for="gen-tone">How should it sound?</label><select id="gen-tone" name="tone"><option value="professional">Polite and professional</option><option value="warm">Warm and friendly</option><option value="confident">Confident</option><option value="concise">Short and simple</option></select></div>
                                    </div>
                                    <div class="form-group"><label for="gen-advert">Job advert (optional)</label><textarea id="gen-advert" name="job_description" rows="5" placeholder="Copy the job advert from the website or email and paste it here..."></textarea><small class="form-hint">AI only uses facts from your resume. It will not add qualifications you do not have.</small></div>
                                    <button class="btn btn-primary"><i class="fas fa-wand-magic-sparkles"></i> Write my cover letter</button>
                                </form>
                            @else
                                <div class="eh-empty"><p>Create or upload a resume first. AI uses it to write your cover letter.</p></div>
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
                        <div class="admin-panel"><div class="admin-table-wrap"><table class="admin-table">
                            <thead><tr><th>Resume</th><th>File</th><th class="table-actions">Actions</th></tr></thead>
                            <tbody>
                            @forelse($resumes as $resume)
                                <tr>
                                    <td><strong>{{ $resume->title }}</strong></td>
                                    <td>Current generated resume</td>
                                    <td class="table-actions"><a class="btn btn-primary btn-sm" href="{{ route('career.resume.download',$resume) }}"><i class="fas fa-download"></i> Download PDF</a></td>
                                </tr>
                            @empty
                                <tr><td colspan="3"><div class="admin-empty">No resumes to download yet.</div></td></tr>
                            @endforelse
                            </tbody>
                        </table></div></div>
                    </div>
                </section>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-document-share]').forEach(button => {
        button.addEventListener('click', async () => {
            button.disabled = true;
            try {
                const response = await fetch(button.dataset.documentShare, {
                    method: 'POST',
                    headers: {'Accept':'application/json','X-CSRF-TOKEN': @json(csrf_token())}
                });
                if (!response.ok) throw new Error('Unable to create a share link. Please try again.');
                const data = await response.json();
                if (navigator.share) {
                    try { await navigator.share({title: 'Career document', text: 'Link expires in seven days.', url: data.url}); }
                    catch (error) { if (error.name !== 'AbortError') window.prompt('Copy this link (expires in seven days):', data.url); }
                } else {
                    window.prompt('Copy this link (expires in seven days):', data.url);
                }
            } catch (error) { window.alert(error.message); }
            finally { button.disabled = false; }
        });
    });
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
