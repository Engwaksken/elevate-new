@php use App\Support\DocumentUi; @endphp
<nav class="dl-rail" aria-label="Document sites">
    <a href="{{ route('staff.documents.index') }}" class="{{ $active === 'home' ? 'active' : '' }}"><i class="fas fa-house"></i><span>Home</span></a>
    <a href="{{ route('staff.documents.shared') }}" class="{{ $active === 'shared' ? 'active' : '' }}"><i class="fas fa-user-group"></i><span>Shared with me</span></a>

    <div class="dl-rail-title">Department sites</div>
    @forelse($sites as $site)
        <a href="{{ route('staff.documents.library', $site) }}" class="{{ $active === $site->id ? 'active' : '' }}" title="{{ $site->name }}">
            <span class="dl-site-tile" style="--tile:{{ DocumentUi::colour($site->id) }}">{{ DocumentUi::initials($site->name) }}</span>
            <span>{{ $site->name }}</span>
        </a>
    @empty
        <p class="dl-rail-empty">You're not in a department yet. Items shared with you appear under Shared with me.</p>
    @endforelse
</nav>
