@extends('layouts.app')
@section('content')
<div class="card" style="max-width:520px;margin:auto">
    <h1>WITU Staff Login</h1>
    <p>Authorised staff only.</p>
    <form method="POST" action="{{ route('admin.login.attempt') }}">
        @csrf
        <label>Email</label><input type="email" name="email" value="{{ old('email') }}" required>
        <label>Password</label><input type="password" name="password" required>
        <label><input type="checkbox" name="remember" value="1" style="width:auto"> Remember me</label><br><br>
        <button>Login</button>
    </form>
</div>
@endsection
