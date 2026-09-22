<?php

namespace App\Http\Controllers\Admin\Jobs;

use App\Http\Controllers\Controller;
use App\Models\Employer;

class EmployerAdminController extends Controller
{
    public function index()
    {
        return view('admin.jobs.employers', [
            'employers'=>Employer::with('owner')->latest()->paginate(20),
        ]);
    }

    public function approve(Employer $employer)
    {
        $employer->update([
            'status'=>'approved',
            'approved_at'=>now(),
            'approved_by'=>auth()->id(),
        ]);

        return back()->with('success','Employer approved.');
    }

    public function reject(Employer $employer)
    {
        $employer->update(['status'=>'rejected']);
        return back()->with('success','Employer rejected.');
    }
}
