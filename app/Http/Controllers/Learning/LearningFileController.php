<?php

namespace App\Http\Controllers\Learning;

use App\Http\Controllers\Controller;
use App\Models\Enrolment;
use App\Models\LearningFile;
use App\Services\Files\FilePreviewService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class LearningFileController extends Controller
{
    public function download(Request $request, LearningFile $file, FilePreviewService $previews)
    {
        if ($file->course_id) {
            $allowed = Enrolment::where('course_id',$file->course_id)
                ->where('user_id',auth()->id())
                ->exists();

            abort_unless($allowed || auth()->user()->isStaff(),403);
        }

        abort_unless(Storage::disk($file->disk)->exists($file->path),404);

        if ($previews->wantsPreview($request)) {
            return $previews->respond($request,$file->disk,$file->path,$file->original_name);
        }

        return Storage::disk($file->disk)->download($file->path,$file->original_name);
    }
}
