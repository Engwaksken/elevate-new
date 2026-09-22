@extends('layouts.app')
@section('content')
<div class="card" style="max-width:520px;margin:auto">
    <h1>Participant Login</h1>
    <form method="POST" action="{{ route('login.attempt') }}">
        @csrf
        <label>Email</label><input type="email" name="email" value="{{ old('email') }}" required>
        <label>Password</label><input type="password" name="password" required>
        <label><input type="checkbox" name="remember" value="1" style="width:auto"> Remember me</label><br><br>
        <button>Login</button>
    </form>
</div>
@endsection
