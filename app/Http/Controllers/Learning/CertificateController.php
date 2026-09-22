<?php

namespace App\Http\Controllers\Learning;

use App\Http\Controllers\Controller;
use App\Models\Certificate;

class CertificateController extends Controller
{
    public function verify(string $token)
    {
        $certificate = Certificate::with(['user','course'])
            ->where('verification_token', $token)
            ->first();

        return view('certificates.verify', compact('certificate'));
    }
}
