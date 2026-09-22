<!doctype html>
<html><head><meta charset="utf-8"><style>
body{font-family:DejaVu Sans,sans-serif;text-align:center;padding:50px}
h1{font-size:34px} .name{font-size:28px;font-weight:bold;margin:25px}
.box{border:4px solid #222;padding:45px}
</style></head>
<body><div class="box">
<h1>Certificate of Completion</h1>
<p>This is to certify that</p>
<div class="name">{{ $certificate->user->name }}</div>
<p>has successfully completed</p>
<h2>{{ $certificate->course->title }}</h2>
<p>Issued {{ $certificate->issued_on->format('d F Y') }}</p>
<p>Certificate No: {{ $certificate->certificate_number }}</p>
<p>Verification: {{ route('certificates.verify',$certificate->verification_token) }}</p>
</div></body></html>
