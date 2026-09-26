<?php

namespace App\Http\Controllers\Admin\ME;

use App\Http\Controllers\Controller;
use App\Models\Programme;
use App\Models\Project;
use App\Models\Result;
use App\Models\ResultsFramework;
use Illuminate\Http\Request;

class ResultsFrameworkController extends Controller
{
    public function index(Request $request)
    {
        $query = ResultsFramework::with('results')->latest();

        if ($search = trim((string)$request->get('search'))) {
            $query->where(function ($q) use ($search) {
                $q->where('title','like',"%{$search}%")
                  ->orWhere('description','like',"%{$search}%");
            });
        }

        $perPage = in_array((int)$request->get('per_page'),[10,20,25,50,100],true)
            ? (int)$request->get('per_page') : 20;

        return view('admin.results-framework.index',[
            'frameworks'=>$query->paginate($perPage)->withQueryString(),
            'programmes'=>Programme::orderBy('name')->get(),
            'projects'=>Project::orderBy('name')->get(),
            'stats'=>[
                'frameworks'=>ResultsFramework::count(),
                'impacts'=>Result::where('result_level','impact')->count(),
                'outcomes'=>Result::where('result_level','outcome')->count(),
                'outputs'=>Result::where('result_level','output')->count(),
            ],
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
