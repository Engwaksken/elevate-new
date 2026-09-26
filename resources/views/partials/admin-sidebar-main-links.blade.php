{{-- Hidden route-aware source template. Nothing visible is rendered here. --}}
<template id="eh-sidebar-links-template" data-sidebar-links-template>
    @foreach([
        ['admin.events.index','Events','fa-calendar-days'],
        ['admin.events.calendar','Events Calendar','fa-calendar'],
        ['admin.course-attendance-report.index','Course Attendance','fa-clipboard-user'],
        ['admin.participant-attendance-summary.index','Participant Attendance','fa-chart-line'],
        ['admin.attendance-analytics.index','Attendance Analytics','fa-chart-column'],
        ['admin.events.meal-report','Event MEAL Report','fa-chart-simple'],
        ['admin.elearning.assignments.index','Course Assignments','fa-user-tie'],
        ['admin.elearning.enrolments.index','Enrolments','fa-user-graduate'],
        ['admin.elearning.learning-files.index','Learning Files','fa-folder-open'],
        ['admin.elearning.bulk-enrolment.create','Bulk Enrolment','fa-file-import'],
        ['admin.hr.kpi-templates.index','KPI Templates','fa-file-excel'],
        ['admin.hr.appraisals.index','Appraisals','fa-clipboard-check'],
        ['admin.executive-dashboard','Executive Dashboard','fa-chart-pie'],
        ['admin.settings.index','System Settings','fa-gears'],
    ] as [$route,$label,$icon])
        @if(Route::has($route))
            <a href="{{ route($route) }}"
               data-eh-generated-sidebar-link="true"
               class="eh-native-sidebar-link {{ request()->routeIs($route) ? 'active' : '' }}">
                <i class="fas {{ $icon }}"></i>
                <span>{{ $label }}</span>
            </a>
        @endif
    @endforeach
</template>
