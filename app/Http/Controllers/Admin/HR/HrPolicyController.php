<?php

namespace App\Http\Controllers\Admin\HR;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Employee;
use App\Models\HrPolicy;
use App\Models\User;
use App\Services\HR\HrComplianceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/** HR/admins publish organisation policies and share them with staff for acknowledgement. */
class HrPolicyController extends Controller
{
    public function __construct(private readonly HrComplianceService $compliance)
    {
    }

    public function index()
    {
        $policies = HrPolicy::withCount([
            'assignments',
            'assignments as acknowledged_count' => fn ($q) => $q->whereNotNull('acknowledged_at'),
        ])->orderByDesc('is_active')->orderBy('category')->orderBy('title')->get();

        return view('admin.hr.policies.index', [
            'policies' => $policies,
            'departments' => Department::where('is_active', true)->orderBy('name')->get(),
            'staff' => $this->compliance->staffQuery()->orderBy('name')->get(['id', 'name', 'email']),
        ]);
    }

    public function show(HrPolicy $policy)
    {
        $policy->load(['assignments' => fn ($q) => $q->with('user:id,name,email')->orderByRaw('acknowledged_at IS NOT NULL')->orderBy('due_date')]);

        return view('admin.hr.policies.show', [
            'policy' => $policy,
            'departments' => Department::where('is_active', true)->orderBy('name')->get(),
            'staff' => $this->compliance->staffQuery()->orderBy('name')->get(['id', 'name', 'email']),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['created_by'] = $request->user()->id;
        $policy = HrPolicy::create($data);

        $shared = $this->share($request, $policy);

        return redirect()->route('admin.hr.policies.show', $policy)
            ->with('success', 'Policy published'.($shared ? " and shared with {$shared} staff member(s)." : '. Use "Share with staff" to send it out.'));
    }

    public function update(Request $request, HrPolicy $policy)
    {
        $data = $this->validated($request, $policy);
        $newVersion = $request->boolean('reacknowledge');
        $policy->update($data);

        if ($newVersion) {
            // A revised policy must be read again by everyone it was shared with.
            $policy->assignments()->update([
                'acknowledged_at' => null, 'viewed_at' => null,
                'due_date' => now()->addDays(max(1, (int) $policy->due_days))->toDateString(),
            ]);
            $users = User::whereIn('id', $policy->assignments()->pluck('user_id'))->get();
            app(\App\Services\NotificationDispatcher::class)->notifyMany($users, 'hr_policy_shared', 'Updated policy: '.$policy->title,
                'The '.$policy->title.' policy has been revised. Please read and acknowledge the new version.', route('staff.hr.policies.show', $policy));
        }

        return back()->with('success', 'Policy updated'.($newVersion ? ' and staff asked to acknowledge the new version.' : '.'));
    }

    public function destroy(HrPolicy $policy)
    {
        $policy->update(['is_active' => false]);

        return back()->with('success', 'Policy archived. Staff will no longer be asked to acknowledge it.');
    }

    public function assign(Request $request, HrPolicy $policy)
    {
        $count = $this->share($request, $policy);

        return back()->with('success', $count ? "Policy shared with {$count} staff member(s)." : 'Everyone selected already has this policy.');
    }

    public function remind(HrPolicy $policy)
    {
        $userIds = $policy->assignments()->whereNull('acknowledged_at')->pluck('user_id');
        $count = $this->compliance->sendReminders($userIds, 'policies');

        return back()->with('success', "Reminder sent to {$count} staff member(s).");
    }

    public function file(HrPolicy $policy)
    {
        abort_unless($policy->file_path && Storage::disk(HrPolicy::DISK)->exists($policy->file_path), 404);

        return Storage::disk(HrPolicy::DISK)->download($policy->file_path, $policy->file_name ?: Str::slug($policy->title));
    }

    /** Resolve the audience picked on the form and assign the policy. */
    private function share(Request $request, HrPolicy $policy): int
    {
        $data = $request->validate([
            'audience' => ['nullable', Rule::in(['none', 'all', 'department', 'selected', 'new_staff'])],
            'department_ids' => ['nullable', 'array'],
            'department_ids.*' => ['integer'],
            'user_ids' => ['nullable', 'array'],
            'user_ids.*' => ['integer'],
            'new_staff_days' => ['nullable', 'integer', 'min:1', 'max:365'],
        ]);

        $staff = $this->compliance->staffQuery();
        $ids = match ($data['audience'] ?? 'none') {
            'all' => $staff->pluck('id'),
            'department' => $staff->whereIn('id', Employee::whereIn('department_id', $data['department_ids'] ?? [])->pluck('user_id'))->pluck('id'),
            'selected' => $staff->whereIn('id', $data['user_ids'] ?? [])->pluck('id'),
            'new_staff' => $staff->whereIn('id', Employee::where('start_date', '>=', now()->subDays((int) ($data['new_staff_days'] ?? 30)))->pluck('user_id'))
                ->orWhere(fn ($q) => $q->where('user_type', 'staff')->where('status', 'active')->where('created_at', '>=', now()->subDays((int) ($data['new_staff_days'] ?? 30))))
                ->pluck('id'),
            default => collect(),
        };

        return $ids->isEmpty() ? 0 : $this->compliance->assignPolicy($policy, $ids, $request->user());
    }

    private function validated(Request $request, ?HrPolicy $policy = null): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:190'],
            'category' => ['nullable', 'string', 'max:80'],
            'version' => ['nullable', 'string', 'max:30'],
            'summary' => ['nullable', 'string', 'max:2000'],
            'body' => ['nullable', 'string', 'max:100000'],
            'file' => [$policy || $request->filled('body') ? 'nullable' : 'required', 'file', 'mimes:pdf,doc,docx', 'max:20480'],
            'effective_date' => ['nullable', 'date'],
            'requires_acknowledgement' => ['nullable', 'boolean'],
            'assign_to_new_staff' => ['nullable', 'boolean'],
            'due_days' => ['nullable', 'integer', 'min:1', 'max:120'],
            'is_active' => ['nullable', 'boolean'],
        ], ['file.required' => 'Upload the policy document or type the policy text.']);

        if ($file = $request->file('file')) {
            if ($policy?->file_path) {
                Storage::disk(HrPolicy::DISK)->delete($policy->file_path);
            }
            $data['file_path'] = $file->storeAs('hr/policies', Str::uuid().'.'.strtolower($file->getClientOriginalExtension()), HrPolicy::DISK);
            $data['file_name'] = $file->getClientOriginalName();
        }
        unset($data['file']);

        $data['requires_acknowledgement'] = $request->boolean('requires_acknowledgement');
        $data['assign_to_new_staff'] = $request->boolean('assign_to_new_staff');
        $data['due_days'] = $data['due_days'] ?? 7;
        $data['is_active'] = $policy ? $request->boolean('is_active', true) : true;

        return $data;
    }
}
