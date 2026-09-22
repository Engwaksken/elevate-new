@extends('layouts.app')
@section('content')
<div class="card">
    <h1>Create your ElevateHer360 account</h1>
    <form method="POST" action="{{ route('register.store') }}">
        @csrf
        <div class="grid">
            <div><label>Surname</label><input name="surname" value="{{ old('surname') }}" required></div>
            <div><label>Given name</label><input name="given_name" value="{{ old('given_name') }}" required></div>
            <div><label>Other name</label><input name="other_name" value="{{ old('other_name') }}"></div>
            <div><label>Email</label><input type="email" name="email" value="{{ old('email') }}" required></div>
            <div><label>Phone</label><input name="phone" value="{{ old('phone') }}"></div>
            <div><label>Date of birth</label><input type="date" name="date_of_birth" value="{{ old('date_of_birth') }}"></div>
            <div><label>Gender</label>
                <select name="gender">
                    <option value="">Select</option><option value="female">Female</option><option value="male">Male</option>
                    <option value="other">Other</option><option value="prefer_not_to_say">Prefer not to say</option>
                </select>
            </div>
            <div><label>Branch</label>
                <select name="branch_id"><option value="">Select</option>
                    @foreach($branches as $branch)<option value="{{ $branch->id }}">{{ $branch->name }}</option>@endforeach
                </select>
            </div>
            <div><label>Country</label><input name="country" value="{{ old('country','Uganda') }}"></div>
            <div><label>District</label><input name="district" value="{{ old('district') }}"></div>
        </div>
        <label><input type="checkbox" name="is_pwd" value="1" style="width:auto"> Person with disability</label><br><br>
        <label>Career interests</label><textarea name="career_interests">{{ old('career_interests') }}</textarea>
        <div class="grid">
            <div><label>Password</label><input type="password" name="password" required></div>
            <div><label>Confirm password</label><input type="password" name="password_confirmation" required></div>
        </div>
        <label><input type="checkbox" name="privacy_policy" value="1" style="width:auto" required> I accept the Privacy Policy</label><br>
        <label><input type="checkbox" name="terms" value="1" style="width:auto" required> I accept the Terms</label><br><br>
        <button>Create account</button>
    </form>
</div>
@endsection
