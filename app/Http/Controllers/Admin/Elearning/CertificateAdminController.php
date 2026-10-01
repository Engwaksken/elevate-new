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
            'templates' => CertificateTemplate::with(['courses:id,title','course:id,title','event:id,title'])->latest()->paginate(20),
            'courses' => Course::orderBy('title')->get(['id','title','code']),
            'events' => Event::latest('starts_at')->get(['id','title','starts_at']),
        ]);
    }

    public function storeTemplate(Request $request)
    {
        $data = $this->validatedTemplate($request, requireImage: true);

        $template = CertificateTemplate::create($this->templateAttributes($data) + [
            'background_path' => $request->file('background')->store('certificate-templates', 'public'),
            'is_active' => true,
            'created_by' => auth()->id(),
        ]);

        $template->courses()->sync($data['context_type'] === 'course' ? $data['course_ids'] : []);

        return back()->with('success','Certificate template uploaded.');
    }

    public function updateTemplate(Request $request, CertificateTemplate $template)
    {
        $data = $this->validatedTemplate($request, requireImage: false);
        $attributes = $this->templateAttributes($data);

        if ($request->hasFile('background')) {
            $old = $template->background_path;
            $attributes['background_path'] = $request->file('background')->store('certificate-templates', 'public');

            if ($old) {
                Storage::disk('public')->delete($old);
            }
        }

        $template->update($attributes);
        $template->courses()->sync($data['context_type'] === 'course' ? $data['course_ids'] : []);

        return back()->with('success','Certificate template updated.');
    }

    private function validatedTemplate(Request $request, bool $requireImage): array
    {
        return $request->validate([
            'template_id' => ['nullable','integer'],
            'name' => ['required','string','max:190'],
            'context_type' => ['required','in:course,event,default'],
            'course_ids' => ['nullable','array','required_if:context_type,course'],
            'course_ids.*' => ['integer','exists:courses,id'],
            'event_id' => ['nullable','exists:events,id','required_if:context_type,event'],
            'orientation' => ['required','in:landscape,portrait'],
            'background' => [$requireImage ? 'required' : 'nullable','file','mimes:png,jpg,jpeg,webp','max:10240'],
        ], [
            'course_ids.required_if' => 'Select at least one course for this template.',
            'event_id.required_if' => 'Select the event for this template.',
        ]);
    }

    private function templateAttributes(array $data): array
    {
        $courseIds = array_values(array_unique(array_map('intval', $data['course_ids'] ?? [])));

        return [
            'name' => $data['name'],
            'context_type' => $data['context_type'],
            // course_id is kept for older code paths; the course list lives in the pivot.
            'course_id' => $data['context_type'] === 'course' ? ($courseIds[0] ?? null) : null,
            'event_id' => $data['context_type'] === 'event' ? $data['event_id'] : null,
            'orientation' => $data['orientation'],
        ];
    }

    public function previewTemplate(CertificateTemplate $template)
    {
        abort_unless($template->background_path && Storage::disk('public')->exists($template->background_path), 404);

        return Storage::disk('public')->response($template->background_path);
    }

    public function toggleTemplate(CertificateTemplate $template)
    {
        $template->update(['is_active' => ! $template->is_active]);

        return back()->with('success', $template->is_active ? 'Template activated.' : 'Template deactivated.');
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