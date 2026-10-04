<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Employer;
use App\Models\MentorProfile;
use App\Services\CmsContentService;
use App\Services\PartnerDetailsService;
use Illuminate\Http\Request;

class PartnerSignupController extends Controller
{
    public function show(string $type, CmsContentService $cms)
    {
        abort_unless(in_array($type, ['mentor', 'employer'], true), 404);
        return view('auth.partner-register', [
            'type' => $type, 'profile' => $type === 'mentor' ? new MentorProfile() : new Employer(),
            'content' => $cms->published($type.'-signup'),
        ]);
    }

    public function store(Request $request, string $type, CmsContentService $cms, PartnerDetailsService $service)
    {
        abort_unless(in_array($type, ['mentor', 'employer'], true), 404);
        abort_if(data_get($cms->published($type.'-signup'), 'settings.signup_open', true) === false, 403, 'Registrations are currently closed.');
        $service->save($request, $type, public: true);
        return redirect()->route('partners.'.$type.'.login')->with('status', 'Registration received. Verify your details and sign in once an administrator approves your account.');
    }
}
