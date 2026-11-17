@extends('layouts.admin')
@section('title', 'Recycle bin · '.$department->name.' | Documents')
@section('content')
@php
    use App\Models\DocumentFile;
    use App\Models\DocumentFolder;
    use App\Support\DocumentUi;
@endphp

<div class="dl-shell">
    @include('staff.documents._rail', ['active' => $department->id])

    <div class="dl-main">
        <header class="dl-site-header">
            <span class="dl-site-tile lg" style="--tile:{{ DocumentUi::colour($department->id) }}">{{ DocumentUi::initials($department->name) }}</span>
            <div>
                <h1>{{ $department->name }}</h1>
                <p><span><i class="fas fa-trash-can"></i> Recycle bin</span></p>
            </div>
            <div class="dl-site-header-actions">
                @if($siteRole)
                    <a href="{{ route('staff.documents.library', $department) }}" class="btn btn-outline"><i class="fas fa-arrow-left"></i> Back to Documents</a>
                @endif
            </div>
        </header>

        <div class="dl-commandbar">
            @if($isOwner && $items->isNotEmpty())
                <form method="POST" action="{{ route('staff.documents.recycle-bin.empty', $department) }}" data-delete-form data-confirm="Permanently delete everything in the recycle bin? This cannot be undone." style="margin:0">
                    @csrf @method('DELETE')
                    <button class="dl-cmd danger"><i class="fas fa-trash-can"></i><span class="dl-cmd-label">Empty recycle bin</span></button>
                </form>
            @endif
            <span class="dl-selection-info" style="margin-left:auto">
                <i class="fas fa-circle-info"></i>
                Items are kept for {{ DocumentFolder::RETENTION_DAYS }} days, then deleted for good.{{ $isOwner ? '' : ' You see the items you deleted.' }}
            </span>
        </div>

        <nav class="dl-breadcrumb" aria-label="Location">
            @if($siteRole)<a href="{{ route('staff.documents.library', $department) }}">Documents</a>@else<span class="crumb">Documents</span>@endif
            <i class="fas fa-chevron-right sep" aria-hidden="true"></i>
            <span class="current" aria-current="page">Recycle bin</span>
        </nav>

        <div class="dl-content">
        @if($items->isEmpty())
            <div class="dl-empty">
                <div class="dl-empty-art"><i class="fas fa-folder-open"></i><i class="fas fa-file-word"></i></div>
                <strong>The recycle bin is empty</strong>
                <span>Deleted files and folders show up here, ready to restore.</span>
            </div>
        @else
            <table class="dl-list">
                <thead><tr>
                    <th class="col-icon"></th>
                    <th class="col-name">Name</th>
                    <th class="col-modified">Deleted</th>
                    <th class="col-by">Deleted by</th>
                    <th class="col-size">Size</th>
                    <th class="col-site">Original location</th>
                    <th class="col-actions" style="width:96px"><span class="sr-only">Actions</span></th>
                </tr></thead>
                <tbody>
                @foreach($items as $row)
                    @php
                        $isFolder = $row->type === 'folder';
                        [$fi, $fc] = $isFolder ? ['fa-folder', 'folder'] : $row->item->iconInfo();
                    @endphp
                    <tr>
                        <td class="col-icon"><span class="dl-icon {{ $fc }}"><i class="fas {{ $fi }}"></i></span></td>
                        <td class="col-name">
                            <span class="dl-name"><span>{{ $row->item->name }}</span></span>
                            <span class="dl-sub">{{ $isFolder ? $row->files.' '.Str::plural('file', $row->files).' inside · ' : '' }}{{ $row->days_left }} {{ Str::plural('day', $row->days_left) }} left</span>
                        </td>
                        <td class="col-modified" title="{{ $row->item->deleted_at->format('d M Y H:i') }}">{{ $row->item->deleted_at->diffForHumans() }}</td>
                        <td class="col-by">{{ $row->item->deleter?->name ?? '—' }}</td>
                        <td class="col-size">{{ DocumentFile::formatBytes($row->bytes) }}</td>
                        <td class="col-site dl-muted">{{ $row->location ?? 'Documents' }}</td>
                        <td class="col-actions" style="overflow:visible">
                            <div style="display:flex;gap:4px;justify-content:flex-end">
                                <form method="POST" action="{{ route('staff.documents.recycle-bin.restore', [$row->type, $row->item->id]) }}" style="margin:0">
                                    @csrf
                                    <button class="dl-x" style="color:var(--dl-accent)" title="Restore" aria-label="Restore {{ $row->item->name }}"><i class="fas fa-rotate-left"></i></button>
                                </form>
                                <form method="POST" action="{{ route('staff.documents.recycle-bin.purge', [$row->type, $row->item->id]) }}" data-delete-form data-confirm="Permanently delete &quot;{{ $row->item->name }}&quot;{{ $isFolder ? ' and everything inside it' : '' }}? This cannot be undone." style="margin:0">
                                    @csrf @method('DELETE')
                                    <button class="dl-x" title="Delete permanently" aria-label="Delete {{ $row->item->name }} permanently"><i class="fas fa-trash-can"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        @endif
        </div>
    </div>
</div>
@endsection
