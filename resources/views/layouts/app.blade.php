<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'ElevateHer360' }}</title>
    <style>
        body{font-family:Arial,sans-serif;margin:0;background:#f7f8fb;color:#172033}
        .wrap{max-width:1100px;margin:0 auto;padding:24px}
        .card{background:#fff;border:1px solid #e7e9ee;border-radius:12px;padding:20px;margin-bottom:18px}
        input,select,textarea{width:100%;padding:10px;margin-top:6px;margin-bottom:12px;box-sizing:border-box}
        button,.btn{display:inline-block;padding:10px 16px;border-radius:8px;border:0;background:#222;color:white;text-decoration:none;cursor:pointer}
        .error{color:#b42318}.success{color:#067647}.grid{display:grid;grid-template-columns:repeat(2,1fr);gap:16px}
        @media(max-width:700px){.grid{grid-template-columns:1fr}}
    </style>
</head>
<body>
<div class="wrap">
    @if(session('success')) <div class="card success">{{ session('success') }}</div> @endif
    @if($errors->any())
        <div class="card error"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
    @endif
    {{ $slot ?? '' }}
    @yield('content')
</div>
</body>
</html>
