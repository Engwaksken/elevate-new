@extends(auth()->check() && method_exists(auth()->user(), 'isStaff') && auth()->user()->isStaff() ? 'layouts.admin' : 'layouts.app')

@section('title', 'My Growth Goals | ElevateHer360')

@section('content')
<style>
.mentorship-goals-page{
    display:flex;
    flex-direction:column;
    gap:18px;
}

.mentorship-form-card{
    background:#ffffff !important;
    border:1px solid #eadede;
    border-left:4px solid #800000;
    border-radius:14px;
    padding:20px;
    box-shadow:0 6px 18px rgba(16,24,40,.05);
}

.mentorship-form-grid{
    display:grid;
    grid-template-columns:repeat(2,minmax(0,1fr));
    gap:16px;
}

.mentorship-form-group{
    display:flex;
    flex-direction:column;
    gap:7px;
}

.mentorship-form-group.full{
    grid-column:1 / -1;
}

.mentorship-form-group label{
    color:#344054;
    font-size:.9rem;
    font-weight:700;
}

.mentorship-form-group input,
.mentorship-form-group textarea,
.mentorship-form-group select{
    box-sizing:border-box;
    width:100%;
    min-height:46px;
    padding:11px 13px;
    border:1px solid #d0d5dd !important;
    border-radius:10px !important;
    background:#fffdf7 !important;
    color:#101828 !important;
    font:inherit;
    outline:none;
}

.mentorship-form-group textarea{
    min-height:120px;
    resize:vertical;
}

.mentorship-form-group input::placeholder,
.mentorship-form-group textarea::placeholder{
    color:#98a2b3 !important;
    opacity:1 !important;
}

.mentorship-form-group input:focus,
.mentorship-form-group textarea:focus,
.mentorship-form-group select:focus{
    background:#ffffff !important;
    border-color:#800000 !important;
    box-shadow:0 0 0 3px rgba(128,0,0,.08) !important;
}

.mentorship-form-actions{
    display:flex;
    justify-content:flex-end;
    margin-top:16px;
}

.mentorship-goal-list{
    display:grid;
    gap:12px;
}

.mentorship-goal-card{
    padding:17px;
    background:#ffffff;
    border:1px solid #e5e7eb;
    border-left:4px solid #D4AF37;
    border-radius:12px;
}

.mentorship-goal-card h3{
    margin:0 0 6px;
    color:#101828;
}

.mentorship-goal-meta{
    color:#667085;
    font-size:.9rem;
}

@media(max-width:700px){
    .mentorship-form-grid{grid-template-columns:1fr}
    .mentorship-form-group.full{grid-column:auto}
}
</style>

<div class="mentorship-goals-page">
    <div class="page-header">
        <div>
            <span class="eh-kicker">Mentorship</span>
            <h1>My Growth Goals</h1>
            <p>Create and track goals agreed during your mentorship journey.</p>
        </div>

        @if(Route::has('mentorship.dashboard'))
            <a href="{{ route('mentorship.dashboard') }}" class="btn btn-outline">
                <i class="fas fa-arrow-left"></i>
                Back to Mentorship
            </a>
        @endif
    </div>

    <div class="mentorship-form-card">
        <h2 style="margin-top:0">Add a Goal</h2>

        <form method="POST" action="{{ route('mentorship.goals.store', $match) }}">
            @csrf

            <div class="mentorship-form-grid">
                <div class="mentorship-form-group">
                    <label for="goal_title">Goal title</label>
                    <input
                        id="goal_title"
                        name="title"
                        value="{{ old('title') }}"
                        placeholder="e.g. Complete my CV and apply for three roles"
                        required
                    >
                </div>

                <div class="mentorship-form-group">
                    <label for="target_date">Target date</label>
                    <input
                        id="target_date"
                        type="date"
                        name="target_date"
                        value="{{ old('target_date') }}"
                    >
                </div>

                <div class="mentorship-form-group full">
                    <label for="goal_description">Description</label>
                    <textarea
                        id="goal_description"
                        name="description"
                        placeholder="Describe what you want to achieve, why it matters, and what support you need."
                    >{{ old('description') }}</textarea>
                </div>
            </div>

            <div class="mentorship-form-actions">
                <button class="btn btn-primary" type="submit">
                    <i class="fas fa-plus"></i>
                    Add Goal
                </button>
            </div>
        </form>
    </div>

    <div class="mentorship-goal-list">
        @forelse($match->goals as $goal)
            <article class="mentorship-goal-card">
                <h3>{{ $goal->title }}</h3>

                <div class="mentorship-goal-meta">
                    {{ number_format((float) ($goal->progress_percent ?? 0), 0) }}% complete
                    Â· {{ ucfirst(str_replace('_', ' ', $goal->status)) }}

                    @if($goal->target_date)
                        Â· Target {{ $goal->target_date->format('d M Y') }}
                    @endif
                </div>

                @if($goal->description)
                    <p>{{ $goal->description }}</p>
                @endif
            </article>
        @empty
            <div class="eh-empty">
                <i class="fas fa-bullseye"></i>
                <h3>No goals yet</h3>
                <p>Add your first mentorship growth goal above.</p>
            </div>
        @endforelse
    </div>
</div>
@endsection

