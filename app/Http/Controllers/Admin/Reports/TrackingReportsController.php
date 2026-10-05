<?php

namespace App\Http\Controllers\Admin\Reports;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class TrackingReportsController extends Controller
{
    public function mentorship()
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

    public function jobs()
    {
        $events = Schema::hasTable('job_tracking_events')
            ? DB::table('job_tracking_events')->latest()->limit(200)->get()
            : collect();

        $funnel = $events->groupBy('event_type')->map->count()->sortDesc();

        $userNames = DB::table('users')->whereIn('id', $events->pluck('user_id')->filter()->unique())->pluck('name', 'id');
        $jobTitles = Schema::hasTable('jobs') && Schema::hasColumn('jobs', 'title')
            ? DB::table('jobs')->whereIn('id', $events->pluck('job_id')->filter()->unique())->pluck('title', 'id')
            : collect();

        return view('admin.reports.jobs', compact('events', 'funnel', 'userNames', 'jobTitles'));
    }
}
