<?php

namespace App\Http\Controllers\Admin\Jobs;

use App\Http\Controllers\Controller;
use App\Models\Employer;
use Illuminate\Http\Request;

class EmployerAdminController extends Controller
{
    public function index(Request $request)
    {
        $query = Employer::with('owner')->latest();

        if ($search = trim((string) $request->get('search'))) {
            $query->where(function ($q) use ($search) {
                $q->where('company_name','like',"%{$search}%")
                  ->orWhereHas('owner', fn ($u) => $u
                      ->where('name','like',"%{$search}%")
                      ->orWhere('email','like',"%{$search}%"));
            });
        }

        if ($status = $request->get('status')) {
            $query->where('status',$status);
        }

        $stats = [
            'total' => Employer::count(),
            'pending' => Employer::where('status','pending')->count(),
            'approved' => Employer::where('status','approved')->count(),
            'rejected' => Employer::where('status','rejected')->count(),
        ];

        $perPage = in_array((int)$request->get('per_page'), [10,20,25,50,100], true)
            ? (int)$request->get('per_page')
            : 20;

        return view('admin.jobs.employers', [
            'employers' => $query->paginate($perPage)->withQueryString(),
            'stats' => $stats,
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
