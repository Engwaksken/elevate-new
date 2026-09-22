<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\GlobalSearchService;
use Illuminate\Http\Request;

class GlobalSearchController extends Controller
{
    public function __invoke(Request $request, GlobalSearchService $service)
    {
        $term=(string)$request->get('q','');

        return view('search.results',[
            'term'=>$term,
            'results'=>$service->search($term),
        ]);
    }
}
