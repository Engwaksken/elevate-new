<?php
namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Job;

class JobController extends Controller
{
    public function index()
    {
        return response()->json([
            'data'=>Job::with('employer:id,company_name')
                ->where('status','published')
                ->latest('published_at')
                ->paginate(20)
        ]);
    }
}
