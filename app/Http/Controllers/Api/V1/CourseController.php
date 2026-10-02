<?php
namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Course;

class CourseController extends Controller
{
    public function index()
    {
        return response()->json([
            'data'=>Course::where('status','published')
                ->with('branches')
                ->select('id','title','code','summary','description','thumbnail_path','delivery_mode','start_date','end_date')
                ->latest()
                ->paginate(20)->through(fn ($course) => $course->publicData())
        ]);
    }

    public function show(Course $course)
    {
        abort_unless($course->status==='published',404);

        return response()->json([
            'data'=>$course->load('branches')->publicData()
        ]);
    }
}
