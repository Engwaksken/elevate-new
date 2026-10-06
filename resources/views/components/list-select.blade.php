@props([
    // Field name, e.g. "department" or "funding_source".
    'name',
    // Active names to choose from (managed in Departments / Funding Sources).
    'options' => [],
    // Current value. Shown even when it is no longer on the list, so older
    // records that hold a typed value still display and can be saved as-is.
    'selected' => null,
    'placeholder' => 'Select…',
    // Optional "manage the list" link for people who may edit it.
    'manageUrl' => null,
    'manageLabel' => 'Manage list',
    'emptyHint' => 'No options have been added yet.',
])
@php
    $listOptions = collect($options)->map(fn ($o) => (string) $o)->values();
    $listSelected = $selected === null ? '' : (string) $selected;
    $listLegacy = $listSelected !== '' && ! $listOptions->contains($listSelected);
@endphp
<select name="{{ $name }}" {{ $attributes }}>
    <option value="">{{ $placeholder }}</option>
    @if($listLegacy)
        <option value="{{ $listSelected }}" selected>{{ $listSelected }} (not on the list)</option>
    @endif
    @foreach($listOptions as $option)
        <option value="{{ $option }}" @selected($option === $listSelected)>{{ $option }}</option>
    @endforeach
</select>
@if($listOptions->isEmpty())
    <small class="form-hint">{{ $emptyHint }} @if($manageUrl)<a href="{{ $manageUrl }}">{{ $manageLabel }}</a>@endif</small>
@elseif($manageUrl)
    <small class="form-hint"><a href="{{ $manageUrl }}">{{ $manageLabel }}</a></small>
@endif
