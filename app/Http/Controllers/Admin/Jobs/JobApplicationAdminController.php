<?php

namespace App\Http\Controllers\Admin\Jobs;

use App\Http\Controllers\Controller;
use App\Models\JobApplication;
use Illuminate\Http\Request;

class JobApplicationAdminController extends Controller
{
    public function index(Request $request)
    {
        $query = JobApplication::query()->with(['job.employer', 'user', 'statusHistory']);

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        if ($search = trim((string) $request->get('search'))) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('user', fn ($u) => $u->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"))
                    ->orWhereHas('job', fn ($j) => $j->where('title', 'like', "%{$search}%"));
            });
        }

        $statuses = ['submitted', 'under_review', 'shortlisted', 'interview', 'offer', 'hired', 'rejected', 'withdrawn'];

        return view('admin.jobs.applications', [
            'applications' => $query->latest('applied_at')->paginate(20)->withQueryString(),
            'statuses' => $statuses,
            'stats' => collect($statuses)->mapWithKeys(fn ($s) => [$s => JobApplication::where('status', $s)->count()])->all(),
        ]);
    }
}
