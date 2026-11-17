@extends('layouts.admin')
@section('title', $department->name.' · Documents | ElevateHer360')
@section('content')
@php
    use App\Models\DocumentShare;
    use App\Services\DocumentAccessService as Access;
    use App\Support\DocumentUi;

    $canEdit = Access::allows($role, DocumentShare::EDIT);
    $canFull = Access::allows($role, DocumentShare::FULL);
    $siteFull = Access::allows($siteRole, DocumentShare::FULL);

    // Current location + display options, carried through every link.
    $here = array_filter([
        'department' => $department->id,
        'folder' => $folder?->id,
        'view' => $view !== 'list' ? $view : null,
        'sort' => $sort !== 'name' ? $sort : null,
        'dir' => $dir !== 'asc' ? $dir : null,
        'q' => $search !== '' ? $search : null,
    ]);
    $url = fn (array $changes = []) => route('staff.documents.library', array_filter(array_merge($here, $changes), fn ($v) => $v !== null && $v !== ''));
    $panelUrl = fn (string $panel, string $key) => $url(['panel' => $panel, 'item' => $key]);
    $sortUrl = fn (string $col) => $url(['sort' => $col === 'name' ? null : $col, 'dir' => ($sort === $col && $dir === 'asc') ? 'desc' : null]);
    $sortIcon = fn (string $col) => $sort === $col ? '<i class="fas fa-arrow-'.($dir === 'asc' ? 'up' : 'down').'"></i>' : '';

    /** Actions a row offers, by the viewer's role on that row. */
    $actionsFor = function ($item, bool $isFolder) use ($url, $panelUrl) {
        $type = $isFolder ? 'folder' : 'file';
        $key = $type.':'.$item->id;
        $r = $item->row_role;
        $a = [];
        if ($isFolder) {
            $a['open'] = ['Open', 'fa-folder-open', $url(['folder' => $item->id, 'q' => null]), 'link'];
        } else {
            $a['open'] = ['Open', 'fa-up-right-from-square', route('staff.documents.open', $item), 'blank'];
            $a['download'] = ['Download', 'fa-download', route('staff.documents.download', $item), 'link'];
        }
        if (Access::allows($r, DocumentShare::FULL)) $a['share'] = ['Share', 'fa-share-nodes', $panelUrl('access', $key).'#grant', 'link'];
        $a['access'] = ['Manage access', 'fa-users-gear', $panelUrl('access', $key), 'link'];
        $a['details'] = ['Details', 'fa-circle-info', $panelUrl('details', $key), 'link'];
        if (! $isFolder) $a['versions'] = ['Version history', 'fa-clock-rotate-left', $panelUrl('versions', $key), 'link'];
        if (Access::allows($r, DocumentShare::EDIT)) {
            if (! $isFolder) $a['newversion'] = ['Upload new version', 'fa-file-arrow-up', route('staff.documents.versions.store', $item), 'version'];
            $a['rename'] = ['Rename', 'fa-i-cursor', route('staff.documents.rename', [$type, $item->id]), 'rename'];
        }
        if (Access::allows($r, DocumentShare::FULL)) $a['delete'] = ['Delete', 'fa-trash-can', route('staff.documents.destroy', [$type, $item->id]), 'delete'];

        return $a;
    };
    $renameValue = fn ($item, bool $isFolder) => $isFolder || ! $item->extension ? $item->name : \Illuminate\Support\Str::beforeLast($item->name, '.'.$item->extension);
    $total = $folders->count() + $files->count();
@endphp

<div class="dl-shell {{ $panel ? 'has-panel' : '' }}">
    @include('staff.documents._rail', ['active' => $department->id])

    <div class="dl-main">
        {{-- Site header --}}
        <header class="dl-site-header">
            <span class="dl-site-tile lg" style="--tile:{{ DocumentUi::colour($department->id) }}">{{ DocumentUi::initials($department->name) }}</span>
            <div>
                <h1>{{ $department->name }}</h1>
                <p>
                    <span><i class="fas fa-building"></i> Department site</span>
                    <span><i class="fas fa-users"></i> {{ $memberCount }} {{ Str::plural('member', $memberCount) }}</span>
                    @if($department->head)<span><i class="fas fa-user-tie"></i> Owner: {{ $department->head->name }}</span>@endif
                    <span class="dl-access-pill {{ $role }}"><i class="fas {{ $role === 'view' ? 'fa-eye' : ($role === 'edit' ? 'fa-pen' : 'fa-key') }}"></i> {{ DocumentShare::LABELS[$role] }}</span>
                </p>
            </div>
            <div class="dl-site-header-actions">
                @if($siteRole)
                    <a href="{{ $panelUrl('access', 'site:'.$department->id) }}" class="btn btn-outline"><i class="fas fa-users-gear"></i> Site access</a>
                @endif
                @if($siteFull)
                    <a href="{{ $panelUrl('access', 'site:'.$department->id) }}#grant" class="btn btn-primary"><i class="fas fa-share-nodes"></i> Share site</a>
                @endif
            </div>
        </header>

        {{-- Command bar --}}
        <div class="dl-commandbar" data-dl-commandbar>
            <div data-dl-default-cmds style="display:contents">
                @if($canEdit)
                <details class="dl-dropdown">
                    <summary class="dl-cmd primary"><i class="fas fa-plus"></i><span class="dl-cmd-label">New</span> <i class="fas fa-chevron-down" style="font-size:.6rem"></i></summary>
                    <div class="dl-menu">
                        <button type="button" data-modal-open="dl-new-folder"><i class="fas fa-folder-plus"></i> Folder</button>
                        <button type="button" data-dl-upload-trigger><i class="fas fa-file-arrow-up"></i> Upload files</button>
                    </div>
                </details>
                <button type="button" class="dl-cmd" data-dl-upload-trigger><i class="fas fa-arrow-up-from-bracket"></i><span class="dl-cmd-label">Upload</span></button>
                @endif
                @if($folder && $canFull)
                    <a class="dl-cmd" href="{{ $panelUrl('access', 'folder:'.$folder->id) }}#grant"><i class="fas fa-share-nodes"></i><span class="dl-cmd-label">Share folder</span></a>
                @endif
                @if($folder)
                    <a class="dl-cmd" href="{{ $panelUrl('details', 'folder:'.$folder->id) }}"><i class="fas fa-circle-info"></i><span class="dl-cmd-label">Folder details</span></a>
                @endif
            </div>

            {{-- Filled by JS from the selected row's actions --}}
            <div data-dl-selection-cmds hidden style="display:contents"></div>

            <div class="dl-cmd-right">
                <form method="GET" action="{{ route('staff.documents.library', $department) }}" class="dl-search" role="search">
                    @if($folder)<input type="hidden" name="folder" value="{{ $folder->id }}">@endif
                    @if($view !== 'list')<input type="hidden" name="view" value="{{ $view }}">@endif
                    <i class="fas fa-magnifying-glass"></i>
                    <input type="search" name="q" value="{{ $search }}" placeholder="Search {{ $folder ? 'this folder' : 'this library' }}" aria-label="Search files">
                </form>
                <details class="dl-dropdown">
                    <summary class="dl-cmd" title="Sort"><i class="fas fa-arrow-down-wide-short"></i><span class="dl-cmd-label">Sort</span></summary>
                    <div class="dl-menu right">
                        <a href="{{ $url(['sort' => null, 'dir' => null]) }}" class="{{ $sort === 'name' && $dir === 'asc' ? 'is-current' : '' }}"><i class="fas fa-arrow-down-a-z"></i> Name A to Z</a>
                        <a href="{{ $url(['sort' => null, 'dir' => 'desc']) }}" class="{{ $sort === 'name' && $dir === 'desc' ? 'is-current' : '' }}"><i class="fas fa-arrow-down-z-a"></i> Name Z to A</a>
                        <a href="{{ $url(['sort' => 'modified', 'dir' => 'desc']) }}" class="{{ $sort === 'modified' && $dir === 'desc' ? 'is-current' : '' }}"><i class="fas fa-clock"></i> Newest first</a>
                        <a href="{{ $url(['sort' => 'modified', 'dir' => null]) }}" class="{{ $sort === 'modified' && $dir === 'asc' ? 'is-current' : '' }}"><i class="fas fa-clock-rotate-left"></i> Oldest first</a>
                        <a href="{{ $url(['sort' => 'size', 'dir' => 'desc']) }}" class="{{ $sort === 'size' && $dir === 'desc' ? 'is-current' : '' }}"><i class="fas fa-weight-hanging"></i> Largest first</a>
                    </div>
                </details>
                <details class="dl-dropdown">
                    <summary class="dl-cmd" title="Switch view"><i class="fas {{ $view === 'tiles' ? 'fa-table-cells-large' : 'fa-list' }}"></i><span class="dl-cmd-label">{{ $view === 'tiles' ? 'Tiles' : 'List' }}</span></summary>
                    <div class="dl-menu right">
                        <a href="{{ $url(['view' => null]) }}" class="{{ $view === 'list' ? 'is-current' : '' }}"><i class="fas fa-list"></i> List</a>
                        <a href="{{ $url(['view' => 'tiles']) }}" class="{{ $view === 'tiles' ? 'is-current' : '' }}"><i class="fas fa-table-cells-large"></i> Tiles</a>
                    </div>
                </details>
                <a class="dl-cmd" href="{{ $panel ? $url() : $panelUrl('details', $folder ? 'folder:'.$folder->id : 'site:'.$department->id) }}" title="{{ $panel ? 'Close details pane' : 'Open details pane' }}" aria-label="Toggle details pane"><i class="fas fa-circle-info"></i></a>
            </div>
        </div>

        {{-- Breadcrumb --}}
        <nav class="dl-breadcrumb" aria-label="Folder path">
            @foreach($crumbs as $i => $crumb)
                @if($i > 0)<i class="fas fa-chevron-right sep" aria-hidden="true"></i>@endif
                @if($loop->last && $search === '')
                    <span class="current" aria-current="page">{{ $crumb['label'] }}</span>
                @elseif($crumb['linked'])
                    <a href="{{ $url(['folder' => $crumb['folder'], 'q' => null]) }}">{{ $crumb['label'] }}</a>
                @else
                    <span class="crumb">{{ $crumb['label'] }}</span>
                @endif
            @endforeach
            @if($search !== '')
                <i class="fas fa-chevron-right sep" aria-hidden="true"></i><span class="current">Search results</span>
            @endif
        </nav>
        @if($search !== '')
            <p class="dl-search-note">{{ $total }} {{ Str::plural('result', $total) }} for “{{ $search }}” · <a href="{{ $url(['q' => null]) }}">Clear search</a></p>
        @endif

        {{-- Items --}}
        <div class="dl-content" @if($canEdit) data-dl-dropzone @endif>
            @if($total === 0)
                <div class="dl-empty">
                    <div class="dl-empty-art"><i class="fas fa-folder-open"></i><i class="fas fa-file-word"></i></div>
                    @if($search !== '')
                        <strong>No matches</strong><span>Nothing here is named like “{{ $search }}”.</span>
                    @elseif($canEdit)
                        <strong>Drag files here</strong>
                        <span>Or use <b>New</b> to add a folder, or <b>Upload</b> to choose files.</span>
                    @else
                        <strong>This folder is empty</strong>
                    @endif
                </div>
            @elseif($view === 'tiles')
                <div class="dl-tiles" data-dl-items>
                    @foreach($folders as $item)
                        @php $actions = $actionsFor($item, true); @endphp
                        <div class="dl-tile" data-dl-row data-dl-item="{{ json_encode(['name' => $item->name, 'type' => 'folder', 'rename' => $renameValue($item, true), 'actions' => $actions]) }}">
                            <input type="checkbox" class="dl-check dl-tile-check" data-dl-select aria-label="Select {{ $item->name }}">
                            <div class="dl-tile-more">@include('staff.documents._row-menu', ['actions' => $actions, 'item' => $item, 'rename' => $renameValue($item, true)])</div>
                            <a class="dl-tile-thumb" href="{{ $actions['open'][2] }}">
                                <span class="dl-icon folder"><i class="fas fa-folder"></i></span>
                                @if(isset($item->files_count))<span class="dl-tile-folder-count">{{ $item->files_count + $item->children_count }}</span>@endif
                            </a>
                            <div class="dl-tile-body">
                                <a href="{{ $actions['open'][2] }}" title="{{ $item->name }}">{{ $item->name }}</a>
                                <small>{{ $item->updated_at?->format('d M Y') }}@if($item->is_shared) · <i class="fas fa-user-group"></i> Shared @endif</small>
                            </div>
                        </div>
                    @endforeach
                    @foreach($files as $item)
                        @php $actions = $actionsFor($item, false); [$fi, $fc] = $item->iconInfo(); $isImage = $fc === 'image' && $item->canOpenInline(); @endphp
                        <div class="dl-tile" data-dl-row data-dl-item="{{ json_encode(['name' => $item->name, 'type' => 'file', 'rename' => $renameValue($item, false), 'actions' => $actions]) }}">
                            <input type="checkbox" class="dl-check dl-tile-check" data-dl-select aria-label="Select {{ $item->name }}">
                            <div class="dl-tile-more">@include('staff.documents._row-menu', ['actions' => $actions, 'item' => $item, 'rename' => $renameValue($item, false)])</div>
                            <a class="dl-tile-thumb" href="{{ $actions['open'][2] }}" target="_blank" rel="noopener">
                                @if($isImage)
                                    <img src="{{ route('staff.documents.open', $item) }}?v={{ $item->version }}" alt="" loading="lazy">
                                @else
                                    <span class="dl-icon {{ $fc }}"><i class="fas {{ $fi }}"></i></span>
                                @endif
                            </a>
                            <div class="dl-tile-body">
                                <a href="{{ $actions['open'][2] }}" target="_blank" rel="noopener" title="{{ $item->name }}">{{ $item->name }}</a>
                                <small>{{ $item->updated_at?->format('d M Y') }} · {{ $item->humanSize() }}@if($item->is_shared) · <i class="fas fa-user-group"></i>@endif</small>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <table class="dl-list" data-dl-items>
                    <thead><tr>
                        <th class="col-check"><input type="checkbox" class="dl-check" data-dl-select-all aria-label="Select all"></th>
                        <th class="col-icon"><i class="fas fa-file" aria-hidden="true"></i><span class="sr-only">Type</span></th>
                        <th class="col-name"><a href="{{ $sortUrl('name') }}">Name {!! $sortIcon('name') !!}</a></th>
                        <th class="col-modified"><a href="{{ $sortUrl('modified') }}">Modified {!! $sortIcon('modified') !!}</a></th>
                        <th class="col-by">Modified by</th>
                        <th class="col-size"><a href="{{ $sortUrl('size') }}">File size {!! $sortIcon('size') !!}</a></th>
                        <th class="col-sharing">Sharing</th>
                        <th class="col-more"><span class="sr-only">Actions</span></th>
                    </tr></thead>
                    <tbody>
                    @foreach($folders as $item)
                        @php $actions = $actionsFor($item, true); @endphp
                        <tr data-dl-row data-dl-item="{{ json_encode(['name' => $item->name, 'type' => 'folder', 'rename' => $renameValue($item, true), 'actions' => $actions]) }}">
                            <td class="col-check"><input type="checkbox" class="dl-check" data-dl-select aria-label="Select {{ $item->name }}"></td>
                            <td class="col-icon"><span class="dl-icon folder"><i class="fas fa-folder"></i></span></td>
                            <td class="col-name">
                                <a class="dl-name" href="{{ $actions['open'][2] }}"><span>{{ $item->name }}</span></a>
                                @if($search !== '' && $item->parent)<span class="dl-sub">in {{ $item->parent->name }}</span>@endif
                            </td>
                            <td class="col-modified" title="{{ $item->updated_at?->format('d M Y H:i') }}">{{ $item->updated_at?->diffForHumans() }}</td>
                            <td class="col-by">{{ $item->editor?->name ?? '—' }}</td>
                            <td class="col-size dl-muted">@isset($item->files_count){{ $item->files_count + $item->children_count }} {{ Str::plural('item', $item->files_count + $item->children_count) }}@endisset</td>
                            <td class="col-sharing">@if($item->is_shared)<span class="dl-shared"><i class="fas fa-user-group"></i> Shared</span>@else<span class="dl-muted">Private</span>@endif</td>
                            <td class="col-more">@include('staff.documents._row-menu', ['actions' => $actions, 'item' => $item, 'rename' => $renameValue($item, true)])</td>
                        </tr>
                    @endforeach
                    @foreach($files as $item)
                        @php $actions = $actionsFor($item, false); [$fi, $fc] = $item->iconInfo(); @endphp
                        <tr data-dl-row data-dl-item="{{ json_encode(['name' => $item->name, 'type' => 'file', 'rename' => $renameValue($item, false), 'actions' => $actions]) }}">
                            <td class="col-check"><input type="checkbox" class="dl-check" data-dl-select aria-label="Select {{ $item->name }}"></td>
                            <td class="col-icon"><span class="dl-icon {{ $fc }}"><i class="fas {{ $fi }}"></i></span></td>
                            <td class="col-name">
                                <a class="dl-name" href="{{ $actions['open'][2] }}" target="_blank" rel="noopener"><span>{{ $item->name }}</span></a>
                                @if($item->version > 1)<a class="dl-version-tag" href="{{ $actions['versions'][2] }}" title="Version history">v{{ $item->version }}</a>@endif
                                @if($search !== '' && $item->folder)<span class="dl-sub">in {{ $item->folder->name }}</span>@endif
                            </td>
                            <td class="col-modified" title="{{ $item->updated_at?->format('d M Y H:i') }}">{{ $item->updated_at?->diffForHumans() }}</td>
                            <td class="col-by">{{ $item->editor?->name ?? '—' }}</td>
                            <td class="col-size">{{ $item->humanSize() }}</td>
                            <td class="col-sharing">@if($item->is_shared)<span class="dl-shared"><i class="fas fa-user-group"></i> Shared</span>@else<span class="dl-muted">Private</span>@endif</td>
                            <td class="col-more">@include('staff.documents._row-menu', ['actions' => $actions, 'item' => $item, 'rename' => $renameValue($item, false)])</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>

    @if($panel)
        @include('staff.documents._panel', ['panel' => $panel, 'closeUrl' => $url(), 'panelUrl' => $panelUrl])
    @endif
</div>

{{-- Hidden upload form: Upload button, New › Upload files and drag-and-drop all feed it --}}
@if($canEdit)
<form method="POST" action="{{ route('staff.documents.upload', $department) }}" enctype="multipart/form-data" data-dl-upload-form hidden>
    @csrf
    @if($folder)<input type="hidden" name="folder_id" value="{{ $folder->id }}">@endif
    <input type="file" name="files[]" multiple data-dl-upload-input>
</form>

<div class="eh-modal" id="dl-new-folder" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="dl-new-folder-title" @if($errors->has('name') && old('_form') === 'folder') data-modal-autoopen @endif>
<div class="eh-modal-dialog eh-modal-sm">
<form method="POST" action="{{ route('staff.documents.folders.store', $department) }}">
    @csrf
    <input type="hidden" name="_form" value="folder">
    @if($folder)<input type="hidden" name="folder_id" value="{{ $folder->id }}">@endif
    <div class="eh-modal-header">
        <div><h2 id="dl-new-folder-title">Create a folder</h2><p>In {{ $folder?->name ?? $department->name.' › Documents' }}</p></div>
        <button type="button" class="eh-modal-close" data-modal-close aria-label="Close"><i class="fas fa-xmark"></i></button>
    </div>
    <div class="eh-modal-body">
        <div class="form-group">
            <label for="dl-folder-name">Folder name</label>
            <input id="dl-folder-name" name="name" value="{{ old('_form') === 'folder' ? old('name') : '' }}" maxlength="120" required placeholder="e.g. Policies 2026">
            @if(old('_form') === 'folder')@error('name')<small class="text-danger">{{ $message }}</small>@enderror @endif
        </div>
        <div class="form-group">
            <label for="dl-folder-desc">Description <span class="dl-muted">(optional)</span></label>
            <textarea id="dl-folder-desc" name="description" rows="2" maxlength="500"></textarea>
        </div>
    </div>
    <div class="eh-modal-footer">
        <button type="button" class="btn btn-outline" data-modal-close>Cancel</button>
        <button class="btn btn-primary"><i class="fas fa-folder-plus"></i> Create</button>
    </div>
</form>
</div>
</div>
@endif

{{-- Rename (any row the user can edit; form action set by JS) --}}
<div class="eh-modal" id="dl-rename" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="dl-rename-title">
<div class="eh-modal-dialog eh-modal-sm">
<form method="POST" action="" data-dl-rename-form>
    @csrf @method('PUT')
    <div class="eh-modal-header">
        <div><h2 id="dl-rename-title">Rename</h2><p data-dl-rename-label></p></div>
        <button type="button" class="eh-modal-close" data-modal-close aria-label="Close"><i class="fas fa-xmark"></i></button>
    </div>
    <div class="eh-modal-body">
        <div class="form-group">
            <label for="dl-rename-name">New name</label>
            <input id="dl-rename-name" name="name" maxlength="180" required data-dl-rename-input>
            <small class="dl-muted">A file keeps its extension.</small>
        </div>
    </div>
    <div class="eh-modal-footer">
        <button type="button" class="btn btn-outline" data-modal-close>Cancel</button>
        <button class="btn btn-primary">Rename</button>
    </div>
</form>
</div>
</div>

{{-- Upload a new version --}}
<div class="eh-modal" id="dl-new-version" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="dl-new-version-title">
<div class="eh-modal-dialog eh-modal-sm">
<form method="POST" action="" enctype="multipart/form-data" data-dl-version-form>
    @csrf
    <div class="eh-modal-header">
        <div><h2 id="dl-new-version-title">Upload a new version</h2><p data-dl-version-label></p></div>
        <button type="button" class="eh-modal-close" data-modal-close aria-label="Close"><i class="fas fa-xmark"></i></button>
    </div>
    <div class="eh-modal-body">
        <div class="form-group">
            <label for="dl-version-file">File</label>
            <input id="dl-version-file" type="file" name="file" required>
            <small class="dl-muted">Up to {{ $maxUploadMb }} MB. Earlier versions stay in Version history.</small>
        </div>
        <div class="form-group">
            <label for="dl-version-comment">What changed? <span class="dl-muted">(optional)</span></label>
            <input id="dl-version-comment" name="comment" maxlength="255" placeholder="e.g. Updated budget figures">
        </div>
    </div>
    <div class="eh-modal-footer">
        <button type="button" class="btn btn-outline" data-modal-close>Cancel</button>
        <button class="btn btn-primary"><i class="fas fa-file-arrow-up"></i> Upload version</button>
    </div>
</form>
</div>
</div>

<form method="POST" action="" data-dl-delete-form hidden>@csrf @method('DELETE')</form>
@endsection
