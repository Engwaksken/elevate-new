<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CourseCall;

class CourseCallDisplayController extends Controller
{
    public function qr(CourseCall $courseCall)
    {
        abort_unless(
            auth()->user()?->isSuperAdmin()
            || auth()->user()?->hasPermission('course_calls.view'),
            403
        );

        $url = route('participant.course-calls.show', $courseCall);

        if (! class_exists(\SimpleSoftwareIO\QrCode\Facades\QrCode::class)) {
            abort(503, 'QR renderer is not installed.');
        }

        return response(
            \SimpleSoftwareIO\QrCode\Facades\QrCode::format('svg')
                ->size(320)
                ->margin(1)
                ->generate($url),
            200,
            ['Content-Type' => 'image/svg+xml']
        );
    }
}
