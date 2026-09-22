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

        if ($status = $request->get('verification_status')) {
            $query->where('verification_status',$status);
        }

        return view('admin.jobs.outcomes', [
            'outcomes'=>$query->paginate(25)->withQueryString(),
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
