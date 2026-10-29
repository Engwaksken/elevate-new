<?php

namespace App\Http\Controllers\Participant;

use App\Http\Controllers\Concerns\ExportsTables;
use App\Http\Controllers\Controller;
use App\Models\UserNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class NotificationController extends Controller
{
    use ExportsTables;

    public function index(Request $request): View|\Symfony\Component\HttpFoundation\Response
    {
        $base = UserNotification::query()->where('user_id', $request->user()->id);
        $query = clone $base;

        [$from, $to] = $this->periodRange($request);
        if ($from) {
            $query->where('created_at', '>=', $from);
        }
        if ($to) {
            $query->where('created_at', '<=', $to);
        }

        if ($request->get('status') === 'unread') {
            $query->whereNull('read_at');
        }

        if ($request->get('status') === 'read') {
            $query->whereNotNull('read_at');
        }

        if ($request->filled('search')) {
            $search = trim((string) $request->get('search'));

            $query->where(function ($inner) use ($search) {
                $inner->where('title', 'like', "%{$search}%")
                    ->orWhere('message', 'like', "%{$search}%");
            });
        }

        if ($format = $this->exportFormat($request)) {
            return $this->exportTable($format, 'My Notifications', $query->latest(), [
                'Title' => 'title',
                'Message' => 'message',
                'Status' => fn ($n) => $n->read_at ? 'Read' : 'Unread',
                'Received' => 'created_at',
                'Read At' => 'read_at',
            ]);
        }

        $perPage = in_array((int) $request->get('per_page'), [10, 15, 25, 50], true) ? (int) $request->get('per_page') : 15;

        return view('notifications.index', [
            'notifications' => $query->latest()->paginate($perPage)->withQueryString(),
            'unreadCount' => (clone $base)->whereNull('read_at')->count(),
            'stats' => [
                'week' => (clone $base)->where('created_at', '>=', now()->startOfWeek())->count(),
                'month' => (clone $base)->where('created_at', '>=', now()->startOfMonth())->count(),
                'total' => (clone $base)->count(),
            ],
            'perPage' => $perPage,
        ]);
    }

    /**
     * Resolve the selected period (today/week/month/year/custom) to a created_at range.
     */
    private function periodRange(Request $request): array
    {
        return match ($request->get('period')) {
            'today' => [now()->startOfDay(), null],
            'week' => [now()->startOfWeek(), null],
            'month' => [now()->startOfMonth(), null],
            'year' => [now()->startOfYear(), null],
            'custom' => [
                $this->parseDate($request->get('from'))?->startOfDay(),
                $this->parseDate($request->get('to'))?->endOfDay(),
            ],
            default => [null, null],
        };
    }

    private function parseDate(mixed $value): ?\Illuminate\Support\Carbon
    {
        if (! is_string($value) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return null;
        }

        try {
            return \Illuminate\Support\Carbon::createFromFormat('Y-m-d', $value);
        } catch (\Throwable) {
            return null;
        }
    }

    public function read(Request $request, UserNotification $notification): RedirectResponse|Response
    {
        abort_unless($notification->user_id === $request->user()->id, 403);

        if ($notification->read_at === null) {
            $notification->update(['read_at' => now()]);
        }

        // Opening the detail modal marks it read in the background.
        if ($request->expectsJson()) {
            return response()->noContent();
        }

        if ($notification->action_url) {
            return redirect()->to($notification->action_url);
        }

        return back()->with('success', 'Notification marked as read.');
    }

    public function readAll(Request $request): RedirectResponse
    {
        UserNotification::query()
            ->where('user_id', $request->user()->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return back()->with('success', 'All notifications marked as read.');
    }

    public function bulkDestroy(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'ids' => ['required', 'array', 'max:200'],
            'ids.*' => ['integer'],
        ]);

        $deleted = UserNotification::query()
            ->where('user_id', $request->user()->id)
            ->whereIn('id', $data['ids'])
            ->delete();

        return back()->with('success', $deleted.' notification'.($deleted === 1 ? '' : 's').' deleted.');
    }
}
