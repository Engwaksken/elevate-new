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
    pre { margin:0; padding:16px; overflow:auto; background:var(--panel); border:1px solid var(--line); border-radius:8px; white-space:pre-wrap; word-break:break-word; }
</style>
</head>
<body>
<header>
    <div style="min-width:0">
        <h1>{{ $name }}</h1>
        <small>{{ number_format($size / 1024, 1) }} KB · Preview</small>
    </div>
    <a class="btn" href="{{ $downloadUrl }}">Download</a>
</header>

<main>
    @if($tooLarge)
        <div class="notice">
            <p>This file is too large to preview ({{ number_format($size / 1048576, 1) }} MB).</p>
            <a class="btn" href="{{ $downloadUrl }}">Download to open</a>
        </div>
    @elseif($error)
        <div class="notice"><p>{{ $error }}</p><a class="btn" href="{{ $downloadUrl }}">Download</a></div>
    @elseif($kind === 'pdf' || $kind === 'word')
        <iframe class="frame" src="{{ $rawUrl }}" title="{{ $name }}"></iframe>
    @elseif($kind === 'image')
        <img class="image" src="{{ $rawUrl }}" alt="{{ $name }}">
    @elseif($kind === 'spreadsheet')
        @if($truncated)
            <div class="warn">Showing the first {{ \App\Services\Files\FilePreviewService::MAX_ROWS }} rows and {{ \App\Services\Files\FilePreviewService::MAX_COLUMNS }} columns of up to {{ \App\Services\Files\FilePreviewService::MAX_SHEETS }} sheets. Download the file to see everything.</div>
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
            <p>Preview isn't available for this file type.</p>
            <a class="btn" href="{{ $downloadUrl }}">Download</a>
        </div>
    @endif
</main>
</body>
</html>
