<?php
namespace App\Http\Controllers\Calendar;

use App\Http\Controllers\Controller;
use App\Models\CalendarEvent;
use Illuminate\Http\Request;

class CalendarController extends Controller
{
    public function index(Request $request)
    {
        $query=CalendarEvent::orderBy('starts_at');

        if($type=$request->get('event_type')) $query->where('event_type',$type);
        if($programme=$request->get('programme_id')) $query->where('programme_id',$programme);

        return view('calendar.index',[
            'events'=>$query->paginate(50)->withQueryString()
        ]);
    }
}
