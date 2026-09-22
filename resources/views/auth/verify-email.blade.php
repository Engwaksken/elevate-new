@extends('layouts.app')
@section('content')
<div class="card">
    <h1>Verify your email</h1>
    <p>Please check your inbox and click the verification link.</p>
    <form method="POST" action="{{ route('verification.send') }}">@csrf<button>Resend verification email</button></form>
</div>
@endsection
