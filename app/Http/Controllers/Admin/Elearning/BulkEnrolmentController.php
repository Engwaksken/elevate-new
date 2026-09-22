<?php

namespace App\Http\Controllers\Admin\Elearning;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Enrolment;
use App\Models\User;
use Illuminate\Http\Request;

class BulkEnrolmentController extends Controller
{
    public function create()
    {
        return view('admin.elearning.bulk-enrolment.create', [
            'courses'=>Course::orderBy('title')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'course_id'=>['required','exists:courses,id'],
            'cohort_id'=>['nullable','exists:cohorts,id'],
            'file'=>['required','file','mimes:csv,txt','max:10240'],
        ]);

        $handle = fopen($request->file('file')->getRealPath(),'r');
        $headers = array_map('trim', fgetcsv($handle) ?: []);
        $count = 0;

        while (($row = fgetcsv($handle)) !== false) {
            if (count($headers) !== count($row)) continue;
            $record = array_combine($headers,$row);

            $email = strtolower(trim((string)($record['email'] ?? '')));
            if (!$email) continue;

            $user = User::whereRaw('LOWER(email)=?',[$email])->first();
            if (!$user) continue;

            Enrolment::firstOrCreate(
                ['course_id'=>$data['course_id'],'user_id'=>$user->id],
                [
                    'cohort_id'=>$data['cohort_id'] ?? null,
                    'status'=>'enrolled',
                    'enrolled_at'=>now(),
                ]
            );
            $count++;
        }

        fclose($handle);

        return back()->with('success',"{$count} learners enrolled.");
    }
}
