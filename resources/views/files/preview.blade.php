<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex">
<title>Preview · {{ $name }}</title>
<style>
    :root { --bg:#f5f6fa; --panel:#fff; --text:#1d2433; --muted:#667085; --line:#e4e7ec; --accent:#7a1f5c; }
    @media (prefers-color-scheme: dark) { :root { --bg:#14161c; --panel:#1d2029; --text:#e8eaf0; --muted:#9aa3b5; --line:#2e3240; --accent:#e07ab8; } }
    * { box-sizing:border-box; }
    body { margin:0; font-family:system-ui,-apple-system,"Segoe UI",Roboto,sans-serif; background:var(--bg); color:var(--text); }
    header { position:sticky; top:0; z-index:2; display:flex; gap:12px; align-items:center; justify-content:space-between; padding:12px 16px; background:var(--panel); border-bottom:1px solid var(--line); }
    header h1 { margin:0; font-size:1rem; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
    header small { color:var(--muted); }
    .btn { flex:none; display:inline-block; padding:8px 14px; border-radius:8px; background:var(--accent); color:#fff; text-decoration:none; font-weight:600; font-size:.9rem; }
    main { padding:16px; }
    .frame { width:100%; height:calc(100vh - 90px); border:1px solid var(--line); border-radius:8px; background:#fff; }
    .image { display:block; max-width:100%; margin:0 auto; border-radius:8px; }
    .notice { max-width:640px; margin:48px auto; padding:24px; text-align:center; background:var(--panel); border:1px solid var(--line); border-radius:12px; }
    .warn { margin-bottom:12px; padding:10px 14px; border-radius:8px; background:var(--panel); border:1px solid var(--line); color:var(--muted); font-size:.9rem; }
    .tabs { display:flex; flex-wrap:wrap; gap:6px; margin-bottom:12px; }
    .tabs button { padding:6px 12px; border:1px solid var(--line); border-radius:999px; background:var(--panel); color:var(--text); cursor:pointer; font:inherit; font-size:.85rem; }
    .tabs button[aria-selected="true"] { background:var(--accent); border-color:var(--accent); color:#fff; }
    .sheet { overflow:auto; max-height:calc(100vh - 170px); background:var(--panel); border:1px solid var(--line); border-radius:8px; }
    table { border-collapse:collapse; font-size:.85rem; }
    th, td { padding:6px 10px; border:1px solid var(--line); white-space:pre-wrap; vertical-align:top; max-width:420px; }
    th { position:sticky; top:0; background:var(--bg); color:var(--muted); font-weight:600; }
    td.n { position:sticky; left:0; background:var(--bg); color:var(--muted); text-align:right; }
    body.embedded main { padding:12px; }
    body.embedded .frame { height:calc(100vh - 26px); }
    body.embedded .sheet { max-height:calc(100vh - 100px); }
    pre { margin:0; padding:16px; overflow:auto; background:var(--panel); border:1px solid var(--line); border-radius:8px; white-space:pre-wrap; word-break:break-word; }
</style>
</head>
@php($allowDownload = $allowDownload ?? true)
<body @class(['embedded' => $embedded]) @unless($allowDownload) oncontextmenu="return false" @endunless>
@unless($embedded)
<header>
    <div style="min-width:0">
        <h1>{{ $name }}</h1>
        <small>{{ number_format($size / 1024, 1) }} KB · {{ $allowDownload ? 'Preview' : 'View only' }}</small>
    </div>
    @if($allowDownload)
        <a class="btn" href="{{ $downloadUrl }}">Download</a>
    @endif
</header>
@endunless

<main>
    @if($tooLarge)
        <div class="notice">
            <p>This file is too large to preview ({{ number_format($size / 1048576, 1) }} MB).</p>
            @if($allowDownload)
                <a class="btn" href="{{ $downloadUrl }}">Download to open</a>
            @else
                <p style="color:var(--muted)">This file is view-only and cannot be downloaded. Ask your instructor for help.</p>
            @endif
        </div>
    @elseif($error)
        <div class="notice"><p>{{ $error }}</p>@if($allowDownload)<a class="btn" href="{{ $downloadUrl }}">Download</a>@endif</div>
    @elseif($kind === 'pdf' || $kind === 'word')
        <iframe class="frame" src="{{ $rawUrl }}{{ $allowDownload ? '' : '#toolbar=0&navpanes=0' }}" title="{{ $name }}"></iframe>
    @elseif($kind === 'image')
        <img class="image" src="{{ $rawUrl }}" alt="{{ $name }}" @unless($allowDownload) draggable="false" @endunless>
    @elseif($kind === 'video')
        <video class="image" style="width:100%;max-height:calc(100vh - 120px);background:#000" controls preload="metadata" playsinline @unless($allowDownload) controlslist="nodownload" disablepictureinpicture @endunless>
            <source src="{{ $rawUrl }}" type="{{ $mediaType ?? 'video/mp4' }}">
            Your browser cannot play this video.
        </video>
    @elseif($kind === 'audio')
        <div class="notice">
            <p>{{ $name }}</p>
            <audio style="width:100%" controls preload="metadata" @unless($allowDownload) controlslist="nodownload" @endunless>
                <source src="{{ $rawUrl }}" type="{{ $mediaType ?? 'audio/mpeg' }}">
                Your browser cannot play this audio file.
            </audio>
        </div>
    @elseif($kind === 'spreadsheet')
        @if($truncated)
            <div class="warn">Showing the first {{ \App\Services\Files\FilePreviewService::MAX_ROWS }} rows and {{ \App\Services\Files\FilePreviewService::MAX_COLUMNS }} columns of up to {{ \App\Services\Files\FilePreviewService::MAX_SHEETS }} sheets.@if($allowDownload) Download the file to see everything.@endif</div>
        @endif

        @if(count($sheets) > 1)
            <div class="tabs" role="tablist">
                @foreach($sheets as $index => $sheet)
                    <button type="button" role="tab" data-sheet="{{ $index }}" aria-selected="{{ $index === 0 ? 'true' : 'false' }}">{{ $sheet['title'] }}</button>
                @endforeach
            </div>
        @endif

        @forelse($sheets as $index => $sheet)
            <div class="sheet" data-sheet-panel="{{ $index }}" @if($index > 0) hidden @endif>
                @if(empty($sheet['rows']))
                    <p style="padding:16px;color:var(--muted)">This sheet is empty.</p>
                @else
                    <table>
                        <thead>
                            <tr>
                                <th></th>
                                @foreach(array_keys($sheet['rows'][0]) as $column)
                                    <th>{{ \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($column + 1) }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($sheet['rows'] as $rowIndex => $row)
                                <tr>
                                    <td class="n">{{ $rowIndex + 1 }}</td>
                                    @foreach($row as $cell)
                                        <td>{{ is_scalar($cell) ? $cell : '' }}</td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        @empty
            <div class="notice"><p>This workbook has no sheets.</p></div>
        @endforelse

        <script>
            document.querySelectorAll('[data-sheet]').forEach(function (tab) {
                tab.addEventListener('click', function () {
                    document.querySelectorAll('[data-sheet]').forEach(function (t) { t.setAttribute('aria-selected', t === tab ? 'true' : 'false'); });
                    document.querySelectorAll('[data-sheet-panel]').forEach(function (p) { p.hidden = p.dataset.sheetPanel !== tab.dataset.sheet; });
                });
            });
        </script>
    @elseif($kind === 'text')
        @if($truncated)
            <div class="warn">Showing the first {{ number_format(\App\Services\Files\FilePreviewService::MAX_TEXT_BYTES / 1024) }} KB.</div>
        @endif
        <pre>{{ $text }}</pre>
    @else
        <div class="notice">
            @if($allowDownload)
                <p>Preview isn't available for this file type.</p>
                <a class="btn" href="{{ $downloadUrl }}">Download</a>
            @else
                <p><strong>Preview not available in the browser — this file is view-only.</strong></p>
                <p style="color:var(--muted)">Files of this type can't be shown here and can't be downloaded. Please ask your instructor if you need access to it.</p>
            @endif
        </div>
    @endif
</main>
</body>
</html>
