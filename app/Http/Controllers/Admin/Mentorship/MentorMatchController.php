<?php

namespace App\Http\Controllers\Admin\Mentorship;

use App\Http\Controllers\Controller;
use App\Models\MentorMatch;
use App\Models\MentorProfile;
use App\Models\MenteeProfile;
use Illuminate\Http\Request;

class MentorMatchController extends Controller
{
    public function index(Request $request)
    {
        $query = MentorMatch::with(['mentor','mentee'])->latest();

        if ($status = $request->get('status')) $query->where('status',$status);

        $stats = [
            'total' => MentorMatch::count(),
            'active' => MentorMatch::where('status','active')->count(),
            'completed' => MentorMatch::where('status','completed')->count(),
            'inactive' => MentorMatch::whereNotIn('status',['active','completed'])->count(),
        ];

        $perPage = in_array((int)$request->get('per_page'), [10,20,25,50,100], true)
            ? (int)$request->get('per_page') : 20;

        return view('admin.mentorship.matches.index', [
            'matches'=>$query->paginate($perPage)->withQueryString(),
            'mentors'=>MentorProfile::with('user')->where('status','approved')->get(),
            'mentees'=>MenteeProfile::with('user')->get(),
            'stats'=>$stats,
        ]);
    }

    public function store(Request $request)
    {
        MentorMatch::create($this->validated($request) + [
            'status'=>'active',
            'matched_by'=>auth()->id(),
        ]);

        return back()->with('success','Mentor matched successfully.');
    }

    public function update(Request $request, MentorMatch $match)
    {
        $match->update($this->validated($request) + [
            'status'=>$request->input('status','active'),
        ]);

        return back()->with('success','Mentor match updated.');
    }

    public function destroy(MentorMatch $match)
    {
        $match->delete();
        return back()->with('success','Mentor match deleted.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'mentor_user_id'=>['required','exists:users,id'],
            'mentee_user_id'=>['required','different:mentor_user_id','exists:users,id'],
            'programme_id'=>['nullable','exists:programmes,id'],
            'cohort_id'=>['nullable','exists:cohorts,id'],
            'start_date'=>['nullable','date'],
            'end_date'=>['nullable','date','after_or_equal:start_date'],
            'status'=>['nullable','in:active,completed,inactive,cancelled'],
        ]);
    }
}
