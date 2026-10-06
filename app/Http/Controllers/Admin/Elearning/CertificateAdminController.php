<?php

namespace App\Http\Controllers\Admin\Elearning;

use App\Http\Controllers\Concerns\ExportsTables;
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
    use ExportsTables;
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

    public function templates(Request $request)
    {
        if ($format = $this->exportFormat($request)) {
            return $this->exportTable($format, 'Certificate Templates', CertificateTemplate::with(['course','courses','event'])->latest(), [
                'Name' => 'name',
                'Context' => fn ($t) => $t->context_type === 'default' ? 'Default template' : ucfirst((string) $t->context_type),
                'Course(s)' => fn ($t) => $t->courses->pluck('title')->push($t->course?->title)->filter()->unique()->join(', '),
                'Event' => 'event.title',
                'Orientation' => 'orientation',
                'Status' => fn ($t) => $t->is_active ? 'Active' : 'Inactive',
                'Created' => 'created_at',
            ]);
        }

        return view('admin.elearning.certificates.templates', [
            'templates' => CertificateTemplate::with(['course','courses','event'])->latest()->paginate(20),
            'courses' => Course::orderBy('title')->get(),
            'events' => Event::latest('starts_at')->get(),
        ]);
    }

    public function storeTemplate(Request $request)
    {
        // Accept the previous single-course form as well as the multi-course form.
        if (! $request->has('course_ids') && $request->filled('course_id')) {
            $request->merge(['course_ids' => [$request->input('course_id')]]);
        }
        $data = $request->validate([
            'name' => ['required','string','max:190'],
            'context_type' => ['required','in:course,event,default'],
            'course_ids' => ['required_if:context_type,course','nullable','array','min:1'],
            'course_ids.*' => ['integer','distinct', \Illuminate\Validation\Rule::exists('courses', 'id')->whereNull('deleted_at')],
            'event_id' => ['nullable','exists:events,id'],
            'orientation' => ['required','in:landscape,portrait'],
            'background' => ['required','file','mimes:png,jpg,jpeg,webp','max:10240'],
        ]);

        if ($data['context_type'] === 'event') {
            $request->validate(['event_id' => ['required','exists:events,id']]);
        }

        $path = $request->file('background')->store('certificate-templates', 'public');

        try {
            \Illuminate\Support\Facades\DB::transaction(function () use ($data, $path) {
                $template = CertificateTemplate::create([
                    'name' => $data['name'],
                    'context_type' => $data['context_type'],
                    'course_id' => null,
                    'event_id' => $data['context_type'] === 'event' ? $data['event_id'] : null,
                    'background_path' => $path,
                    'orientation' => $data['orientation'],
                    'is_active' => true,
                    'created_by' => auth()->id(),
                ]);
                if ($data['context_type'] === 'course') {
                    $template->courses()->sync($data['course_ids']);
                }
            });
        } catch (\Throwable $exception) {
            Storage::disk('public')->delete($path);
            throw $exception;
        }

        return back()->with('success','Certificate template uploaded.');
    }

    public function designTemplate(CertificateTemplate $template)
    {
        abort_unless($template->background_path && Storage::disk('public')->exists($template->background_path), 404);

        return view('admin.elearning.certificates.template-design', [
            'template' => $template,
            'layout' => $template->fieldLayout(),
            'definitions' => CertificateTemplate::fieldDefinitions(),
            'fonts' => CertificateTemplate::fontOptions(),
        ]);
    }

    public function updateTemplateLayout(Request $request, CertificateTemplate $template)
    {
        $validated = $request->validate([
            'layout' => ['required', 'array'],
            'layout.*.enabled' => ['nullable', 'boolean'],
            'layout.*.x' => ['nullable', 'numeric', 'between:0,100'],
            'layout.*.y' => ['nullable', 'numeric', 'between:0,100'],
            'layout.*.width' => ['nullable', 'numeric', 'between:1,100'],
            'layout.*.align' => ['nullable', 'in:left,center,right'],
            'layout.*.font_family' => ['nullable', 'string', 'max:60'],
            'layout.*.font_size' => ['nullable', 'numeric', 'between:6,120'],
            'layout.*.color' => ['nullable', 'string', 'max:20'],
            'layout.*.bold' => ['nullable', 'boolean'],
            'layout.*.italic' => ['nullable', 'boolean'],
            'layout.*.underline' => ['nullable', 'boolean'],
            'layout.*.uppercase' => ['nullable', 'boolean'],
            'layout.*.letter_spacing' => ['nullable', 'numeric', 'between:-5,20'],
            'layout.*.prefix' => ['nullable', 'string', 'max:60'],
            'layout.*.suffix' => ['nullable', 'string', 'max:60'],
        ]);

        $input = $validated['layout'];
        $defaults = CertificateTemplate::defaultLayout();
        $layout = [];

        foreach (array_keys(CertificateTemplate::fieldDefinitions()) as $key) {
            $row = $input[$key] ?? [];
            $default = $defaults[$key];

            $layout[$key] = [
                'enabled' => (bool) ($row['enabled'] ?? false),
                'x' => isset($row['x']) ? round((float) $row['x'], 2) : $default['x'],
                'y' => isset($row['y']) ? round((float) $row['y'], 2) : $default['y'],
                'width' => isset($row['width']) ? round((float) $row['width'], 2) : $default['width'],
                'align' => $row['align'] ?? $default['align'],
                'font_family' => $row['font_family'] ?? $default['font_family'],
                'font_size' => isset($row['font_size']) ? round((float) $row['font_size'], 2) : $default['font_size'],
                'color' => $row['color'] ?? $default['color'],
                'bold' => filter_var($row['bold'] ?? false, FILTER_VALIDATE_BOOLEAN),
                'italic' => filter_var($row['italic'] ?? false, FILTER_VALIDATE_BOOLEAN),
                'underline' => filter_var($row['underline'] ?? false, FILTER_VALIDATE_BOOLEAN),
                'uppercase' => filter_var($row['uppercase'] ?? false, FILTER_VALIDATE_BOOLEAN),
                'letter_spacing' => isset($row['letter_spacing']) ? round((float) $row['letter_spacing'], 2) : 0,
                'prefix' => $row['prefix'] ?? '',
                'suffix' => $row['suffix'] ?? '',
            ];
        }

        $template->update(['layout' => $layout]);

        return redirect()->route('admin.elearning.certificates.templates.design', $template)
            ->with('success', 'Certificate layout saved.');
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
