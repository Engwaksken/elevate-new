<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>{{ $title }}</title>
<style>
    @page { margin: 22mm 12mm 16mm 12mm; }
    * { box-sizing: border-box; }
    body { font-family: "DejaVu Sans", Arial, sans-serif; font-size: {{ count($headings) > 10 ? '7.5px' : (count($headings) > 6 ? '8.5px' : '9.5px') }}; color: #1f2937; margin: 0; }
    .head { width: 100%; border-bottom: 2px solid #800000; padding-bottom: 6px; margin-bottom: 8px; }
    .head td { vertical-align: middle; border: 0; padding: 0; }
    .logo { max-height: 38px; max-width: 140px; }
    .org { font-size: 10px; color: #800000; font-weight: bold; text-transform: uppercase; letter-spacing: .5px; }
    h1 { font-size: 16px; margin: 2px 0 0; color: #111827; }
    .sub { color: #4b5563; margin-top: 2px; }
    .meta { text-align: right; color: #6b7280; font-size: 8px; line-height: 1.5; }
    .filters { margin: 0 0 8px; padding: 5px 7px; background: #fbf6f6; border: 1px solid #eadede; font-size: 8px; color: #374151; }
    .filters span { margin-right: 10px; }
    .note { margin: 0 0 8px; padding: 5px 7px; background: #fff7da; border: 1px solid #e9d48a; font-size: 8px; color: #6b4e00; }
    table.data { width: 100%; border-collapse: collapse; }
    table.data thead { display: table-header-group; }
    table.data tr { page-break-inside: avoid; }
    table.data th { background: #800000; color: #fff; text-align: left; font-weight: bold; padding: 4px 5px; border: 1px solid #6b0000; }
    table.data td { padding: 3px 5px; border: 1px solid #e5e7eb; vertical-align: top; word-wrap: break-word; }
    table.data tbody tr:nth-child(even) td { background: #f9fafb; }
    .empty { text-align: center; color: #6b7280; padding: 14px; }
    .footer { position: fixed; bottom: -10mm; left: 0; right: 0; font-size: 7.5px; color: #9ca3af; }
</style>
</head>
<body>
<table class="head">
    <tr>
        <td style="width:60%">
            @if($logo)<img src="{{ $logo }}" class="logo" alt=""><br>@endif
            <div class="org">{{ $orgName }}</div>
            <h1>{{ $title }}</h1>
            @if($subtitle)<div class="sub">{{ $subtitle }}</div>@endif
        </td>
        <td class="meta">
            Generated {{ $generatedAt->format('d M Y H:i') }}<br>
            @if($generatedBy)By {{ $generatedBy }}<br>@endif
            {{ number_format(count($rows)) }} {{ \Illuminate\Support\Str::plural('row', count($rows)) }}{{ $truncated ? ' (truncated)' : '' }}
        </td>
    </tr>
</table>

@if(!empty($filters))
<div class="filters"><strong>Filters:</strong>
    @foreach($filters as $label => $value)<span>{{ $label }}: <strong>{{ $value }}</strong></span>@endforeach
</div>
@endif

@if($truncated)
<div class="note">This PDF shows the first {{ number_format($limit) }} rows only. Download the CSV export for the complete data set.</div>
@endif

<table class="data">
    <thead><tr>@foreach($headings as $heading)<th>{{ $heading }}</th>@endforeach</tr></thead>
    <tbody>
    @forelse($rows as $row)
        <tr>@foreach($row as $cell)<td>{{ $cell }}</td>@endforeach</tr>
    @empty
        <tr><td class="empty" colspan="{{ max(1, count($headings)) }}">No records match the current view.</td></tr>
    @endforelse
    </tbody>
</table>

<div class="footer">{{ $orgName }} &middot; {{ $title }} &middot; {{ $generatedAt->format('Y-m-d H:i') }}</div>
</body>
</html>
