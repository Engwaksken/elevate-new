<?php

namespace App\Http\Controllers\Career;

use App\Http\Controllers\Controller;
use App\Models\CoverLetter;
use App\Models\Resume;
use App\Services\CareerDocumentService;
use Illuminate\Http\Request;

class DocumentShareController extends Controller
{
    public function share(Request $request, string $type, int $id, CareerDocumentService $service)
    {
        $document = $this->document($type, $id);
        abort_unless($document->user_id === $request->user()->id, 403);

        return response()->json($service->share($document));
    }

    public function shared(string $type, int $id, CareerDocumentService $service)
    {
        return $service->download($this->document($type, $id));
    }

    private function document(string $type, int $id): Resume|CoverLetter
    {
        abort_unless(in_array($type, ['resume', 'cover-letter'], true), 404);

        return $type === 'resume' ? Resume::findOrFail($id) : CoverLetter::findOrFail($id);
    }
}
