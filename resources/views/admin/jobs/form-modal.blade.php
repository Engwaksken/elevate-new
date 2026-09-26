<div class="eh-modal" id="{{ $id }}" aria-hidden="true"><div class="eh-modal-dialog eh-modal-lg">
<div class="eh-modal-header"><div><h2>{{ $title }}</h2><p>Complete the job opportunity details.</p></div><button type="button" class="eh-modal-close" data-modal-close><i class="fas fa-xmark"></i></button></div>
<form method="POST" action="{{ $action }}">@csrf @if($method==='PUT') @method('PUT') @endif
<div class="eh-modal-body"><div class="modal-grid">
<div class="form-group full"><label>Job Title *</label><input name="title" value="{{ $job->title }}" placeholder="e.g. Junior Software Developer" required><small class="form-hint">Required. Use the job title candidates will recognise.</small></div>
<div class="form-group"><label>Employer *</label><select name="employer_id" required><option value="">Select employer</option>@foreach($employers as $e)<option value="{{ $e->id }}" @selected((string)$job->employer_id===(string)$e->id)>{{ $e->company_name }}</option>@endforeach</select></div>
<div class="form-group"><label>Industry</label><input name="industry" value="{{ $job->industry }}" placeholder="e.g. Technology"></div>
<div class="form-group"><label>Category</label><input name="category" value="{{ $job->category }}" placeholder="e.g. Software Development"></div>
<div class="form-group"><label>Location</label><input name="location" value="{{ $job->location }}" placeholder="e.g. Kampala"></div>
<div class="form-group"><label>Country *</label><input name="country" value="{{ $job->country ?: 'Uganda' }}" placeholder="e.g. Uganda" required></div>
<div class="form-group"><label>Employment Type *</label><select name="employment_type">@foreach(['full_time'=>'Full time','part_time'=>'Part time','contract'=>'Contract','internship'=>'Internship','temporary'=>'Temporary'] as $v=>$l)<option value="{{ $v }}" @selected($job->employment_type===$v)>{{ $l }}</option>@endforeach</select></div>
<div class="form-group"><label>Work Arrangement *</label><select name="work_arrangement">@foreach(['onsite'=>'Onsite','hybrid'=>'Hybrid','remote'=>'Remote'] as $v=>$l)<option value="{{ $v }}" @selected($job->work_arrangement===$v)>{{ $l }}</option>@endforeach</select></div>
<div class="form-group"><label>Experience Level</label><input name="experience_level" value="{{ $job->experience_level }}" placeholder="e.g. Entry level"></div>
<div class="form-group"><label>Education Level</label><input name="education_level" value="{{ $job->education_level }}" placeholder="e.g. Diploma or Bachelor's degree"></div>
<div class="form-group"><label>Salary Min</label><input type="number" min="0" name="salary_min" value="{{ $job->salary_min }}" placeholder="e.g. 1000000"></div>
<div class="form-group"><label>Salary Max</label><input type="number" min="0" name="salary_max" value="{{ $job->salary_max }}" placeholder="e.g. 2500000"></div>
<div class="form-group"><label>Currency *</label><input name="salary_currency" maxlength="3" value="{{ $job->salary_currency ?: 'UGX' }}" placeholder="UGX" required></div>
<div class="form-group"><label>Positions *</label><input type="number" min="1" name="positions" value="{{ $job->positions ?: 1 }}" required></div>
<div class="form-group"><label>Deadline</label><input type="date" name="application_deadline" value="{{ optional($job->application_deadline)->format('Y-m-d') }}"></div>
<div class="form-group"><label>Status *</label><select name="status">@foreach(['draft','pending','published','closed','rejected'] as $s)<option value="{{ $s }}" @selected(($job->status ?: 'draft')===$s)>{{ ucfirst($s) }}</option>@endforeach</select></div>
<div class="form-group full"><label>Skills</label><input name="skills_text" value="{{ implode(', ',$job->skills ?? []) }}" placeholder="e.g. PHP, Laravel, Communication, Git"><small class="form-hint">Separate skills with commas.</small></div>
<div class="form-group full"><label>Description *</label><textarea name="description" rows="5" placeholder="Describe the role, purpose and opportunity..." required>{{ $job->description }}</textarea></div>
<div class="form-group full"><label>Responsibilities</label><textarea name="responsibilities" rows="4" placeholder="List the main responsibilities...">{{ $job->responsibilities }}</textarea></div>
<div class="form-group full"><label>Requirements</label><textarea name="requirements" rows="4" placeholder="List qualifications and requirements...">{{ $job->requirements }}</textarea></div>
</div></div>
<div class="eh-modal-footer"><button type="button" class="btn btn-outline" data-modal-close>Cancel</button><button class="btn btn-primary">{{ $method==='POST' ? 'Create Job':'Save Changes' }}</button></div>
</form></div></div>