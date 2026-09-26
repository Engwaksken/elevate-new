<?php

namespace App\Http\Controllers\Admin\Elearning;

use App\Http\Controllers\Controller;
use App\Models\Certificate;
use App\Services\CertificatePdfService;
use Illuminate\Http\Request;

class CertificateAdminController extends Controller
{
    public function index(Request $request)
    {
        $query = Certificate::with(['course','user'])->latest();

        if ($search = trim((string) $request->get('search'))) {
            $query->where(function ($q) use ($search) {
                $q->where('certificate_number','like',"%{$search}%")
                    ->orWhereHas('user', fn ($u) => $u
                        ->where('name','like',"%{$search}%")
                        ->orWhere('email','like',"%{$search}%"))
                    ->orWhereHas('course', fn ($c) =>
                        $c->where('title','like',"%{$search}%")
                    );
            });
        }

        if ($request->filled('pdf_status')) {
            if ($request->get('pdf_status') === 'generated') {
                $query->whereNotNull('pdf_path');
            } elseif ($request->get('pdf_status') === 'pending') {
                $query->whereNull('pdf_path');
            }
        }

        $stats = [
            'total' => Certificate::count(),
            'generated' => Certificate::whereNotNull('pdf_path')->count(),
            'pending' => Certificate::whereNull('pdf_path')->count(),
            'issued_this_month' => Certificate::whereBetween('issued_on', [
                now()->startOfMonth()->toDateString(),
                now()->endOfMonth()->toDateString(),
            ])->count(),
        ];

        $perPage = in_array((int)$request->get('per_page'), [10,20,25,50,100], true)
            ? (int)$request->get('per_page')
            : 20;

        return view('admin.elearning.certificates.index', [
            'certificates' => $query->paginate($perPage)->withQueryString(),
            'stats' => $stats,
        ]);
    }

    public function generate(
        Certificate $certificate,
        CertificatePdfService $service
    ) {
        $service->render($certificate);

        return back()->with(
            'success',
            'Certificate PDF generated.'
        );
    }
}
