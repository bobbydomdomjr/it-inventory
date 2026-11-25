<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>{{ $title }}</title>
<style>
    @page { margin: 28px 28px 42px; }
    * { box-sizing: border-box; }
    body { margin: 0; color: #263548; font-family: DejaVu Sans, sans-serif; font-size: 8px; }
    .report-head { border-bottom: 2px solid #17365f; padding-bottom: 12px; margin-bottom: 14px; }
    .org { color: #17365f; font-size: 13px; font-weight: bold; }
    .address { margin-top: 3px; color: #778397; font-size: 8px; }
    h1 { margin: 12px 0 4px; color: #25364b; font-size: 16px; }
    .meta { color: #758196; font-size: 8px; }
    .notice { margin: 10px 0; padding: 7px 9px; color: #74521e; background: #fbf3e4; border: 1px solid #eeddbd; }
    table { width: 100%; border-collapse: collapse; table-layout: auto; }
    thead { display: table-header-group; }
    th { background: #edf2f8; color: #42536a; font-size: 7px; text-transform: uppercase; letter-spacing: .3px; }
    th, td { padding: 6px 5px; border: 1px solid #dfe5ed; text-align: left; vertical-align: top; overflow-wrap: anywhere; }
    tbody tr:nth-child(even) { background: #f8fafc; }
    .empty { padding: 20px; text-align: center; color: #8793a3; }
    .foot { margin-top: 14px; color: #94a0af; font-size: 7px; }
</style>
</head>
<body>
<header class="report-head">
    <div class="org">{{ $organizationName }}</div>
    @if($organizationAddress)<div class="address">{{ $organizationAddress }}</div>@endif
    <h1>{{ $title }}</h1>
    <div class="meta">Generated {{ $generatedAt->timezone(config('app.timezone'))->format(config('app.date_format', 'd M Y').' '.config('app.time_format', 'H:i')) }} · {{ count($rows) }} record(s)</div>
</header>
@if($truncated)<div class="notice">This PDF is limited to the first {{ number_format($limit) }} records to keep document rendering reliable. Use the XLSX export for larger registers.</div>@endif
<table>
    <thead><tr>@foreach($headings as $heading)<th>{{ $heading }}</th>@endforeach</tr></thead>
    <tbody>
    @forelse($rows as $row)
        <tr>@foreach($row as $cell)<td>{{ is_scalar($cell) || $cell === null ? $cell : json_encode($cell) }}</td>@endforeach</tr>
    @empty
        <tr><td class="empty" colspan="{{ max(1,count($headings)) }}">No records found.</td></tr>
    @endforelse
    </tbody>
</table>
<div class="foot">IT Inventory Management System · Internal use · Generated report</div>
</body>
</html>
