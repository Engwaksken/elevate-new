@props([
    'heading' => 'How will you use ElevateHer360?',
    'intro' => 'Choose your path to sign in or create an account.',
])
@php
    $rcUser = auth()->user();
    $rcRole = \App\Support\RoleShell::roleFor($rcUser);
    $rcHome = \App\Support\RoleShell::homeUrl($rcUser);
@endphp
<section {{ $attributes->class(['role-chooser']) }} id="get-started" aria-labelledby="role-chooser-title">
    <div class="container">
        <div class="role-chooser__head">
            <span class="eyebrow">Get started</span>
            <h2 id="role-chooser-title">{{ $heading }}</h2>
            <p>{{ $intro }}</p>
        </div>

        @auth
            <div class="role-chooser__signed-in">
                <div>
                    @if($rcRole)<x-role-badge :role="$rcRole" />@endif
                    <p>You are signed in as <strong>{{ $rcUser->name ?? $rcUser->email }}</strong>.</p>
                </div>
                @if($rcHome)
                    <a href="{{ $rcHome }}" class="btn btn-primary"><i class="fas fa-gauge-high" aria-hidden="true"></i> Continue to your dashboard</a>
                @endif
            </div>
        @else
            <ul class="role-chooser__grid" role="list">
                @foreach(\App\Support\RoleShell::ROLES as $roleKey => $roleMeta)
                    @php
                        $rcLogin = \App\Support\RoleShell::url($roleMeta['login']);
                        $rcJoin = \App\Support\RoleShell::url($roleMeta['join']);
                    @endphp
                    <li class="role-card role-card--{{ $roleKey }}">
                        <span class="role-card__icon" aria-hidden="true"><i class="fas {{ $roleMeta['icon'] }}"></i></span>
                        <h3 class="role-card__title" id="role-card-{{ $roleKey }}">{{ $roleMeta['label'] }}</h3>
                        <p class="role-card__text">{{ $roleMeta['tagline'] }}</p>
                        <div class="role-card__actions">
                            @if($rcLogin)
                                <a href="{{ $rcLogin }}" class="btn btn-primary">
                                    <i class="fas fa-right-to-bracket" aria-hidden="true"></i> Sign in<span class="role-sr-only"> as {{ $roleMeta['label'] }}</span>
                                </a>
                            @endif
                            @if($rcJoin)
                                <a href="{{ $rcJoin }}" class="btn btn-outline">
                                    <i class="fas fa-user-plus" aria-hidden="true"></i> Join<span class="role-sr-only"> as {{ $roleMeta['label'] }}</span>
                                </a>
                            @endif
                        </div>
                    </li>
                @endforeach
            </ul>
        @endauth
    </div>
</section>
