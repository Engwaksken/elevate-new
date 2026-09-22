<?php

namespace App\Http\Controllers\Employer;

use App\Http\Controllers\Controller;
use App\Models\Employer;
use App\Models\JobApplication;
use Illuminate\Http\Request;

class OfferController extends Controller
{
    public function store(Request $request, JobApplication $application)
    {
        $employerId = Employer::where('owner_user_id',auth()->id())->value('id');
        abort_unless($application->job()->where('employer_id',$employerId)->exists(),403);

        $data = $request->validate([
            'salary_amount'=>['nullable','numeric','min:0'],
            'salary_currency'=>['nullable','string','size:3'],
            'start_date'=>['nullable','date'],
            'offer_notes'=>['nullable','string'],
        ]);

        $application->offers()->create($data + ['status'=>'sent']);
        $application->update(['status'=>'offer']);

        return back()->with('success','Offer sent.');
    }
}
