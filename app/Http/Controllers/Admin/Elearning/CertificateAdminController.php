<?php

namespace App\Http\Controllers\Admin\Elearning;

use App\Http\Controllers\Controller;
use App\Models\Certificate;
use App\Services\CertificatePdfService;

class CertificateAdminController extends Controller
{
    public function generate(Certificate $certificate, CertificatePdfService $service)
    {
        $service->render($certificate);
        return back()->with('success','Certificate PDF generated.');
    }
}
