<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\GlobalSearchService;
use Illuminate\Http\Request;

class GlobalSearchController extends Controller
{
    public function __invoke(Request $request, GlobalSearchService $service)
    {
        $term=trim((string)$request->get('q',''));
        $category=(string)$request->get('category','all');

        return view('search.results',[
            'term'=>$term,
            'category'=>$category,
            'results'=>$service->search($term,$category),
        ]);
    }
}
