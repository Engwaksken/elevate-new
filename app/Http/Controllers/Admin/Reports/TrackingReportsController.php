<?php

namespace App\Http\Controllers\Admin\Reports;

use App\Http\Controllers\Concerns\ExportsTables;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class TrackingReportsController extends Controller
{
    use ExportsTables;

    public function mentorship(Request $request)
    {
        $stats = [];

        foreach (['mentors' => 'mentor_profiles', 'matches' => 'mentor_matches', 'sessions' => 'mentorship_sessions', 'goals' => 'mentorship_goals'] as $key => $table) {
            $stats[$key] = Schema::hasTable($table) ? DB::table($table)->count() : 0;
        }

        $breakdowns = [
            'mentors' => $this->columnBreakdown('mentor_profiles', 'status'),
            'matches' => $this->columnBreakdown('mentor_matches', 'status'),
            'sessions' => $this->columnBreakdown('mentorship_sessions', 'status'),
            'goals' => $this->columnBreakdown('mentorship_goals', 'status'),
        ];

        if ($format = $this->exportFormat($request)) {
            $rows = collect();
            foreach ($breakdowns as $area => $counts) {
                $rows->push(['area' => ucfirst($area), 'status' => 'All', 'total' => $stats[$area] ?? 0]);
                foreach ($counts as $status => $total) {
                    $rows->push(['area' => ucfirst($area), 'status' => ucwords(str_replace('_', ' ', (string) ($status ?: 'Unspecified'))), 'total' => $total]);
                }
            }

            return $this->exportTable($format, 'Mentorship Tracking', $rows, [
                'Area' => 'area',
                'Status' => 'status',
                'Count' => 'total',
            ]);
        }

        return view('admin.reports.mentorship', compact('stats', 'breakdowns'));
    }

    /**
     * Count of each distinct value in a column (for pie/bar breakdowns),
     * returning an empty collection when the table/column is unavailable.
     */
    private function columnBreakdown(string $table, string $column): \Illuminate\Support\Collection
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
            return collect();
        }

        return DB::table($table)
            ->select($column, DB::raw('COUNT(*) as total'))
            ->groupBy($column)
            ->orderByDesc('total')
            ->pluck('total', $column);
    }

    public function jobs(Request $request)
    {
        $events = Schema::hasTable('job_tracking_events')
            ? DB::table('job_tracking_events')->latest()->limit(200)->get()
            : collect();

        $funnel = $events->groupBy('event_type')->map->count()->sortDesc();

        $userNames = DB::table('users')->whereIn('id', $events->pluck('user_id')->filter()->unique())->pluck('name', 'id');
        $jobTitles = Schema::hasTable('jobs') && Schema::hasColumn('jobs', 'title')
            ? DB::table('jobs')->whereIn('id', $events->pluck('job_id')->filter()->unique())->pluck('title', 'id')
            : collect();

        if ($format = $this->exportFormat($request)) {
            return $this->exportTable($format, 'Jobs Tracking', $events, [
                'Participant' => fn ($e) => $userNames[$e->user_id] ?? ($e->user_id ? 'User #'.$e->user_id : ''),
                'Job' => fn ($e) => $e->job_id ? ($jobTitles[$e->job_id] ?? 'Job #'.$e->job_id) : '',
                'Event' => fn ($e) => ucwords(str_replace('_', ' ', (string) $e->event_type)),
                'Employer' => 'employer',
                'Position' => 'position',
                'Date' => fn ($e) => $e->event_date ? \Illuminate\Support\Carbon::parse($e->event_date)->format('Y-m-d') : '',
            ], null, [], fn ($x) => $x->subtitle('Latest 200 job tracking events'));
        }

        return view('admin.reports.jobs', compact('events', 'funnel', 'userNames', 'jobTitles'));
    }
}
