<?php

namespace App\Http\Controllers\Admin\Jobs;

use App\Http\Controllers\Controller;
use App\Models\ParticipantOutcome;
use Illuminate\Http\Request;

class OutcomeAdminController extends Controller
{
    public function index(Request $request)
    {
        $query = ParticipantOutcome::with('user')->latest();

        if ($search = trim((string) $request->get('search'))) {
            $query->where(function ($q) use ($search) {
                $q->where('organisation_name','like',"%{$search}%")
                  ->orWhere('outcome_type','like',"%{$search}%")
                  ->orWhereHas('user', fn ($u) => $u
                      ->where('name','like',"%{$search}%")
                      ->orWhere('email','like',"%{$search}%"));
            });
        }

        if ($status = $request->get('verification_status')) {
            $query->where('verification_status',$status);
        }

        $stats = [
            'total' => ParticipantOutcome::count(),
            'submitted' => ParticipantOutcome::where('verification_status','submitted')->count(),
            'verified' => ParticipantOutcome::where('verification_status','verified')->count(),
            'rejected' => ParticipantOutcome::where('verification_status','rejected')->count(),
        ];

        $perPage = in_array((int)$request->get('per_page'), [10,25,50,100], true)
            ? (int)$request->get('per_page')
            : 25;

        return view('admin.jobs.outcomes', [
            'outcomes'=>$query->paginate($perPage)->withQueryString(),
            'stats'=>$stats,
        ]);
    }

    public function verify(ParticipantOutcome $outcome)
    {
        $outcome->update([
            'verification_status'=>'verified',
            'verified_by'=>auth()->id(),
            'verified_at'=>now(),
        ]);

        return back()->with('success','Outcome verified.');
    }

    public function reject(ParticipantOutcome $outcome)
    {
        $outcome->update(['verification_status'=>'rejected']);

        return back()->with('success','Outcome rejected.');
    }
}
