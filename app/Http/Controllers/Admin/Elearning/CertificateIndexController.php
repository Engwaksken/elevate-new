<?php

namespace App\Http\Controllers\Admin\Elearning;

use App\Http\Controllers\Controller;
use App\Models\Certificate;
use App\Models\Course;
use Illuminate\Http\Request;

class CertificateIndexController extends Controller
{
    public function index(Request $request)
    {
        $query = Certificate::query()
            ->with(['user','course']);

        if ($search = trim((string) $request->get('search'))) {
            $query->where(function ($q) use ($search) {
                $q->where('certificate_number', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($userQuery) use ($search) {
                        $userQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    })
                    ->orWhereHas('course', function ($courseQuery) use ($search) {
                        $courseQuery->where('title', 'like', "%{$search}%")
                            ->orWhere('code', 'like', "%{$search}%");
                    });
            });
        }

        if ($courseId = $request->integer('course_id')) {
            $query->where('course_id', $courseId);
        }

        if ($status = $request->get('status')) {
            if ($status === 'generated') {
                $query->whereNotNull('pdf_path');
            }

            if ($status === 'pending') {
                $query->whereNull('pdf_path');
            }
        }

        $certificates = $query
            ->latest('issued_on')
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('admin.elearning.certificates.index', [
            'certificates' => $certificates,
            'courses' => Course::query()
                ->orderBy('title')
                ->get(['id','title','code']),
            'stats' => [
                'total' => Certificate::count(),
                'generated' => Certificate::whereNotNull('pdf_path')->count(),
                'pending' => Certificate::whereNull('pdf_path')->count(),
                'courses' => Certificate::distinct('course_id')->count('course_id'),
            ],
        ]);
    }
}
