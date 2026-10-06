<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\LearningReportRequest;
use App\Models\Enrolment;
use App\Models\Course;
use App\Models\Programme;
use App\Models\Branch;
use App\Support\Export\TableExport;
use App\Support\Export\TableExporter;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LearningReportController extends Controller
{
    public function index(LearningReportRequest $request)
    {
        $filters = $request->validated();

        return view('admin.learning-reports.index', [
            'rows' => $this->rows($request),
            'filters' => $filters,
            'courses' => Course::query()->orderBy('title')->get(['id', 'title']),
            'programmes' => Programme::query()->orderBy('name')->get(['id', 'name']),
            'branches' => Branch::query()->orderBy('name')->get(['id', 'name']),
            'csvUrl' => route('admin.learning-reports.csv', $filters),
            'pdfUrl' => route('admin.learning-reports.pdf', $filters),
        ]);
    }

    public function csv(LearningReportRequest $request): StreamedResponse
    {
        $rows = $this->rows($request);

        return response()->streamDownload(function () use ($rows): void {
            $output = fopen('php://output', 'w');
            fputcsv($output, ['Course', 'Programme ID', 'Branch ID', 'Enrolments', 'Completed', 'In progress', 'Completion rate (%)', 'Average progress (%)']);
            foreach ($rows as $row) {
                fputcsv($output, array_values($row));
            }
            fclose($output);
        }, 'learning-report-' . now()->format('Ymd_His') . '.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function pdf(LearningReportRequest $request)
    {
        $columns = [];
        foreach ([
            'course' => 'Course',
            'programme_id' => 'Programme ID',
            'branch_id' => 'Branch ID',
            'enrolments' => 'Enrolments',
            'completed' => 'Completed',
            'in_progress' => 'In progress',
            'completion_rate_percent' => 'Completion rate (%)',
            'average_progress_percent' => 'Average progress (%)',
        ] as $key => $label) {
            $columns[$label] = $key;
        }

        // Rendered through the shared branded export template.
        return app(TableExporter::class)->pdf(
            TableExport::make('Learning report')
                ->columns($columns)
                ->source($this->rows($request))
                ->filters([
                    'From' => $request->validated('from'),
                    'To' => $request->validated('to'),
                    'Course' => $request->filled('course_id') ? Course::find($request->validated('course_id'))?->title : null,
                    'Programme' => $request->filled('programme_id') ? Programme::find($request->validated('programme_id'))?->name : null,
                    'Branch' => $request->filled('branch_id') ? Branch::find($request->validated('branch_id'))?->name : null,
                ])
        );
    }

    private function rows(LearningReportRequest $request): array
    {
        $query = Enrolment::query()->join('courses', 'courses.id', '=', 'enrolments.course_id')
            ->whereNotIn('enrolments.status', ['cancelled', 'withdrawn'])
            ->selectRaw("courses.title as course, courses.programme_id, courses.branch_id, COUNT(enrolments.id) as enrolments, SUM(CASE WHEN enrolments.status = 'completed' THEN 1 ELSE 0 END) as completed, SUM(CASE WHEN enrolments.status IN ('enrolled', 'active', 'in_progress') THEN 1 ELSE 0 END) as in_progress, AVG(COALESCE(enrolments.progress_percent, 0)) as average_progress")
            ->groupBy('courses.id', 'courses.title', 'courses.programme_id', 'courses.branch_id')
            ->orderBy('courses.title');

        if ($request->filled('from')) {
            $query->whereDate('enrolments.enrolled_at', '>=', $request->validated('from'));
        }
        if ($request->filled('to')) {
            $query->whereDate('enrolments.enrolled_at', '<=', $request->validated('to'));
        }
        if ($request->filled('course_id')) {
            $query->where('courses.id', $request->validated('course_id'));
        }
        if ($request->filled('programme_id')) {
            $query->where('courses.programme_id', $request->validated('programme_id'));
        }
        if ($request->filled('branch_id')) {
            $query->where('courses.branch_id', $request->validated('branch_id'));
        }

        return $query->get()->map(fn ($row) => [
            'course' => $row->course,
            'programme_id' => $row->programme_id,
            'branch_id' => $row->branch_id,
            'enrolments' => (int) $row->enrolments,
            'completed' => (int) $row->completed,
            'in_progress' => (int) $row->in_progress,
            'completion_rate_percent' => $row->enrolments ? round($row->completed * 100 / $row->enrolments, 2) : 0,
            'average_progress_percent' => round((float) $row->average_progress, 2),
        ])->all();
    }
}
