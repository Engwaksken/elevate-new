{{-- Courses a library book belongs to. Optional; checked courses limit the book to their enrolled participants. --}}
@php($selectedCourseIds = array_map('intval', (array) old('course_ids', $selected ?? [])))
<div class="form-group full">
<label>Courses</label>
<input type="hidden" name="sync_courses" value="1">
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:8px;padding:10px;border:1px solid var(--border-color,#d0d5dd);border-radius:8px;max-height:200px;overflow:auto">
@forelse($courses as $course)
<label style="display:flex;align-items:center;gap:8px;font-weight:400"><input type="checkbox" name="course_ids[]" value="{{ $course->id }}" @checked(in_array($course->id, $selectedCourseIds, true))> {{ $course->title }}@if($course->code) <small class="form-hint" style="display:inline">({{ $course->code }})</small>@endif</label>
@empty
<span class="form-hint">No courses are available.</span>
@endforelse
</div>
<small class="form-hint">Only participants enrolled in a ticked course can see and read this book. Leave all unticked to make it available to every participant.</small>
</div>
