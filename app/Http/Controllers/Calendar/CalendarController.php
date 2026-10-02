<?php
namespace App\Http\Controllers\Calendar;

use App\Http\Controllers\Controller;
use App\Services\CalendarFeedService;
use Carbon\CarbonImmutable;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;

class CalendarController extends Controller
{
    public function index(Request $request, CalendarFeedService $feed)
    {
        abort_unless($request->user()->isActive(), 403);
        $filters = $request->validate(['month'=>['nullable','date_format:Y-m'], 'event_type'=>['nullable','string','max:100'], 'programme_id'=>['nullable','integer','exists:programmes,id']]);
        $zone = config('app.timezone', 'UTC');
        $start = CarbonImmutable::createFromFormat('!Y-m', $filters['month'] ?? now()->format('Y-m'), $zone);
        $gridStart = $start->startOfWeek(\Carbon\CarbonInterface::MONDAY);
        $gridEnd = $start->endOfMonth()->endOfWeek(\Carbon\CarbonInterface::SUNDAY);
        $entries = $feed->entries($request->user(), $gridStart, $gridEnd->addDay()->startOfDay(), $filters);
        $page = LengthAwarePaginator::resolveCurrentPage();
        $events = new LengthAwarePaginator($entries->forPage($page, 50)->values(), $entries->count(), 50, $page, ['path'=>$request->url(), 'query'=>$request->query()]);
        return view('calendar.index', ['start'=>$start,'gridStart'=>$gridStart,'gridEnd'=>$gridEnd,'zone'=>$zone,'days'=>$entries->groupBy(fn ($entry) => $entry['starts_at']->format('Y-m-d')), 'events'=>$events]);
    }
}
