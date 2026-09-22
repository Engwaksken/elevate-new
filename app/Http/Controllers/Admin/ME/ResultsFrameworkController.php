<?php
namespace App\Http\Controllers\Admin\ME;

use App\Http\Controllers\Controller;
use App\Models\ResultsFramework;
use App\Models\Result;
use Illuminate\Http\Request;

class ResultsFrameworkController extends Controller
{
    public function index()
    {
        return view('admin.results-framework.index',[
            'frameworks'=>ResultsFramework::with('results')->latest()->paginate(20)
        ]);
    }

    public function store(Request $request)
    {
        ResultsFramework::create($request->validate([
            'programme_id'=>['nullable','exists:programmes,id'],
            'project_id'=>['nullable','exists:projects,id'],
            'title'=>['required','string','max:190'],
            'description'=>['nullable','string'],
        ]));
        return back()->with('success','Results framework created.');
    }

    public function addResult(Request $request, ResultsFramework $framework)
    {
        $framework->results()->create($request->validate([
            'parent_id'=>['nullable','exists:results,id'],
            'result_level'=>['required','in:impact,outcome,output'],
            'title'=>['required','string','max:190'],
            'description'=>['nullable','string'],
        ]));
        return back()->with('success','Result added.');
    }
}
