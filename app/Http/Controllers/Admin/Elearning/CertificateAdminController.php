<?php

namespace App\Http\Controllers\Admin\Elearning;

use App\Http\Controllers\Controller;
use App\Models\Certificate;
use App\Models\CertificateTemplate;
use App\Models\Course;
use App\Models\Event;
use App\Services\CertificatePdfService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CertificateAdminController extends Controller
{
    public function index(Request $request)
    {
        $query = Certificate::with(['course','event','user','template'])->latest();

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

        return view('admin.elearning.certificates.index', [
            'certificates' => $query->paginate(20)->withQueryString(),
            'stats' => [
                'total' => Certificate::count(),
                'generated' => Certificate::whereNotNull('pdf_path')->count(),
                'pending' => Certificate::whereNull('pdf_path')->count(),
                'issued_this_month' => Certificate::whereBetween('issued_on', [
                    now()->startOfMonth()->toDateString(),
                    now()->endOfMonth()->toDateString(),
                ])->count(),
            ],
        ]);
    }

    public function generate(Certificate $certificate, CertificatePdfService $service)
    {
        $service->render($certificate);

        return back()->with('success', 'Certificate PDF generated.');
    }

    public function templates()
    {
        return view('admin.elearning.certificates.templates', [
            'templates' => CertificateTemplate::with(['course','event'])->latest()->paginate(20),
            'courses' => Course::orderBy('title')->get(),
            'events' => Event::latest('starts_at')->get(),
        ]);
    }

    public function storeTemplate(Request $request)
    {
        $data = $request->validate([
            'name' => ['required','string','max:190'],
            'context_type' => ['required','in:course,event,default'],
            'course_id' => ['nullable','exists:courses,id'],
            'event_id' => ['nullable','exists:events,id'],
            'orientation' => ['required','in:landscape,portrait'],
            'background' => ['required','file','mimes:png,jpg,jpeg,webp','max:10240'],
        ]);

        if ($data['context_type'] === 'course') {
            $request->validate(['course_id' => ['required','exists:courses,id']]);
        }

        if ($data['context_type'] === 'event') {
            $request->validate(['event_id' => ['required','exists:events,id']]);
        }

        $path = $request->file('background')->store('certificate-templates', 'public');

        CertificateTemplate::create([
            'name' => $data['name'],
            'context_type' => $data['context_type'],
            'course_id' => $data['context_type'] === 'course' ? $data['course_id'] : null,
            'event_id' => $data['context_type'] === 'event' ? $data['event_id'] : null,
            'background_path' => $path,
            'orientation' => $data['orientation'],
            'is_active' => true,
            'created_by' => auth()->id(),
        ]);

        return back()->with('success','Certificate template uploaded.');
    }

    public function destroyTemplate(CertificateTemplate $template)
    {
        if ($template->background_path) {
            Storage::disk('public')->delete($template->background_path);
        }

        $template->delete();

        return back()->with('success','Certificate template deleted.');
    }
}