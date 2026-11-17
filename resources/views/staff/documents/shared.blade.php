@extends('layouts.admin')
@section('title','Shared with me | Documents')
@section('content')
@php use App\Models\DocumentShare; use App\Models\DocumentFile; @endphp

<div class="dl-shell">
    @include('staff.documents._rail', ['active' => 'shared'])

    <div class="dl-main">
        <header class="dl-site-header">
            <span class="dl-site-tile lg" style="--tile:#0f6cbd"><i class="fas fa-user-group"></i></span>
            <div>
                <h1>Shared with me</h1>
                <p>Folders and files colleagues in other departments have shared with you.</p>
            </div>
        </header>

        <div class="dl-content" style="padding-top:10px">
        @if($items->isEmpty())
            <div class="dl-empty">
                <div class="dl-empty-art"><i class="fas fa-folder-open"></i><i class="fas fa-file-word"></i></div>
                <strong>Nothing has been shared with you yet</strong>
                <span>When someone shares a folder or file with you, it appears here.</span>
            </div>
        @else
            <table class="dl-list">
                <thead><tr>
                    <th class="col-icon"></th><th class="col-name">Name</th><th class="col-modified">Shared</th><th class="col-by">Shared by</th><th class="col-sharing">Access</th><th class="col-more"></th>
                </tr></thead>
                <tbody>
                @foreach($items as $row)
                    @php
                        $isFolder = $row->type === DocumentShare::FOLDER;
                        [$fi, $fc] = $isFolder ? ['fa-folder', 'folder'] : $row->item->iconInfo();
                        $href = $isFolder
                            ? route('staff.documents.library', ['department' => $row->item->department_id, 'folder' => $row->item->id])
                            : route('staff.documents.open', $row->item);
                    @endphp
                    <tr>
                        <td class="col-icon"><span class="dl-icon {{ $fc }}"><i class="fas {{ $fi }}"></i></span></td>
                        <td class="col-name">
                            <a class="dl-name" href="{{ $href }}" @unless($isFolder) target="_blank" rel="noopener" @endunless><span>{{ $row->item->name }}</span></a>
                            <span class="dl-sub">{{ $row->item->department->name }}{{ ! $isFolder && $row->item->folder ? ' › '.$row->item->folder->name : '' }}</span>
                        </td>
                        <td class="col-modified">{{ $row->shared_at?->diffForHumans() }}</td>
                        <td class="col-by">{{ $row->shared_by ?? '—' }}</td>
                        <td class="col-sharing"><span class="dl-access-pill {{ $row->role }}">{{ DocumentShare::LABELS[$row->role] ?? '' }}</span></td>
                        <td class="col-more">
                            @unless($isFolder)
                                <a class="dl-more" style="opacity:1" href="{{ route('staff.documents.download', $row->item) }}" title="Download" aria-label="Download {{ $row->item->name }}"><i class="fas fa-download"></i></a>
                            @endunless
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
