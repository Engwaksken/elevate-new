@extends('layouts.app')
@section('title', app(\App\Services\CmsContentService::class)->text('learning', 'title', 'Courses').' | ElevateHer360')
@section('meta_description', app(\App\Services\CmsContentService::class)->text('learning', 'summary', 'Explore our learning courses.'))
@section('content')

<style>
.eh-learning-hero{position:relative;overflow:hidden;border-radius:22px;padding:44px 40px;margin-bottom:28px;background:linear-gradient(120deg,#5f0000,#800000 55%,#a52a2a);color:#fff}
.eh-learning-hero::after{content:"";position:absolute;right:-70px;top:-70px;width:260px;height:260px;border-radius:50%;background:rgba(212,175,55,.18)}
.eh-learning-hero .eh-kicker{color:#f7e7a9}
.eh-learning-hero h1{margin:8px 0 10px;color:#fff;font-size:2.2rem}
.eh-learning-hero p{max-width:640px;margin:0 0 22px;color:rgba(255,255,255,.9)}
/* Full-bleed band: breaks out of the guest page-shell container; content stays aligned to it. */
.page-shell > .eh-learning-hero{width:100vw;margin:-40px calc(50% - 50vw) 28px;border-radius:0;padding:52px max(24px,calc(50vw - 590px))}
.participant-site-main .eh-learning-hero{width:100%}
.eh-learning-hero > *{position:relative;z-index:1}
/* Search, format and button on one row (global CSS makes form controls 100% wide). */
.eh-learning-search{display:grid;grid-template-columns:minmax(0,1fr) minmax(170px,220px) auto;gap:10px;align-items:center;width:100%}
.eh-learning-search .search-box{min-width:0}
.eh-learning-search select{width:100%}
.eh-learning-search .btn{padding-inline:26px;white-space:nowrap}
.eh-learning-search .search-box input{min-height:46px;background:#fff;border:0;border-radius:10px;box-shadow:0 6px 18px rgba(0,0,0,.12)}
.eh-learning-search select{min-height:46px;padding:0 12px;border:0;border-radius:10px;background:#fff;color:#344054;font:inherit}
.eh-learning-search .btn{min-height:46px;background:#d4af37;border-color:#d4af37;color:#3a2c00;font-weight:700}
.eh-learning-search .btn:hover{background:#e6c55a;border-color:#e6c55a;color:#3a2c00}
.eh-course-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:22px}
.eh-course-card{display:flex;flex-direction:column;overflow:hidden;background:#fff;border:1px solid #e5e7eb;border-radius:16px;text-decoration:none;color:inherit;box-shadow:0 1px 2px rgba(16,24,40,.04);transition:transform .18s ease,box-shadow .18s ease}
.eh-course-card:hover{transform:translateY(-4px);box-shadow:0 16px 32px rgba(16,24,40,.12)}
.eh-course-thumb{position:relative;aspect-ratio:16/9;background:#f3f4f6;overflow:hidden}
.eh-course-thumb img{width:100%;height:100%;object-fit:cover}
.eh-course-thumb .eh-course-tag{position:absolute;left:12px;top:12px;padding:5px 10px;border-radius:999px;background:rgba(128,0,0,.9);color:#fff;font-size:.72rem;font-weight:700;text-transform:capitalize}
.eh-course-body{display:flex;flex-direction:column;gap:8px;padding:16px 18px 18px;flex:1}
.eh-course-body h2{margin:0;font-size:1.08rem;line-height:1.3}
.eh-course-body p{margin:0;color:#667085;font-size:.85rem;flex:1}
.eh-course-meta{display:flex;gap:16px;flex-wrap:wrap;margin-top:4px;color:#98a2b3;font-size:.75rem}
.eh-course-meta span{display:inline-flex;align-items:center;gap:5px}
.eh-course-meta i{color:#800000}
@media(max-width:980px){.eh-course-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}
@media(max-width:620px){.eh-course-grid{grid-template-columns:1fr}.eh-learning-hero,.page-shell > .eh-learning-hero{padding:32px 16px}.eh-learning-search{grid-template-columns:1fr}.eh-learning-search .btn{width:100%}.eh-learning-hero h1{font-size:1.8rem}}
</style>

<section class="eh-learning-hero">
    <span class="eh-kicker">Learning</span>
    <h1>{{ app(\App\Services\CmsContentService::class)->text('learning', 'title', 'Courses') }}</h1>
    <p>{{ app(\App\Services\CmsContentService::class)->text('learning', 'summary', 'Explore courses across digital skills, entrepreneurship, leadership and career readiness — and start learning at your own pace.') }}</p>
    <form method="GET" class="eh-learning-search" role="search">
        <div class="search-box"><i class="fas fa-search"></i><input name="search" value="{{ request('search') }}" placeholder="Search courses by title or keyword…" aria-label="Search courses"></div>
        <select name="delivery_mode" aria-label="Delivery mode">
            <option value="">All formats</option>
            @foreach(['online'=>'Online','in_person'=>'In person','blended'=>'Blended'] as $value=>$label)
                <option value="{{ $value }}" @selected(request('delivery_mode')===$value)>{{ $label }}</option>
            @endforeach
        </select>
        <button type="submit" class="btn"><i class="fas fa-search" aria-hidden="true"></i> Search</button>
    </form>
</section>

@auth
    @if(auth()->user()->isParticipant() && Route::has('learning.my-courses'))
        <div style="margin-bottom:22px"><a href="{{ route('learning.my-courses') }}" class="btn btn-primary"><i class="fas fa-book-open"></i> Go to My Learning</a></div>
    @endif
@endauth

@include('partials.cms-intro', ['slug' => 'learning'])

@php($deliveryLabels = ['online'=>'Online','in_person'=>'In person','blended'=>'Blended'])

<div class="eh-course-grid">
@forelse($courses as $course)
    <a href="{{ route('learning.course.show',$course) }}" class="eh-course-card">
        <div class="eh-course-thumb">
            <img src="{{ $course->thumbnail_url }}" alt="{{ $course->title }}" loading="lazy">
            @if($course->delivery_mode)<span class="eh-course-tag">{{ $deliveryLabels[$course->delivery_mode] ?? ucfirst(str_replace('_',' ',$course->delivery_mode)) }}</span>@endif
        </div>
        <div class="eh-course-body">
            <h2>{{ $course->title }}</h2>
            <p>{{ $course->briefDescription() }}</p>
            <div class="eh-course-meta">
                @if($course->duration_hours)<span><i class="fas fa-clock"></i> {{ number_format($course->duration_hours) }} hrs</span>@endif
                @if($course->start_date)<span><i class="fas fa-calendar-day"></i> Starts {{ $course->start_date->format('d M Y') }}</span>@endif
                <span><i class="fas fa-arrow-right"></i> View course</span>
            </div>
        </div>
    </a>
@empty
    <div class="eh-empty-state" style="grid-column:1/-1"><h3>No courses found</h3><p>Try a different search or check back soon.</p></div>
@endforelse
</div>

<div class="eh-pagination">{{ $courses->links() }}</div>
@endsection
