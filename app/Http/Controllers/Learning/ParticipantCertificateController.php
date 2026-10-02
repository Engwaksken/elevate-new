<?php

namespace App\Http\Controllers\Learning;

use App\Http\Controllers\Controller;
use App\Services\ParticipantCertificateService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class ParticipantCertificateController extends Controller
{
    public function index(Request $request, ParticipantCertificateService $service)
    {
        $request->validate(['course_id'=>['nullable','integer','exists:courses,id']]);
        $items = $service->list($request->user(), $request->integer('course_id') ?: null);
        $page = LengthAwarePaginator::resolveCurrentPage();
        return response()->json(new LengthAwarePaginator($items->forPage($page, 20)->values(), $items->count(), 20, $page, ['path'=>$request->url(),'query'=>$request->query()]));
    }

    public function preview(Request $request, string $type, int $id, ParticipantCertificateService $service)
    {
        return $service->response($this->own($request, $type, $id, $service), true);
    }

    public function download(Request $request, string $type, int $id, ParticipantCertificateService $service)
    {
        return $service->response($this->own($request, $type, $id, $service));
    }

    public function share(Request $request, string $type, int $id, ParticipantCertificateService $service)
    {
        $this->own($request, $type, $id, $service);
        return response()->json($service->share($type, $id));
    }

    public function shared(string $type, int $id, ParticipantCertificateService $service)
    {
        return $service->response($service->find($type, $id), true);
    }

    private function own(Request $request, string $type, int $id, ParticipantCertificateService $service)
    {
        $certificate = $service->find($type, $id);
        abort_unless($certificate->user_id === $request->user()->id, 403);
        return $certificate;
    }
}
