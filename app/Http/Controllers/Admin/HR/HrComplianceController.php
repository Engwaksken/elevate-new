<?php

namespace App\Http\Controllers\Admin\HR;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\StaffDocument;
use App\Models\HrDocumentReminder;
use App\Models\HrDocumentRequirement;
use App\Models\User;
use App\Services\HR\HrComplianceService;
use App\Services\NotificationDispatcher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Staff documents & compliance: which documents staff must submit, review of
 * uploads, and reminders to staff with missing documents or unread policies.
 */
class HrComplianceController extends Controller
{
    public function __construct(private readonly HrComplianceService $compliance)
    {
    }

    public function index(Request $request)
    {
        $users = $this->compliance->staffQuery()
            ->when(trim((string) $request->get('search')), fn ($q, $s) => $q->where(fn ($w) => $w->where('name', 'like', "%{$s}%")->orWhere('email', 'like', "%{$s}%")))
            ->when($request->get('department'), fn ($q, $d) => $q->whereIn('id', Employee::where('department_id', $d)->pluck('user_id')))
            ->orderBy('name')->get(['id', 'name', 'email']);

        $rows = $this->compliance->summary($users);
        if ($request->get('filter') === 'outstanding') {
            $rows = $rows->filter(fn ($r) => $r['documents'] + $r['policies'] > 0)->values();
        }

        $lastReminded = HrDocumentReminder::whereIn('user_id', $users->pluck('id'))->pluck('last_reminded_at', 'user_id');

        return view('admin.hr.compliance.index', [
            'rows' => $rows,
            'lastReminded' => $lastReminded,
            'requirements' => HrDocumentRequirement::withCount(['documents as approved_count' => fn ($q) => $q->where('status', 'approved')])
                ->orderBy('position')->orderBy('name')->get(),
            'pending' => StaffDocument::with(['user:id,name,email', 'requirement:id,name'])->where('status', 'submitted')->latest()->limit(100)->get(),
            'departments' => \App\Models\Department::where('is_active', true)->orderBy('name')->get(),
            'stats' => [
                'staff' => $users->count(),
                'compliant' => $rows->filter(fn ($r) => $r['documents'] + $r['policies'] === 0)->count(),
                'missing_docs' => $rows->filter(fn ($r) => $r['documents'] > 0)->count(),
                'pending_policies' => $rows->filter(fn ($r) => $r['policies'] > 0)->count(),
            ],
        ]);
    }

    public function remind(Request $request)
    {
        $data = $request->validate([
            'user_ids' => ['nullable', 'array'],
            'user_ids.*' => ['integer'],
            'scope' => ['required', Rule::in(['all', 'documents', 'policies'])],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        if ($request->boolean('selected') && empty($data['user_ids'])) {
            return back()->withErrors(['user_ids' => 'Tick the staff members to remind, or use "Remind everyone outstanding".']);
        }

        $count = $this->compliance->sendReminders(
            empty($data['user_ids']) ? null : $data['user_ids'],
            $data['scope'],
            false,
            $data['note'] ?? null,
        );

        return back()->with('success', $count ? "Reminder sent to {$count} staff member(s)." : 'Nobody selected has outstanding items.');
    }

    public function storeRequirement(Request $request)
    {
        $data = $this->validatedRequirement($request);
        $data['position'] = (int) HrDocumentRequirement::max('position') + 1;
        HrDocumentRequirement::create($data);

        return back()->with('success', 'Document requirement added.');
    }

    public function updateRequirement(Request $request, HrDocumentRequirement $requirement)
    {
        $requirement->update($this->validatedRequirement($request));

        return back()->with('success', 'Document requirement updated.');
    }

    public function destroyRequirement(HrDocumentRequirement $requirement)
    {
        $requirement->documents()->exists() ? $requirement->update(['is_active' => false]) : $requirement->delete();

        return back()->with('success', 'Document requirement removed.');
    }

    public function review(Request $request, StaffDocument $document, NotificationDispatcher $notifier)
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(['approved', 'rejected'])],
            'review_note' => [$request->input('status') === 'rejected' ? 'required' : 'nullable', 'string', 'max:1000'],
        ], ['review_note.required' => 'Tell the staff member why the document was rejected.']);

        $document->update($data + ['reviewed_by' => $request->user()->id, 'reviewed_at' => now()]);

        if ($document->user) {
            $notifier->notify($document->user,
                'hr_document_reviewed',
                $data['status'] === 'approved' ? 'Document approved: '.$document->title : 'Document needs re-uploading: '.$document->title,
                $data['status'] === 'approved' ? 'HR has approved your '.$document->title.'.' : 'HR could not accept your '.$document->title.': '.$data['review_note'],
                route('staff.hr.index'),
                [],
                $data['status'] === 'rejected',
            );
        }

        return back()->with('success', 'Document '.$data['status'].'.');
    }

    /** HR uploads a document on behalf of a staff member (e.g. a paper copy received). */
    public function upload(Request $request, User $user)
    {
        $data = $request->validate([
            'requirement_id' => ['nullable', Rule::exists('hr_document_requirements', 'id')],
            'title' => ['required_without:requirement_id', 'nullable', 'string', 'max:190'],
            'file' => ['required', 'file', 'max:10240', 'mimes:pdf,doc,docx,jpg,jpeg,png'],
        ]);
        $this->storeDocument($user, $request->file('file'), $data, 'approved', $request->user()->id);

        return back()->with('success', 'Document saved to '.$user->name.'\'s HR file.');
    }

    public function download(StaffDocument $document)
    {
        abort_unless(Storage::disk(StaffDocument::DISK)->exists($document->path), 404);

        return Storage::disk(StaffDocument::DISK)->download($document->path, $document->original_name);
    }

    public static function storeDocument(User $user, \Illuminate\Http\UploadedFile $file, array $data, string $status = 'submitted', ?int $reviewerId = null): StaffDocument
    {
        $requirement = ! empty($data['requirement_id']) ? HrDocumentRequirement::find($data['requirement_id']) : null;
        $ext = strtolower($file->getClientOriginalExtension() ?: 'pdf');

        return StaffDocument::create([
            'user_id' => $user->id,
            'requirement_id' => $requirement?->id,
            'title' => $requirement?->name ?? trim((string) $data['title']),
            'path' => $file->storeAs('hr/staff-documents/'.$user->id, Str::uuid().'.'.$ext, StaffDocument::DISK),
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getClientMimeType(),
            'size' => $file->getSize() ?: 0,
            'status' => $status,
            'reviewed_by' => $status === 'approved' ? $reviewerId : null,
            'reviewed_at' => $status === 'approved' ? now() : null,
        ]);
    }

    private function validatedRequirement(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:190'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_mandatory' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'due_days' => ['nullable', 'integer', 'min:1', 'max:365'],
        ]);
        $data['is_mandatory'] = $request->boolean('is_mandatory');
        $data['is_active'] = $request->boolean('is_active', true);
        $data['due_days'] = $data['due_days'] ?? 14;

        return $data;
    }
}
