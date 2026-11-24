{{--
    "Who can see this" for lessons and announcements.
    Blank = the whole course (shared content: only for staff who may edit it);
    a cohort = that group only. Branch instructors see just their own groups.
    Params: $selected (?int), $sharedLabel, $label, $id (optional)
--}}
@php
    $canEditShared = $canEditShared ?? true;
    $scopeCohorts = $scopeCohorts ?? collect();
    $selected = $selected ?? null;
@endphp
<div>
    <label @isset($id) for="{{ $id }}" @endisset>{{ $label ?? 'Who can see this' }}</label>
    <select name="cohort_id" @isset($id) id="{{ $id }}" @endisset @unless($canEditShared) required @endunless>
        @if($canEditShared)
            <option value="">{{ $sharedLabel ?? 'Whole course (all groups)' }}</option>
        @else
            <option value="" disabled @selected($selected === null)>Choose your group</option>
        @endif
        @foreach($scopeCohorts as $cohort)
            <option value="{{ $cohort->id }}" @selected((int) $selected === (int) $cohort->id)>Only {{ $cohort->name }}</option>
        @endforeach
    </select>
    @if($scopeCohorts->isEmpty() && ! $canEditShared)
        <small class="icm-muted">None of this course's groups run at your branch yet. Ask an administrator to add one.</small>
    @endif
</div>
