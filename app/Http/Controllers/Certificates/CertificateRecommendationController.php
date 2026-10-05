<?php

namespace App\Http\Controllers\Certificates;

use App\Http\Controllers\Admin\Concerns\BulkDeletesRecords;
use App\Http\Controllers\Controller;
use App\Models\CertificateRecommendation;
use App\Models\Course;
use App\Models\Event;
use App\Services\CertificateRecommendationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CertificateRecommendationController extends Controller
{
    use BulkDeletesRecords;

    public function __construct(private CertificateRecommendationService $service)
    {
    }

    protected function bulkDeleteModel(): string
    {
        return CertificateRecommendation::class;
    }

    public function index(Request $request): View
    {
        $user = $request->user();
        abort_unless($this->service->canRecommend($user), 403);

        $base = $this->service->visibleTo($user);
        $query = (clone $base)->with(['user', 'course', 'event', 'recommender', 'reviewer'])->latest('id');

        if (in_array($status = $request->get('status'), CertificateRecommendation::STATUSES, true)) {
            $query->where('status', $status);
        }

        if (in_array($context = $request->get('context'), ['course', 'event'], true)) {
            $query->where('context_type', $context);
        }

        if ($search = trim((string) $request->get('search'))) {
            $query->where(function ($inner) use ($search) {
                $inner->whereHas('user', fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%")->orWhere('participant_code', 'like', "%{$search}%"))
                    ->orWhereHas('course', fn ($q) => $q->where('title', 'like', "%{$search}%"))
                    ->orWhereHas('event', fn ($q) => $q->where('title', 'like', "%{$search}%"));
            });
        }

        return view('certificates.recommendations.index', [
            'recommendations' => $query->paginate(in_array($perPage = (int) $request->get('per_page'), [10, 25, 50, 100], true) ? $perPage : 25)->withQueryString(),
            'canApprove' => $this->service->canApprove($user),
            'stats' => [
                'pending' => (clone $base)->where('status', 'pending')->count(),
                'approved' => (clone $base)->where('status', 'approved')->count(),
                'rejected' => (clone $base)->where('status', 'rejected')->count(),
                'total' => (clone $base)->count(),
            ],
        ]);
    }

    public function create(Request $request): View
    {
        $user = $request->user();
        abort_unless($this->service->canRecommend($user), 403);

        $target = $this->resolveTarget($request, required: false);

        return view('certificates.recommendations.create', [
            'courses' => $this->service->coursesFor($user)->get(['id', 'title']),
            'events' => $this->service->eventsFor($user)->get(['id', 'title', 'starts_at']),
            'target' => $target,
            'participants' => $target ? $this->service->participants($target) : collect(),
            'canApprove' => $this->service->canApprove($user),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($this->service->canRecommend($user), 403);

        $data = $request->validate([
            'user_ids' => ['required', 'array', 'min:1'],
            'user_ids.*' => ['integer'],
            'reason' => ['nullable', 'string', 'max:2000'],
            'issue_now' => ['nullable', 'boolean'],
        ], ['user_ids.required' => 'Select at least one participant.']);

        $target = $this->resolveTarget($request);
        $issueNow = $request->boolean('issue_now');

        abort_if($issueNow && ! $this->service->canApprove($user), 403);

        $result = $issueNow
            ? $this->service->issueDirectly($user, $target, $data['user_ids'], $data['reason'] ?? null)
            : $this->service->recommend($user, $target, $data['user_ids'], $data['reason'] ?? null);

        $message = $issueNow
            ? $result['created'].' '.Str::plural('certificate', $result['created']).' issued.'
            : $result['created'].' '.Str::plural('participant', $result['created']).' recommended for review.';

        if ($result['skipped'] > 0) {
            $message .= ' '.$result['skipped'].' skipped (already certified or awaiting review).';
        }

        return redirect()
            ->route('certificates.recommendations.create', [$target instanceof Course ? 'course_id' : 'event_id' => $target->id])
            ->with($result['created'] > 0 ? 'success' : 'error', $message);
    }

    public function review(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($this->service->canApprove($user), 403);

        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
            'decision' => ['required', 'in:approve,reject'],
            'review_notes' => ['nullable', 'string', 'max:2000', 'required_if:decision,reject'],
        ], [
            'ids.required' => 'Select at least one recommendation.',
            'review_notes.required_if' => 'Give a reason when rejecting recommendations.',
        ]);

        $recommendations = CertificateRecommendation::with(['user', 'course', 'event', 'recommender'])
            ->whereIn('id', $data['ids'])
            ->pending()
            ->get();

        foreach ($recommendations as $recommendation) {
            $data['decision'] === 'approve'
                ? $this->service->approve($recommendation, $user, $data['review_notes'] ?? null)
                : $this->service->reject($recommendation, $user, $data['review_notes'] ?? null);
        }

        $count = $recommendations->count();

        return back()->with('success', $count.' '.Str::plural('recommendation', $count).' '
            .($data['decision'] === 'approve' ? 'approved and certificates issued.' : 'rejected.'));
    }

    private function resolveTarget(Request $request, bool $required = true): Course|Event|null
    {
        $user = $request->user();

        if ($courseId = $request->integer('course_id')) {
            return $this->service->coursesFor($user)->whereKey($courseId)->firstOr(fn () => abort(403));
        }

        if ($eventId = $request->integer('event_id')) {
            return $this->service->eventsFor($user)->whereKey($eventId)->firstOr(fn () => abort(403));
        }

        abort_if($required, 422, 'Choose a course or event.');

        return null;
    }
}
