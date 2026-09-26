@extends('layouts.app')

@section('content')
<div class="card">
    <h1>{{ $user->exists ? 'Edit User' : 'Add User' }}</h1>

    <form method="POST" action="{{ $user->exists ? route('admin.users.update', $user) : route('admin.users.store') }}">
        @csrf
        @if($user->exists)
            @method('PUT')
        @endif

        <label for="name">Name</label>
        <input
            id="name"
            name="name"
            value="{{ old('name', $user->name) }}"
            placeholder="e.g. Jane Namusoke"
            required
        >
        <small class="form-hint">Enter the user's full name as it should appear in the system.</small>

        <label for="email">Email</label>
        <input
            id="email"
            type="email"
            name="email"
            value="{{ old('email', $user->email) }}"
            placeholder="e.g. jane@example.com"
            required
        >
        <small class="form-hint">Used for account access and system notifications.</small>

        <label for="phone">Phone</label>
        <input
            id="phone"
            name="phone"
            value="{{ old('phone', $user->phone) }}"
            placeholder="e.g. +256 700 000000"
        >
        <small class="form-hint">Include the country code where possible.</small>

        <div class="grid">
            <div>
                <label for="user_type">User type</label>
                <select id="user_type" name="user_type">
                    <option value="participant" @selected(old('user_type', $user->user_type) === 'participant')>
                        Participant
                    </option>
                    <option value="staff" @selected(old('user_type', $user->user_type) === 'staff')>
                        Staff
                    </option>
                </select>
                <small class="form-hint">Choose whether this account belongs to a participant or staff member.</small>
            </div>

            <div>
                <label for="status">Status</label>
                <select id="status" name="status">
                    @foreach(['active', 'inactive', 'suspended', 'pending'] as $s)
                        <option value="{{ $s }}" @selected(old('status', $user->status) === $s)>
                            {{ ucfirst($s) }}
                        </option>
                    @endforeach
                </select>
                <small class="form-hint">Only active accounts should have normal system access.</small>
            </div>
        </div>

        <label for="password">
            Password {{ $user->exists ? '(leave blank to keep current)' : '' }}
        </label>
        <input
            id="password"
            type="password"
            name="password"
            placeholder="{{ $user->exists ? 'Leave blank to keep the current password' : 'Create a secure password' }}"
        >
        <small class="form-hint">
            {{ $user->exists ? 'Enter a new password only when you want to change it.' : 'Use a strong password that the user can change later.' }}
        </small>

        <label for="password_confirmation">Confirm Password</label>
        <input
            id="password_confirmation"
            type="password"
            name="password_confirmation"
            placeholder="Re-enter the password"
        >
        <small class="form-hint">Must match the password entered above.</small>

        <div class="card">
            <h3>Roles</h3>
            <p class="form-hint">Select one or more roles to determine the user's access permissions.</p>

            <div class="eh-choice-grid">
                @foreach($roles as $role)
                    <label class="eh-choice-card" for="role-{{ $role->id }}">
                        <input
                            id="role-{{ $role->id }}"
                            type="checkbox"
                            name="roles[]"
                            value="{{ $role->id }}"
                            @checked(
                                in_array(
                                    $role->id,
                                    old('roles', $user->exists ? $user->roles->pluck('id')->all() : [])
                                )
                            )
                        >
                        <span class="eh-choice-card__text">{{ $role->name }}</span>
                    </label>
                @endforeach
            </div>
        </div>

        <button type="submit">Save User</button>
    </form>
</div>
@endsection
