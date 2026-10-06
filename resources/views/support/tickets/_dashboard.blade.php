{{-- IT support summary on the admin dashboard, for the IT team and administrators. --}}
@use('App\Models\ItSupportTicket')
@php($itqActive = ItSupportTicket::where('status', '!=', 'resolved'))
@php($itqStats = ['open' => (clone $itqActive)->where('status', 'open')->count(), 'in_progress' => (clone $itqActive)->where('status', 'in_progress')->count(), 'awaiting_requester' => (clone $itqActive)->where('status', 'awaiting_requester')->count(), 'unassigned' => (clone $itqActive)->whereNull('assignee_id')->count()])
@php($itqLatest = (clone $itqActive)->with(['requester:id,name', 'assignee:id,name'])->orderByRaw("case priority when 'urgent' then 0 when 'high' then 1 else 2 end")->latest()->limit(4)->get())
<section class="admin-panel executive-section">
    <div class="admin-panel-head">
        <div>
            <h2>IT Support</h2>
            <p>{{ number_format(array_sum([$itqStats['open'], $itqStats['in_progress'], $itqStats['awaiting_requester']])) }} active help {{ \Illuminate\Support\Str::plural('request', $itqStats['open'] + $itqStats['in_progress'] + $itqStats['awaiting_requester']) }} · {{ number_format($itqStats['unassigned']) }} unassigned</p>
        </div>
        <a class="btn btn-outline btn-sm" href="{{ route('it-support.tickets.index') }}">Open IT queue</a>
    </div>
    <div class="admin-stats-grid compact itq-stats">
        @foreach([['open','Open','fa-inbox',['status' => 'open']],['in_progress','In progress','fa-spinner',['status' => 'in_progress']],['awaiting_requester','Awaiting requester','fa-reply',['status' => 'awaiting_requester']],['unassigned','Unassigned','fa-user-slash',['assignee_id' => 'none']]] as [$key,$label,$icon,$query])
            <a class="admin-stat itq-stat" href="{{ route('it-support.tickets.index', $query) }}"><span class="admin-stat-icon"><i class="fas {{ $icon }}"></i></span><div><small>{{ $label }}</small><strong>{{ number_format($itqStats[$key]) }}</strong></div></a>
        @endforeach
    </div>
    @if($itqLatest->isNotEmpty())
        <div class="itq-grid" style="margin-top:14px">
            @foreach($itqLatest as $ticket)
                <a class="itq-card itq-{{ $ticket->status }}" href="{{ route('it-support.tickets.show', $ticket) }}">
                    <div class="itq-card-top"><span class="itq-id">#{{ $ticket->id }}</span><span class="itq-chip itq-chip--{{ $ticket->status }}">{{ $ticket->status === 'awaiting_requester' ? 'Awaiting requester' : (ItSupportTicket::STATUSES[$ticket->status] ?? $ticket->status) }}</span></div>
                    <strong class="itq-subject">{{ $ticket->subject }}</strong>
                    <div class="itq-foot"><span><i class="fas fa-user"></i> {{ $ticket->requester?->name ?? 'Unknown' }}</span><span><i class="fas fa-user-gear"></i> {{ $ticket->assignee?->name ?? 'Unassigned' }}</span><span><i class="fas fa-clock"></i> {{ $ticket->created_at?->diffForHumans() }}</span></div>
                </a>
            @endforeach
        </div>
    @endif
</section>
@include('support.tickets._styles')
