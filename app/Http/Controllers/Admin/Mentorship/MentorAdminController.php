<?php

namespace App\Http\Controllers\Admin\Mentorship;

use App\Http\Controllers\Controller;
use App\Models\MentorProfile;
use Illuminate\Http\Request;

class MentorAdminController extends Controller
{
    public function index(Request $request)
    {
        $query = MentorProfile::with('user')->latest();

        if ($search = trim((string) $request->get('search'))) {
            $query->where(function ($q) use ($search) {
                $q->where('organisation','like',"%{$search}%")
                  ->orWhere('job_title','like',"%{$search}%")
                  ->orWhereHas('user', fn ($u) => $u
                      ->where('name','like',"%{$search}%")
                      ->orWhere('email','like',"%{$search}%"));
            });
        }

        if ($status = $request->get('status')) $query->where('status',$status);

        $stats = [
            'total' => MentorProfile::count(),
            'pending' => MentorProfile::where('status','pending')->count(),
            'approved' => MentorProfile::where('status','approved')->count(),
            'rejected' => MentorProfile::where('status','rejected')->count(),
        ];

        $perPage = in_array((int)$request->get('per_page'), [10,20,25,50,100], true)
            ? (int)$request->get('per_page') : 20;

        return view('admin.mentorship.mentors.index', [
            'mentors' => $query->paginate($perPage)->withQueryString(),
            'stats' => $stats,
        ]);
    }

    public function approve(MentorProfile $mentor)
    {
        $mentor->update([
            'status'=>'approved',
            'approved_at'=>now(),
            'approved_by'=>auth()->id(),
        ]);

        return back()->with('success','Mentor approved.');
    }

    public function reject(MentorProfile $mentor)
    {
        $mentor->update(['status'=>'rejected']);
        return back()->with('success','Mentor rejected.');
    }
}
