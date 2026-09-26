{{-- Hidden source only: generated links are inserted into existing sidebar groups --}}
<template id="eh-sidebar-grouped-links-template" data-sidebar-grouped-links-template>
    @foreach([
        ['admin.events.index','Events','fa-calendar-days','programme_delivery'],
        ['admin.events.calendar','Events Calendar','fa-calendar','programme_delivery'],
        ['admin.elearning.assignments.index','Course Assignments','fa-user-tie','programme_delivery'],
        ['admin.elearning.enrolments.index','Enrolments','fa-user-graduate','programme_delivery'],
        ['admin.elearning.learning-files.index','Learning Files','fa-folder-open','programme_delivery'],
        ['admin.elearning.bulk-enrolment.create','Bulk Enrolment','fa-file-import','programme_delivery'],

        ['admin.course-attendance-report.index','Course Attendance','fa-clipboard-user','planning_meal'],
        ['admin.participant-attendance-summary.index','Participant Attendance','fa-chart-line','planning_meal'],
        ['admin.attendance-analytics.index','Attendance Analytics','fa-chart-column','planning_meal'],
        ['admin.events.meal-report','Event MEAL Report','fa-chart-simple','planning_meal'],

        ['admin.hr.kpi-templates.index','KPI Templates','fa-file-excel','hr'],
        ['admin.hr.appraisals.index','Appraisals','fa-clipboard-check','hr'],

        ['admin.executive-dashboard','Executive Dashboard','fa-chart-pie','overview'],
        ['admin.settings.index','System Settings','fa-gears','operations'],
    ] as [$route,$label,$icon,$group])
        @if(Route::has($route))
            <a href="{{ route($route) }}"
               data-eh-sidebar-group="{{ $group }}"
               data-eh-generated-grouped-link="true"
               class="eh-grouped-sidebar-link {{ request()->routeIs($route) ? 'active' : '' }}">
                <i class="fas {{ $icon }}"></i>
                <span>{{ $label }}</span>
            </a>
        @endif
    @endforeach
</template>
