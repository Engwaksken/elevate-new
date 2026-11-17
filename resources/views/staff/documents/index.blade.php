@extends('layouts.admin')
@section('title','Documents | ElevateHer360')
@section('content')
@php use App\Support\DocumentUi; use App\Models\DocumentShare; @endphp

<div class="dl-shell">
    @include('staff.documents._rail', ['active' => 'home'])

    <div class="dl-main">
        <header class="dl-site-header">
            <span class="dl-site-tile lg" style="--tile:#800000"><i class="fas fa-folder-tree"></i></span>
            <div>
                <h1>Documents</h1>
                <p>Department sites for storing, sharing and versioning team files.</p>
            </div>
            <div class="dl-site-header-actions">
                <a href="{{ route('staff.documents.shared') }}" class="btn btn-outline"><i class="fas fa-user-group"></i> Shared with me @if($sharedCount)<span class="dl-access-pill">{{ $sharedCount }}</span>@endif</a>
            </div>
        </header>

        <div class="dl-home">
            <h2>Department sites</h2>
            @if($sites->isEmpty())
                <div class="dl-empty">
                    <div class="dl-empty-art"><i class="fas fa-folder-open"></i><i class="fas fa-file-word"></i></div>
                    <strong>No department sites yet</strong>
                    <span>Your HR record has no department. Ask HR to add you to one, or open items people share with you.</span>
                </div>
            @else
            <div class="dl-site-cards">
                @foreach($sites as $site)
                    @php $c = $counts->get($site->id); @endphp
                    <a class="dl-site-card" href="{{ route('staff.documents.library', $site) }}" style="--tile:{{ DocumentUi::colour($site->id) }}">
                        @if(in_array($site->id, $mySiteIds))<span class="dl-mine">Your department</span>@endif
                        <span class="dl-site-tile md" style="--tile:{{ DocumentUi::colour($site->id) }}">{{ DocumentUi::initials($site->name) }}</span>
                        <div>
                            <strong>{{ $site->name }}</strong>
                            <small>{{ $site->code ? $site->code.' · ' : '' }}{{ DocumentShare::LABELS[$site->access_role] ?? 'No access' }}</small>
                        </div>
                        <div class="dl-site-card-foot">
                            <span><i class="fas fa-file"></i> {{ number_format($c->files ?? 0) }} {{ Str::plural('file', $c->files ?? 0) }}</span>
                            <span><i class="fas fa-database"></i> {{ \App\Models\DocumentFile::formatBytes((int) ($c->bytes ?? 0)) }}</span>
                            @if($c?->last_change)<span><i class="fas fa-clock"></i> {{ \Illuminate\Support\Carbon::parse($c->last_change)->diffForHumans() }}</span>@endif
                        </div>
                    </a>
                @endforeach
            </div>
            @endif

            <h2>Recent files</h2>
            @if($recent->isEmpty())
                <p class="dl-muted">Files uploaded to your department sites will show here.</p>
            @else
            <table class="dl-list">
                <thead><tr>
                    <th class="col-icon"></th><th class="col-name">Name</th><th class="col-modified">Modified</th><th class="col-by">Modified by</th><th class="col-site"></th>
                </tr></thead>
                <tbody>
                @foreach($recent as $file)
                    @php [$fi, $fc] = $file->iconInfo(); @endphp
                    <tr>
                        <td class="col-icon"><span class="dl-icon {{ $fc }}"><i class="fas {{ $fi }}"></i></span></td>
                        <td class="col-name">
                            <a class="dl-name" href="{{ route('staff.documents.open', $file) }}" target="_blank" rel="noopener"><span>{{ $file->name }}</span></a>
                            <span class="dl-sub">{{ $file->department?->name }}{{ $file->folder ? ' › '.$file->folder->name : '' }}</span>
                        </td>
                        <td class="col-modified" title="{{ $file->updated_at?->format('d M Y H:i') }}">{{ $file->updated_at?->diffForHumans() }}</td>
                        <td class="col-by">{{ $file->editor?->name ?? '—' }}</td>
                        <td class="col-site"><a href="{{ route('staff.documents.library', array_filter(['department' => $file->department_id, 'folder' => $file->folder_id])) }}">Open location</a></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            @endif
        </div>
    </div>
</div>
@endsection
