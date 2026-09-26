{{-- Route-aware extension: only renders routes that exist. --}}
<div class="eh-sidebar-extension" data-sidebar-extension="true">
    <div class="eh-sidebar-extension-title">More Management</div>

    @foreach([
        ['admin.events.index','Events','fa-calendar-days'],
        ['admin.events.calendar','Events Calendar','fa-calendar'],
        ['admin.attendance-analytics.index','Attendance Analytics','fa-chart-column'],
        ['admin.events.meal-report','Event MEAL Report','fa-chart-line'],
        ['admin.elearning.assignments.index','Course Assignments','fa-user-tie'],
        ['admin.elearning.enrolments.index','Enrolments','fa-user-graduate'],
        ['admin.elearning.learning-files.index','Learning Files','fa-folder-open'],
        ['admin.elearning.bulk-enrolment.create','Bulk Enrolment','fa-file-import'],
        ['admin.hr.kpi-templates.index','KPI Templates','fa-file-excel'],
        ['admin.hr.appraisals.index','Appraisals','fa-clipboard-check'],
    ] as [$route,$label,$icon])
        @if(Route::has($route))
            <a href="{{ route($route) }}"
               class="eh-sidebar-extension-link {{ request()->routeIs($route) ? 'active' : '' }}">
                <i class="fas {{ $icon }}"></i>
                <span>{{ $label }}</span>
            </a>
        @endif
    @endforeach
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const extension = document.querySelector('[data-sidebar-extension="true"]');
    if (!extension) return;

    const sidebar =
        document.querySelector('aside') ||
        document.querySelector('nav[class*="sidebar"]') ||
        document.querySelector('[class*="admin-sidebar"]') ||
        document.querySelector('[class*="sidebar"]') ||
        document.querySelector('#sidebar');

    if (sidebar && !sidebar.contains(extension)) {
        sidebar.appendChild(extension);
    }

    const scope = sidebar || document;
    const seen = new Set();

    scope.querySelectorAll('a[href]').forEach(function (link) {
        const href = (link.getAttribute('href') || '').replace(/\/+$/, '');
        if (!href || href === '#' || href.startsWith('javascript:')) return;

        if (seen.has(href) && link.closest('[data-sidebar-extension="true"]')) {
            link.remove();
            return;
        }

        seen.add(href);
    });

    if (!extension.querySelector('a[href]')) {
        extension.remove();
    }
});
</script>
