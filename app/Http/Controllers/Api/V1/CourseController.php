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
                ->select('id','title','code','summary','delivery_mode','start_date','end_date')
                ->latest()
                ->paginate(20)
        ]);
    }

    public function show(Course $course)
    {
        abort_unless($course->status==='published',404);

        return response()->json([
            'data'=>$course->load([
                'modules'=>fn($q)=>$q->where('is_published',true)->orderBy('position'),
                'modules.lessons'=>fn($q)=>$q->where('is_published',true)->orderBy('position'),
            ])
        ]);
    }
}
