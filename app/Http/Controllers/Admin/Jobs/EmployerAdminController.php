<?php

namespace App\Http\Controllers\Admin\Jobs;

use App\Http\Controllers\Controller;
use App\Models\Employer;
use Illuminate\Http\Request;

class EmployerAdminController extends Controller
{
    public function create()
    {
        return view('admin.partners.form', ['type' => 'employer', 'profile' => new Employer(), 'users' => \App\Models\User::where('user_type', 'participant')->orderBy('name')->get()]);
    }

    public function edit(Employer $employer)
    {
        return view('admin.partners.form', ['type' => 'employer', 'profile' => $employer->load('owner'), 'users' => collect()]);
    }

    public function store(Request $request, \App\Services\PartnerDetailsService $service)
    {
        $service->save($request, 'employer');
        return redirect()->route('admin.jobs.employers.index')->with('success', 'Employer details added.');
    }

    public function update(Request $request, Employer $employer, \App\Services\PartnerDetailsService $service)
    {
        $service->save($request, 'employer', $employer);
        return redirect()->route('admin.jobs.employers.index')->with('success', 'Employer details updated.');
    }
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
        if ($employer->owner?->status === 'pending') $employer->owner->update(['status' => 'active']);
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
