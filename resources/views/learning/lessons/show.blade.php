@extends('layouts.app')

@section('title', $lesson->title.' | ElevateHer360')

@section('content')
<div class="card">
    <p>
        <a href="{{ route('learning.course.dashboard', $course) }}">
            <i class="fas fa-arrow-left"></i> Back to course
        </a>
    </p>

    <div style="display:flex;justify-content:space-between;gap:12px;align-items:flex-start;flex-wrap:wrap">
        <div>
            <h1>{{ $lesson->title }}</h1>
            <p class="text-muted">{{ $lesson->module?->title }}</p>
        </div>

        @php
            // Only files participants may download are cached for offline use;
            // view-only files stay on the server.
            $fileService = app(\App\Services\Learning\LearningFileService::class);
            $offlineUrls = collect([request()->fullUrl()])
                ->merge($files->filter(fn ($file) => $fileService->canDownloadLearningFile(auth()->user(), $file))
                    ->map(fn ($file) => route('learning.files.download', [$file, 'download' => 1])))
                ->all();
        @endphp

        <button
            type="button"
            class="btn btn-outline"
            data-offline-download="{{ implode(',', $offlineUrls) }}"
            data-offline-key="lesson-{{ $lesson->id }}"
        >
            <i class="fas fa-download"></i>
            Download for offline use
        </button>
    </div>

    @if($lesson->video_url)
        <p>
            <a class="btn btn-outline" href="{{ $lesson->video_url }}" target="_blank" rel="noopener">
                <i class="fas fa-video"></i> Open video
            </a>
        </p>
    @endif

    @if($lesson->external_url)
        <p>
            <a class="btn btn-outline" href="{{ $lesson->external_url }}" target="_blank" rel="noopener">
                <i class="fas fa-link"></i> Open resource
            </a>
        </p>
    @endif

    @if($files->isNotEmpty())
        <section class="lesson-files" aria-labelledby="lesson-files-title">
            <h2 id="lesson-files-title" style="font-size:1.05rem;margin:16px 0 4px"><i class="fas fa-paperclip"></i> Lesson files</h2>
            <p class="text-muted" style="margin:0 0 6px;font-size:.85rem">Excel/CSV and zip files can be downloaded. Other files open in the viewer and are view-only.</p>
            <x-learning.file-list :files="$files" />
        </section>
    @endif

    <div class="lesson-content">
        {!! nl2br(e($lesson->content)) !!}
    </div>

    <hr>

    <form method="POST" action="{{ route('learning.lesson.complete', $lesson) }}">
        @csrf
        <button class="btn btn-primary">
            <i class="fas fa-circle-check"></i> Mark Complete
        </button>
    </form>
</div>
@endsection

@push('scripts')
<script>
(() => {
    const token = document.querySelector('input[name="_token"]')?.value;
    if (!token) return;

    const endpoint = @json(route('learning.lesson.reading-time', $lesson));
    let lastTick = Date.now();
    let lastActivity = lastTick;
    let pendingMs = 0;
    let sending = false;

    const interact = () => { lastActivity = Date.now(); };
    ['pointerdown', 'keydown', 'scroll', 'touchstart'].forEach((event) => {
        document.addEventListener(event, interact, {passive: true});
    });

    const flush = (keepalive = false) => {
        const seconds = Math.floor(pendingMs / 1000);
        if (seconds < 1 || sending) return;
        const batch = Math.min(seconds, 300);
        pendingMs -= batch * 1000;
        sending = true;

        fetch(endpoint, {
            method: 'POST',
            credentials: 'same-origin',
            keepalive,
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': token,
            },
            body: JSON.stringify({seconds: batch}),
        }).catch(() => {
            pendingMs += batch * 1000;
        }).finally(() => { sending = false; });
    };

    setInterval(() => {
        const now = Date.now();
        const elapsed = Math.min(now - lastTick, 10000);
        lastTick = now;
        if (!document.hidden && now - lastActivity < 5 * 60 * 1000) {
            pendingMs += elapsed;
            if (pendingMs >= 30000) flush();
        }
    }, 1000);

    document.addEventListener('visibilitychange', () => {
        lastTick = Date.now();
        if (document.hidden) flush(true);
        else interact();
    });
    window.addEventListener('pagehide', () => flush(true));
})();
</script>
@endpush
