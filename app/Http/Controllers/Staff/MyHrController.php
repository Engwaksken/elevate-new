<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Admin\HR\HrComplianceController;
use App\Http\Controllers\Controller;
use App\Models\StaffDocument;
use App\Models\HrDocumentRequirement;
use App\Models\HrPolicy;
use App\Models\HrPolicyAssignment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/** Staff self-service: read and acknowledge policies, upload required HR documents. */
class MyHrController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        return view('staff.hr.index', [
            'assignments' => HrPolicyAssignment::with('policy')->where('user_id', $user->id)
                ->whereHas('policy', fn ($q) => $q->where('is_active', true))
                ->get()->sortBy(fn ($a) => [$a->acknowledged_at ? 1 : 0, $a->due_date?->timestamp ?? PHP_INT_MAX])->values(),
            'requirements' => HrDocumentRequirement::where('is_active', true)->orderBy('position')->orderBy('name')->get(),
            'documents' => StaffDocument::where('user_id', $user->id)->latest()->get(),
        ]);
    }

    public function policy(Request $request, HrPolicy $policy)
    {
        $assignment = $this->assignment($request, $policy);
        if ($assignment && ! $assignment->viewed_at) {
            $assignment->update(['viewed_at' => now()]);
        }

        return view('staff.hr.policy', ['policy' => $policy, 'assignment' => $assignment]);
    }

    public function file(Request $request, HrPolicy $policy)
    {
        $this->assignment($request, $policy);
        abort_unless($policy->file_path && Storage::disk(HrPolicy::DISK)->exists($policy->file_path), 404);

        $name = $policy->file_name ?: Str::slug($policy->title).'.pdf';

        return $request->boolean('download')
            ? Storage::disk(HrPolicy::DISK)->download($policy->file_path, $name)
            : Storage::disk(HrPolicy::DISK)->response($policy->file_path, $name);
    }

    public function acknowledge(Request $request, HrPolicy $policy)
    {
        $request->validate(['confirm' => ['accepted']], ['confirm.accepted' => 'Tick the box to confirm you have read and understood the policy.']);
        $assignment = $this->assignment($request, $policy);
        abort_unless($assignment, 403);
        $assignment->update(['acknowledged_at' => $assignment->acknowledged_at ?? now(), 'viewed_at' => $assignment->viewed_at ?? now()]);

        return redirect()->route('staff.hr.index')->with('success', 'Thank you - your acknowledgement of "'.$policy->title.'" has been recorded.');
    }

    public function upload(Request $request)
    {
        $data = $request->validate([
            'requirement_id' => ['nullable', Rule::exists('hr_document_requirements', 'id')->where('is_active', true)],
            'title' => ['required_without:requirement_id', 'nullable', 'string', 'max:190'],
            'file' => ['required', 'file', 'max:10240', 'mimes:pdf,doc,docx,jpg,jpeg,png'],
        ], ['title.required_without' => 'Name the document you are uploading.']);

        // A new upload for a requirement replaces earlier copies HR has not approved.
        if (! empty($data['requirement_id'])) {
            StaffDocument::where('user_id', $request->user()->id)->where('requirement_id', $data['requirement_id'])
                ->where('status', '!=', 'approved')->get()
                ->each(function (StaffDocument $old) {
                    Storage::disk(StaffDocument::DISK)->delete($old->path);
                    $old->delete();
                });
        }

        HrComplianceController::storeDocument($request->user(), $request->file('file'), $data);

        return redirect()->route('staff.hr.index', ['tab' => 'documents'])->with('success', 'Document uploaded. HR will review it.');
    }

    public function download(Request $request, StaffDocument $document)
    {
        abort_unless($document->user_id === $request->user()->id, 403);
        abort_unless(Storage::disk(StaffDocument::DISK)->exists($document->path), 404);

        return Storage::disk(StaffDocument::DISK)->download($document->path, $document->original_name);
    }

    public function destroy(Request $request, StaffDocument $document)
    {
        abort_unless($document->user_id === $request->user()->id, 403);
        abort_if($document->status === 'approved', 422, 'Approved documents can only be removed by HR.');
        Storage::disk(StaffDocument::DISK)->delete($document->path);
        $document->delete();

        return redirect()->route('staff.hr.index', ['tab' => 'documents'])->with('success', 'Document removed.');
    }

    /** The viewer's assignment; HR may open any policy without one. */
    private function assignment(Request $request, HrPolicy $policy): ?HrPolicyAssignment
    {
        $assignment = HrPolicyAssignment::where('policy_id', $policy->id)->where('user_id', $request->user()->id)->first();
        abort_unless($assignment || $request->user()->hasPermission('hr.view'), 403, 'This policy has not been shared with you.');

        return $assignment;
    }
}
