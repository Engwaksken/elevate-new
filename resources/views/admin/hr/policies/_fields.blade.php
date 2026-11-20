@php $pol = $policy ?? null; $pid = $pol ? 'pol-'.$pol->id : 'pol-new'; @endphp
<div class="modal-grid">
    <div class="form-group" style="grid-column:1/-1"><label for="{{ $pid }}-t">Title *</label><input id="{{ $pid }}-t" name="title" value="{{ old('title', $pol?->title) }}" required maxlength="190"></div>
    <div class="form-group"><label for="{{ $pid }}-c">Category</label><select id="{{ $pid }}-c" name="category"><option value="">Select</option>@foreach(\App\Models\HrPolicy::CATEGORIES as $c)<option @selected(old('category', $pol?->category) === $c)>{{ $c }}</option>@endforeach</select></div>
    <div class="form-group"><label for="{{ $pid }}-v">Version</label><input id="{{ $pid }}-v" name="version" value="{{ old('version', $pol?->version) }}" placeholder="e.g. 2.1"></div>
    <div class="form-group"><label for="{{ $pid }}-e">Effective date</label><input id="{{ $pid }}-e" type="date" name="effective_date" value="{{ old('effective_date', optional($pol?->effective_date)->format('Y-m-d')) }}"></div>
    <div class="form-group"><label for="{{ $pid }}-d">Days to acknowledge</label><input id="{{ $pid }}-d" type="number" name="due_days" min="1" max="120" value="{{ old('due_days', $pol?->due_days ?? 7) }}"></div>
    <div class="form-group" style="grid-column:1/-1"><label for="{{ $pid }}-s">Summary</label><textarea id="{{ $pid }}-s" name="summary" rows="2" placeholder="What staff need to know in a sentence or two">{{ old('summary', $pol?->summary) }}</textarea></div>
    <div class="form-group" style="grid-column:1/-1"><label for="{{ $pid }}-f">Policy document {{ $pol ? '(upload to replace)' : '' }}</label><input id="{{ $pid }}-f" type="file" name="file" accept=".pdf,.doc,.docx">@if($pol?->file_name)<small class="form-hint">Current: {{ $pol->file_name }}</small>@else<small class="form-hint">PDF or Word, up to 20 MB. Or type the policy text below.</small>@endif</div>
    <div class="form-group" style="grid-column:1/-1"><label for="{{ $pid }}-b">Policy text</label><textarea id="{{ $pid }}-b" name="body" rows="6">{{ old('body', $pol?->body) }}</textarea></div>
    <div class="form-group" style="grid-column:1/-1">
        <label style="display:flex;gap:8px;align-items:center;font-weight:500"><input type="hidden" name="requires_acknowledgement" value="0"><input type="checkbox" name="requires_acknowledgement" value="1" @checked(old('requires_acknowledgement', $pol?->requires_acknowledgement ?? true)) style="width:auto"> Staff must confirm they have read and understood it</label>
        <label style="display:flex;gap:8px;align-items:center;font-weight:500"><input type="hidden" name="assign_to_new_staff" value="0"><input type="checkbox" name="assign_to_new_staff" value="1" @checked(old('assign_to_new_staff', $pol?->assign_to_new_staff ?? true)) style="width:auto"> Share automatically with every new staff member (onboarding)</label>
        @if($pol)
            <label style="display:flex;gap:8px;align-items:center;font-weight:500"><input type="hidden" name="is_active" value="0"><input type="checkbox" name="is_active" value="1" @checked($pol->is_active) style="width:auto"> Active</label>
            <label style="display:flex;gap:8px;align-items:center;font-weight:500"><input type="checkbox" name="reacknowledge" value="1" style="width:auto"> This is a new version - ask everyone to acknowledge again</label>
        @endif
    </div>
</div>
