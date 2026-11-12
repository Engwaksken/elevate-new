{{-- Staff branch and course assignments. Expects $branches, $courses, $assignedBranches, $assignedCourses, $prefix. --}}
<p class="form-hint" style="margin-top:0">@if(($isParticipant ?? false))Select the participant's branch and course enrolments.@else Select the branches and courses associated with this user. @endif</p>
<div class="modal-grid">
    <div class="form-group full">
        <label>{{ ($isParticipant ?? false) ? 'Branch' : 'Branches' }}</label>
        @if($branches->isEmpty())
            <div class="admin-readonly">No branches have been set up yet.</div>
        @else
        <div class="permission-check-grid">
            @foreach($branches as $branch)
            <label class="permission-check" for="{{ $prefix }}-branch-{{ $branch->id }}">
                <input id="{{ $prefix }}-branch-{{ $branch->id }}" type="checkbox" name="branches[]" value="{{ $branch->id }}" @checked(in_array((int) $branch->id, array_map('intval', $assignedBranches), true))>
                <span>{{ $branch->name }}{{ $branch->is_active ? '' : ' (inactive)' }}</span>
            </label>
            @endforeach
        </div>
        @endif
    </div>
    <div class="form-group full">
        <label>{{ ($isParticipant ?? false) ? 'Course enrolments' : 'Courses taught' }}</label>
        @if($courses->isEmpty())
            <div class="admin-readonly">No courses yet.</div>
        @else
        <div class="permission-check-grid" style="max-height:260px;overflow:auto">
            @foreach($courses as $course)
            <label class="permission-check" for="{{ $prefix }}-course-{{ $course->id }}">
                <input id="{{ $prefix }}-course-{{ $course->id }}" type="checkbox" name="courses[]" value="{{ $course->id }}" @checked(in_array((int) $course->id, array_map('intval', $assignedCourses), true))>
                <span>{{ $course->title }}{{ $course->code ? ' · '.$course->code : '' }}</span>
            </label>
            @endforeach
        </div>
        <small class="form-hint">{{ ($isParticipant ?? false) ? 'Selected courses will be enrolled for this participant.' : "Lead instructors are still set on each course's Assignments page." }}</small>
        @endif
    </div>
</div>
