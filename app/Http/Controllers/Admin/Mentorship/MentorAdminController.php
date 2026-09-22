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

        if ($status = $request->get('status')) {
            $query->where('status',$status);
        }

        return view('admin.mentorship.mentors.index', [
            'mentors'=>$query->paginate(20)->withQueryString(),
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
