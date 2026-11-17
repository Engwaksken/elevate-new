@php
    use App\Models\DocumentShare;
    use App\Models\DocumentFile;
    use App\Models\Department;
    use App\Models\DocumentFolder;
    use App\Services\DocumentAccessService as Access;
    use App\Support\DocumentUi;

    $item = $panel['item'];
    $key = $panel['itemKey'];
    $isSite = $item instanceof Department;
    $isFile = $item instanceof DocumentFile;
    $canManage = Access::allows($panel['role'], DocumentShare::FULL);
    [$pi, $pc] = $isSite ? ['fa-building', 'folder'] : ($isFile ? $item->iconInfo() : ['fa-folder', 'folder']);
    $title = $isSite ? $item->name.' site' : $item->name;
    $avatar = fn ($u) => '<span class="dl-avatar" style="--av:'.DocumentUi::colour((int) $u?->id).'">'.e(DocumentUi::initials($u?->name)).'</span>';
    $roleIcon = [DocumentShare::VIEW => 'fa-eye', DocumentShare::EDIT => 'fa-pen', DocumentShare::FULL => 'fa-key'];
@endphp

<aside class="dl-panel" aria-label="Details pane">
    <div class="dl-panel-head">
        @if($isSite)
            <span class="dl-site-tile md" style="--tile:{{ DocumentUi::colour($item->id) }}">{{ DocumentUi::initials($item->name) }}</span>
        @else
            <span class="dl-icon {{ $pc }}"><i class="fas {{ $pi }}"></i></span>
        @endif
        <div style="min-width:0">
            <h2>{{ $title }}</h2>
            <p>Your access: {{ DocumentShare::LABELS[$panel['role']] ?? '' }}</p>
        </div>
        <a href="{{ $closeUrl }}" class="dl-panel-close" aria-label="Close details pane"><i class="fas fa-xmark"></i></a>
    </div>

    <nav class="dl-panel-tabs">
        <a href="{{ $panelUrl('details', $key) }}" class="{{ $panel['type'] === 'details' ? 'active' : '' }}">Details</a>
        <a href="{{ $panelUrl('access', $key) }}" class="{{ $panel['type'] === 'access' ? 'active' : '' }}">Access</a>
        @if($isFile)<a href="{{ $panelUrl('versions', $key) }}" class="{{ $panel['type'] === 'versions' ? 'active' : '' }}">Version history</a>@endif
    </nav>

    {{-- ================= Details ================= --}}
    @if($panel['type'] === 'details')
        @if($isFile)
            <div class="dl-panel-section" style="display:flex;gap:8px;flex-wrap:wrap">
                <a href="{{ route('staff.documents.open', $item) }}" target="_blank" rel="noopener" class="btn btn-primary btn-sm"><i class="fas fa-up-right-from-square"></i> Open</a>
                <a href="{{ route('staff.documents.download', $item) }}" class="btn btn-outline btn-sm"><i class="fas fa-download"></i> Download</a>
            </div>
        @endif
        <div class="dl-panel-section">
            <h3>Properties</h3>
            <dl class="dl-facts">
                @if($isSite)
                    <dt>Department</dt><dd>{{ $item->name }}</dd>
                    @if($item->code)<dt>Code</dt><dd>{{ $item->code }}</dd>@endif
                    <dt>Owner</dt><dd>{{ $item->head?->name ?? 'No department head set' }}</dd>
                    <dt>Folders</dt><dd>{{ number_format($item->documentFolders()->count()) }}</dd>
                    <dt>Files</dt><dd>{{ number_format($item->documentFiles()->count()) }} · {{ DocumentFile::formatBytes((int) $item->documentFiles()->sum('size')) }}</dd>
                @else
                    <dt>Type</dt><dd>{{ $isFile ? strtoupper($item->extension ?: 'File').' file' : 'Folder' }}</dd>
                    @if($isFile)
                        <dt>Size</dt><dd>{{ $item->humanSize() }}</dd>
                        <dt>Version</dt><dd>{{ $item->version }}</dd>
                    @else
                        <dt>Contains</dt><dd>{{ $item->children()->count() }} folders, {{ $item->files()->count() }} files</dd>
                        @if($item->description)<dt>Description</dt><dd>{{ $item->description }}</dd>@endif
                    @endif
                    <dt>Location</dt><dd>{{ $item->department->name }} › Documents{{ $isFile ? ($item->folder ? ' › '.$item->folder->name : '') : ($item->parent ? ' › '.$item->parent->name : '') }}</dd>
                    <dt>Created</dt><dd>{{ $item->created_at?->format('d M Y H:i') }}<br><span class="dl-muted">by {{ $item->creator?->name ?? '—' }}</span></dd>
                    <dt>Modified</dt><dd>{{ $item->updated_at?->format('d M Y H:i') }}<br><span class="dl-muted">by {{ $item->editor?->name ?? '—' }}</span></dd>
                @endif
            </dl>
        </div>
        <div class="dl-panel-section">
            <h3>Has access <small><a href="{{ $panelUrl('access', $key) }}">{{ $canManage ? 'Manage access' : 'See all' }}</a></small></h3>
            @php $people = collect([$panel['owner']])->filter()->merge($panel['members'])->merge($panel['inherited']->pluck('user'))->merge($panel['direct']->pluck('user'))->filter()->unique('id'); @endphp
            <div style="display:flex;flex-wrap:wrap;gap:4px">
                @foreach($people->take(14) as $person)
                    <span title="{{ $person->name }}">{!! $avatar($person) !!}</span>
                @endforeach
                @if($people->count() > 14)<span class="dl-avatar" style="--av:#8a8886">+{{ $people->count() - 14 }}</span>@endif
            </div>
        </div>
    @endif

    {{-- ================= Access ================= --}}
    @if($panel['type'] === 'access')
        @if($canManage)
        <div class="dl-panel-section" id="grant">
            <h3><i class="fas fa-share-nodes"></i> Grant access</h3>
            <form method="POST" action="{{ route('staff.documents.share', [$panel['itemType'], $item->id]) }}" class="dl-grant" data-dl-grant>
                @csrf
                @error('user_ids')<div class="icm-notice" role="alert"><span>{{ $message }}</span></div>@enderror
                <input type="search" class="dl-people-filter" placeholder="Search people by name or email" data-dl-people-filter aria-label="Search people">
                <div class="dl-people" data-dl-people>
                    @foreach($panel['staff'] ?? [] as $person)
                        <label data-search="{{ mb_strtolower($person->name.' '.$person->email) }}">
                            <input type="checkbox" name="user_ids[]" value="{{ $person->id }}" @checked(in_array($person->id, (array) old('user_ids', [])))>
                            {!! $avatar($person) !!}
                            <span>{{ $person->name }}<small>{{ $person->email }}</small></span>
                        </label>
                    @endforeach
                </div>
                <span class="dl-chosen" data-dl-chosen>No one selected</span>
                <div class="dl-roles">
                    @foreach(DocumentShare::LABELS as $value => $label)
                        <label>
                            <input type="radio" name="role" value="{{ $value }}" @checked(old('role', DocumentShare::VIEW) === $value)>
                            <span><strong><i class="fas {{ $roleIcon[$value] }}"></i> {{ $label }}</strong><small>{{ DocumentShare::DESCRIPTIONS[$value] }}</small></span>
                        </label>
                    @endforeach
                </div>
                <textarea name="message" rows="2" maxlength="500" placeholder="Add a message (optional)">{{ old('message') }}</textarea>
                <label style="display:flex;gap:8px;align-items:center;font-size:.76rem;margin:0"><input type="checkbox" name="notify" value="1" checked style="width:15px;height:15px;min-height:15px;margin:0"> Notify people</label>
                <button class="btn btn-primary"><i class="fas fa-paper-plane"></i> Grant access</button>
            </form>
        </div>
        @endif

        {{-- Direct shares on this item --}}
        <div class="dl-panel-section">
            <h3>Shared directly <small>{{ $panel['direct']->count() }}</small></h3>
            @forelse($panel['direct'] as $share)
                <div class="dl-person">
                    {!! $avatar($share->user) !!}
                    <div class="dl-person-info">
                        <strong>{{ $share->user?->name }}</strong>
                        <small>{{ $share->grantor ? 'Shared by '.$share->grantor->name.' · ' : '' }}{{ $share->updated_at?->format('d M Y') }}</small>
                    </div>
                    @if($canManage)
                        <form method="POST" action="{{ route('staff.documents.shares.update', $share) }}">
                            @csrf @method('PUT')
                            <select name="role" onchange="this.form.submit()" aria-label="Access for {{ $share->user?->name }}">
                                @foreach(DocumentShare::LABELS as $value => $label)
                                    <option value="{{ $value }}" @selected($share->role === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </form>
                        <form method="POST" action="{{ route('staff.documents.shares.destroy', $share) }}" data-delete-form data-confirm="Stop sharing with {{ $share->user?->name }}?">
                            @csrf @method('DELETE')
                            <button class="dl-x" title="Remove access" aria-label="Remove access for {{ $share->user?->name }}"><i class="fas fa-xmark"></i></button>
                        </form>
                    @else
                        <span class="dl-access-pill {{ $share->role }}">{{ $share->label() }}</span>
                    @endif
                </div>
            @empty
                <p class="dl-muted" style="margin:0;font-size:.76rem">Not shared with anyone individually{{ $canManage ? '. Use Grant access above.' : '.' }}</p>
            @endforelse
        </div>

        {{-- Inherited from site / parent folders --}}
        @if($panel['inherited']->isNotEmpty())
        <div class="dl-panel-section">
            <h3>Inherited <small>{{ $panel['inherited']->count() }}</small></h3>
            @foreach($panel['inherited'] as $share)
                <div class="dl-person">
                    {!! $avatar($share->user) !!}
                    <div class="dl-person-info">
                        <strong>{{ $share->user?->name }}</strong>
                        <small>From {{ $share->source_folder ? 'folder “'.$share->source_label.'”' : $share->source_label }}</small>
                    </div>
                    <span class="dl-access-pill {{ $share->role }}">{{ $share->label() }}</span>
                </div>
            @endforeach
        </div>
        @endif

        {{-- Site groups: owner + members --}}
        <div class="dl-panel-section">
            <h3>Site groups</h3>
            <div class="dl-person">
                <span class="dl-avatar" style="--av:#800000"><i class="fas fa-key"></i></span>
                <div class="dl-person-info">
                    <strong>Owners</strong>
                    <small>{{ $panel['owner']?->name ?? 'No department head' }} · document administrators</small>
                </div>
                <span class="dl-access-pill full">Full control</span>
            </div>
            <details>
                <summary class="dl-group-summary">
                    <span class="dl-avatar" style="--av:#0f6cbd"><i class="fas fa-users"></i></span>
                    <div class="dl-person-info">
                        <strong>Members · {{ $panel['members']->count() }}</strong>
                        <small>Everyone in {{ $isSite ? $item->name : $item->department->name }} (from HR records)</small>
                    </div>
                    <span class="dl-access-pill edit">Can edit</span>
                </summary>
                <div class="dl-group-list">
                    @forelse($panel['members'] as $member)
                        <div class="dl-person">{!! $avatar($member) !!}<div class="dl-person-info"><strong>{{ $member->name }}</strong><small>{{ $member->email }}</small></div></div>
                    @empty
                        <p class="dl-muted" style="font-size:.74rem">No staff are assigned to this department yet.</p>
                    @endforelse
                </div>
            </details>
            <p class="dl-note" style="margin-top:10px">Access granted on a site or folder carries down to everything inside it. Membership follows each employee's department in HR.</p>
        </div>
    @endif

    {{-- ================= Versions ================= --}}
    @if($panel['type'] === 'versions')
        @php $canEditFile = Access::allows($panel['role'], DocumentShare::EDIT); @endphp
        @if($canEditFile)
        <div class="dl-panel-section">
            <button type="button" class="btn btn-outline btn-sm" data-dl-action="version" data-url="{{ route('staff.documents.versions.store', $item) }}" data-name="{{ $item->name }}"><i class="fas fa-file-arrow-up"></i> Upload new version</button>
        </div>
        @endif
        <div class="dl-panel-section">
            <h3>Versions <small>{{ $panel['versions']->count() }}</small></h3>
            <ul class="dl-versions">
                @foreach($panel['versions'] as $version)
                    @php $current = $version->version === $item->version; @endphp
                    <li class="{{ $current ? 'current' : '' }}">
                        <span class="dl-v">{{ $version->version }}.0</span>
                        <div>
                            <strong>{{ $version->created_at?->format('d M Y H:i') }}{{ $current ? ' · Current' : '' }}</strong>
                            <small>{{ $version->uploader?->name ?? '—' }} · {{ DocumentFile::formatBytes($version->size) }}</small>
                            @if($version->comment)<em>“{{ $version->comment }}”</em>@endif
                        </div>
                        <div class="dl-v-actions">
                            <a class="dl-x" style="color:var(--dl-accent)" href="{{ route('staff.documents.versions.download', $version) }}" title="Download version {{ $version->version }}" aria-label="Download version {{ $version->version }}"><i class="fas fa-download"></i></a>
                            @if($canEditFile && ! $current)
                                <form method="POST" action="{{ route('staff.documents.versions.restore', $version) }}" data-delete-form data-confirm="Restore version {{ $version->version }}? It becomes the newest version; nothing is lost.">
                                    @csrf
                                    <button class="dl-x" style="color:var(--dl-accent)" title="Restore this version" aria-label="Restore version {{ $version->version }}"><i class="fas fa-rotate-left"></i></button>
                                </form>
                            @endif
                        </div>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif
</aside>
