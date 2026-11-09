<?php
namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\LibraryResource;

class LibraryController extends Controller
{
    public function index()
    {
        return response()->json([
            'data'=>LibraryResource::where('is_active',true)
                ->whereIn('access_level',['public','authenticated'])
                ->availableTo(request()->user())
                ->latest()
                ->paginate(20)
        ]);
    }
}
