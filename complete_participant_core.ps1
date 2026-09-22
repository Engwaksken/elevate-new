$ErrorActionPreference = "Stop"

$root = "D:\projects\elevate_her"
Set-Location $root

Write-Host "ElevateHer360: completing participant core..." -ForegroundColor Cyan

$dirs = @(
    "app\Http\Controllers\Participant",
    "resources\views\profile",
    "resources\views\notifications"
)
foreach ($dir in $dirs) {
    New-Item -ItemType Directory -Force -Path $dir | Out-Null
}

@'
<?php

namespace App\Http\Controllers\Participant;

use App\Http\Controllers\Controller;
use App\Models\CalendarEvent;
use App\Models\Enrolment;
use App\Models\JobApplication;
use App\Models\MentorMatch;
use App\Models\Resume;
use App\Models\UserNotification;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $enrolments = Enrolment::query()
            ->with('course')
            ->where('user_id', $user->id)
            ->latest('updated_at')
            ->limit(4)
            ->get();

        $activeMentorship = MentorMatch::query()
            ->with([
                'mentor',
                'sessions' => fn ($query) => $query
                    ->where('scheduled_at', '>=', now())
                    ->orderBy('scheduled_at'),
            ])
            ->where('mentee_user_id', $user->id)
            ->whereIn('status', ['pending', 'active', 'paused'])
            ->latest('updated_at')
            ->first();

        $jobApplications = JobApplication::query()
            ->with('job')
            ->where('user_id', $user->id)
            ->latest('applied_at')
            ->limit(4)
            ->get();

        $upcomingEvents = CalendarEvent::query()
            ->where(function ($query) use ($user) {
                $query->whereNull('responsible_user_id')
                    ->orWhere('responsible_user_id', $user->id);
            })
            ->where('starts_at', '>=', now())
            ->orderBy('starts_at')
            ->limit(5)
            ->get();

        $notifications = UserNotification::query()
            ->where('user_id', $user->id)
            ->latest()
            ->limit(5)
            ->get();

        return view('dashboard', [
            'user' => $user->load('profile'),
            'enrolments' => $enrolments,
            'activeMentorship' => $activeMentorship,
            'jobApplications' => $jobApplications,
            'upcomingEvents' => $upcomingEvents,
            'notifications' => $notifications,
            'unreadNotifications' => UserNotification::query()
                ->where('user_id', $user->id)
                ->whereNull('read_at')
                ->count(),
            'resumeCount' => Resume::query()
                ->where('user_id', $user->id)
                ->count(),
        ]);
    }
}
'@ | Set-Content -Encoding UTF8 app\Http\Controllers\Participant\DashboardController.php

@'
<?php

namespace App\Http\Controllers\Participant;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user()->load('profile'),
            'branches' => Branch::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'phone' => ['nullable', 'string', 'max:30'],
            'branch_id' => ['nullable', 'exists:branches,id'],
            'surname' => ['nullable', 'string', 'max:255'],
            'given_name' => ['nullable', 'string', 'max:255'],
            'other_name' => ['nullable', 'string', 'max:255'],
            'gender' => ['nullable', Rule::in(['female', 'male', 'other', 'prefer_not_to_say'])],
            'date_of_birth' => ['nullable', 'date', 'before_or_equal:today'],
            'country' => ['nullable', 'string', 'max:255'],
            'district' => ['nullable', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            'is_pwd' => ['nullable', 'boolean'],
            'education_level' => ['nullable', 'string', 'max:255'],
            'employment_status' => ['nullable', 'string', 'max:255'],
            'career_interests' => ['nullable', 'string', 'max:5000'],
            'preferred_language' => ['nullable', 'string', 'max:100'],
            'current_password' => ['nullable', 'string'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ]);

        if (! empty($data['password'])) {
            if (empty($data['current_password']) || ! Hash::check($data['current_password'], $user->password)) {
                throw ValidationException::withMessages([
                    'current_password' => 'Enter your current password before setting a new password.',
                ]);
            }
        }

        DB::transaction(function () use ($user, $data) {
            $user->fill([
                'name' => $data['name'],
                'email' => strtolower($data['email']),
                'phone' => $data['phone'] ?? null,
            ]);

            if (! empty($data['password'])) {
                $user->password = $data['password'];
            }

            $user->save();

            $user->profile()->updateOrCreate(
                ['user_id' => $user->id],
                [
                    'branch_id' => $data['branch_id'] ?? null,
                    'surname' => $data['surname'] ?? null,
                    'given_name' => $data['given_name'] ?? null,
                    'other_name' => $data['other_name'] ?? null,
                    'gender' => $data['gender'] ?? null,
                    'date_of_birth' => $data['date_of_birth'] ?? null,
                    'country' => $data['country'] ?? null,
                    'district' => $data['district'] ?? null,
                    'location' => $data['location'] ?? null,
                    'is_pwd' => (bool) ($data['is_pwd'] ?? false),
                    'education_level' => $data['education_level'] ?? null,
                    'employment_status' => $data['employment_status'] ?? null,
                    'career_interests' => $data['career_interests'] ?? null,
                    'preferred_language' => $data['preferred_language'] ?? 'English',
                ]
            );
        });

        return back()->with('success', 'Profile updated successfully.');
    }
}
'@ | Set-Content -Encoding UTF8 app\Http\Controllers\Participant\ProfileController.php

@'
<?php

namespace App\Http\Controllers\Participant;

use App\Http\Controllers\Controller;
use App\Models\UserNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function index(Request $request): View
    {
        $query = UserNotification::query()
            ->where('user_id', $request->user()->id);

        if ($request->get('status') === 'unread') {
            $query->whereNull('read_at');
        }

        if ($request->get('status') === 'read') {
            $query->whereNotNull('read_at');
        }

        if ($request->filled('search')) {
            $search = trim((string) $request->get('search'));

            $query->where(function ($inner) use ($search) {
                $inner->where('title', 'like', "%{$search}%")
                    ->orWhere('message', 'like', "%{$search}%");
            });
        }

        return view('notifications.index', [
            'notifications' => $query->latest()->paginate(15)->withQueryString(),
            'unreadCount' => UserNotification::query()
                ->where('user_id', $request->user()->id)
                ->whereNull('read_at')
                ->count(),
        ]);
    }

    public function read(Request $request, UserNotification $notification): RedirectResponse
    {
        abort_unless($notification->user_id === $request->user()->id, 403);

        if ($notification->read_at === null) {
            $notification->update(['read_at' => now()]);
        }

        if ($notification->action_url) {
            return redirect()->to($notification->action_url);
        }

        return back()->with('success', 'Notification marked as read.');
    }

    public function readAll(Request $request): RedirectResponse
    {
        UserNotification::query()
            ->where('user_id', $request->user()->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return back()->with('success', 'All notifications marked as read.');
    }
}
'@ | Set-Content -Encoding UTF8 app\Http\Controllers\Participant\NotificationController.php

@'
@extends('layouts.app')

@section('title', 'Dashboard | ElevateHer360')

@section('content')

<div class="page-header">
    <div>
        <div class="eyebrow">Participant Dashboard</div>
        <h1>Welcome, {{ $user->profile?->given_name ?: $user->name }}</h1>
        <p>Continue your learning, mentorship, career and opportunity journey.</p>
    </div>

    <div class="d-flex gap-2 flex-wrap">
        <a href="{{ route('profile.edit') }}" class="btn btn-outline btn-sm">
            <i class="fas fa-user-pen"></i> Profile
        </a>
        <a href="{{ route('notifications.index') }}" class="btn btn-primary btn-sm">
            <i class="fas fa-bell"></i> Notifications
            @if($unreadNotifications > 0)
                <span class="badge badge-gold">{{ $unreadNotifications }}</span>
            @endif
        </a>
    </div>
</div>

<div class="stats-grid mb-3">
    <a href="{{ route('learning.my-courses') }}" class="stat-card">
        <div class="stat-content">
            <div class="stat-label">Learning</div>
            <div class="stat-value">{{ $enrolments->count() }}</div>
            <div class="stat-note">Recent course enrolments</div>
        </div>
        <div class="stat-icon"><i class="fas fa-graduation-cap"></i></div>
    </a>

    <a href="{{ route('mentorship.dashboard') }}" class="stat-card gold">
        <div class="stat-content">
            <div class="stat-label">Mentorship</div>
            <div class="stat-value">{{ $activeMentorship ? 'Active' : '—' }}</div>
            <div class="stat-note">Mentor connection</div>
        </div>
        <div class="stat-icon"><i class="fas fa-handshake"></i></div>
    </a>

    <a href="{{ route('jobs.applications') }}" class="stat-card success">
        <div class="stat-content">
            <div class="stat-label">Applications</div>
            <div class="stat-value">{{ $jobApplications->count() }}</div>
            <div class="stat-note">Recent job applications</div>
        </div>
        <div class="stat-icon"><i class="fas fa-briefcase"></i></div>
    </a>

    <a href="{{ route('career.resume.index') }}" class="stat-card warning">
        <div class="stat-content">
            <div class="stat-label">Resumes</div>
            <div class="stat-value">{{ $resumeCount }}</div>
            <div class="stat-note">Career documents</div>
        </div>
        <div class="stat-icon"><i class="fas fa-file-lines"></i></div>
    </a>
</div>

<div class="grid-2 mb-3">
    <section class="card">
        <div class="card-header">
            <div>
                <h2 class="card-title">My Learning</h2>
                <p class="card-subtitle">Your most recently updated courses</p>
            </div>
            <a href="{{ route('learning.my-courses') }}" class="btn btn-outline btn-sm">View all</a>
        </div>

        @forelse($enrolments as $enrolment)
            <div class="journey-item">
                <div class="feature-icon"><i class="fas fa-book-open"></i></div>
                <div style="flex:1">
                    <strong>{{ $enrolment->course?->title ?? 'Course' }}</strong>
                    <div class="text-muted">{{ number_format((float) $enrolment->progress_percent, 0) }}% complete</div>
                    <div class="progress mt-1">
                        <div class="progress-bar" style="width: {{ min(100, (float) $enrolment->progress_percent) }}%"></div>
                    </div>
                </div>
            </div>
        @empty
            <div class="empty-state">
                <div class="empty-state-icon"><i class="fas fa-graduation-cap"></i></div>
                <h3>No enrolled courses yet</h3>
                <p>Explore available courses and start learning.</p>
                <a href="{{ route('learning.index') }}" class="btn btn-primary btn-sm">Explore Learning</a>
            </div>
        @endforelse
    </section>

    <section class="card">
        <div class="card-header">
            <div>
                <h2 class="card-title">Upcoming Calendar</h2>
                <p class="card-subtitle">Activities and important dates</p>
            </div>
            <a href="{{ route('calendar.index') }}" class="btn btn-outline btn-sm">Calendar</a>
        </div>

        @forelse($upcomingEvents as $event)
            <div class="journey-item">
                <div class="feature-icon"><i class="fas fa-calendar-day"></i></div>
                <div>
                    <strong>{{ $event->title }}</strong>
                    <div class="text-muted">
                        {{ $event->starts_at?->format('d M Y, H:i') }}
                    </div>
                </div>
            </div>
        @empty
            <div class="empty-state">
                <div class="empty-state-icon"><i class="fas fa-calendar-check"></i></div>
                <h3>No upcoming events</h3>
                <p>Your upcoming programme activities will appear here.</p>
            </div>
        @endforelse
    </section>
</div>

<section class="section-header">
    <div class="eyebrow">Your journey</div>
    <h2>Everything you need in one place</h2>
    <p>Access all participant services from your connected ElevateHer360 account.</p>
</section>

<div class="features">
    <a href="{{ route('learning.index') }}" class="feature-link">
        <article class="feature">
            <div class="feature-icon"><i class="fas fa-graduation-cap"></i></div>
            <h3>Learning</h3>
            <p>Courses, lessons, assessments, progress and certificates.</p>
            <div class="feature-arrow">Open Learning <i class="fas fa-arrow-right"></i></div>
        </article>
    </a>

    <a href="{{ route('mentorship.dashboard') }}" class="feature-link">
        <article class="feature">
            <div class="feature-icon"><i class="fas fa-handshake"></i></div>
            <h3>Mentorship</h3>
            <p>Mentor connections, sessions, goals and milestones.</p>
            <div class="feature-arrow">Open Mentorship <i class="fas fa-arrow-right"></i></div>
        </article>
    </a>

    <a href="{{ route('jobs.index') }}" class="feature-link">
        <article class="feature">
            <div class="feature-icon"><i class="fas fa-briefcase"></i></div>
            <h3>Jobs</h3>
            <p>Discover opportunities and manage your applications.</p>
            <div class="feature-arrow">View Jobs <i class="fas fa-arrow-right"></i></div>
        </article>
    </a>

    <a href="{{ route('career.resume.index') }}" class="feature-link">
        <article class="feature">
            <div class="feature-icon"><i class="fas fa-file-lines"></i></div>
            <h3>Resume Builder</h3>
            <p>Create and manage professional career documents.</p>
            <div class="feature-arrow">Manage Resume <i class="fas fa-arrow-right"></i></div>
        </article>
    </a>

    <a href="{{ route('library.index') }}" class="feature-link">
        <article class="feature">
            <div class="feature-icon"><i class="fas fa-book-open"></i></div>
            <h3>Digital Library</h3>
            <p>Resources supporting learning and career development.</p>
            <div class="feature-arrow">Browse Library <i class="fas fa-arrow-right"></i></div>
        </article>
    </a>

    <a href="{{ route('calendar.index') }}" class="feature-link">
        <article class="feature">
            <div class="feature-icon"><i class="fas fa-calendar-days"></i></div>
            <h3>Calendar</h3>
            <p>Programme activities, sessions, deadlines and events.</p>
            <div class="feature-arrow">View Calendar <i class="fas fa-arrow-right"></i></div>
        </article>
    </a>

    <a href="{{ route('profile.edit') }}" class="feature-link">
        <article class="feature">
            <div class="feature-icon"><i class="fas fa-user-pen"></i></div>
            <h3>My Profile</h3>
            <p>Keep your personal, education and career information current.</p>
            <div class="feature-arrow">Update Profile <i class="fas fa-arrow-right"></i></div>
        </article>
    </a>

    <a href="{{ route('notifications.index') }}" class="feature-link">
        <article class="feature">
            <div class="feature-icon"><i class="fas fa-bell"></i></div>
            <h3>Notifications</h3>
            <p>Stay informed about activities, opportunities and updates.</p>
            <div class="feature-arrow">View Notifications <i class="fas fa-arrow-right"></i></div>
        </article>
    </a>
</div>

@endsection
'@ | Set-Content -Encoding UTF8 resources\views\dashboard.blade.php

@'
@extends('layouts.app')

@section('title', 'My Profile | ElevateHer360')

@section('content')

<div class="page-header">
    <div>
        <div class="eyebrow">My Account</div>
        <h1>Profile & Preferences</h1>
        <p>Keep your participant information accurate and up to date.</p>
    </div>
    <a href="{{ route('dashboard') }}" class="btn btn-outline btn-sm">
        <i class="fas fa-arrow-left"></i> Dashboard
    </a>
</div>

<form method="POST" action="{{ route('profile.update') }}" id="profileForm">
    @csrf
    @method('PUT')

    <div class="tabs profile-tabs" role="tablist">
        <button type="button" class="tab active" data-profile-tab="personal">Personal</button>
        <button type="button" class="tab" data-profile-tab="education">Education & Career</button>
        <button type="button" class="tab" data-profile-tab="preferences">Preferences</button>
        <button type="button" class="tab" data-profile-tab="security">Security</button>
    </div>

    <section class="card profile-panel" data-profile-panel="personal">
        <div class="card-header">
            <div>
                <h2 class="card-title">Personal Information</h2>
                <p class="card-subtitle">Basic account and contact details.</p>
            </div>
        </div>

        <div class="grid">
            <div class="form-group">
                <label for="name" class="required">Display name</label>
                <input id="name" name="name" value="{{ old('name', $user->name) }}" placeholder="Enter your full name" required>
            </div>

            <div class="form-group">
                <label for="email" class="required">Email address</label>
                <input id="email" type="email" name="email" value="{{ old('email', $user->email) }}" placeholder="e.g. name@example.com" required>
            </div>

            <div class="form-group">
                <label for="phone">Phone number</label>
                <input id="phone" type="tel" name="phone" value="{{ old('phone', $user->phone) }}" placeholder="e.g. +256 700 000000">
            </div>

            <div class="form-group">
                <label for="branch_id">Branch</label>
                <select id="branch_id" name="branch_id">
                    <option value="">Select branch</option>
                    @foreach($branches as $branch)
                        <option value="{{ $branch->id }}" @selected(old('branch_id', $user->profile?->branch_id) == $branch->id)>
                            {{ $branch->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label for="surname">Surname</label>
                <input id="surname" name="surname" value="{{ old('surname', $user->profile?->surname) }}" placeholder="Enter your surname">
            </div>

            <div class="form-group">
                <label for="given_name">Given name</label>
                <input id="given_name" name="given_name" value="{{ old('given_name', $user->profile?->given_name) }}" placeholder="Enter your given name">
            </div>

            <div class="form-group">
                <label for="other_name">Other name</label>
                <input id="other_name" name="other_name" value="{{ old('other_name', $user->profile?->other_name) }}" placeholder="Enter other name if applicable">
            </div>

            <div class="form-group">
                <label for="gender">Gender</label>
                <select id="gender" name="gender">
                    <option value="">Select gender</option>
                    @foreach(['female' => 'Female', 'male' => 'Male', 'other' => 'Other', 'prefer_not_to_say' => 'Prefer not to say'] as $value => $label)
                        <option value="{{ $value }}" @selected(old('gender', $user->profile?->gender) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label for="date_of_birth">Date of birth</label>
                <input id="date_of_birth" type="date" name="date_of_birth" value="{{ old('date_of_birth', $user->profile?->date_of_birth?->format('Y-m-d')) }}">
            </div>

            <div class="form-group">
                <label for="country">Country</label>
                <input id="country" name="country" value="{{ old('country', $user->profile?->country ?? 'Uganda') }}" placeholder="e.g. Uganda">
            </div>

            <div class="form-group">
                <label for="district">District</label>
                <input id="district" name="district" value="{{ old('district', $user->profile?->district) }}" placeholder="e.g. Kampala">
            </div>

            <div class="form-group">
                <label for="location">Location</label>
                <input id="location" name="location" value="{{ old('location', $user->profile?->location) }}" placeholder="Enter town, area or address">
            </div>
        </div>

        <div class="checkbox-row">
            <input id="is_pwd" type="checkbox" name="is_pwd" value="1" @checked(old('is_pwd', $user->profile?->is_pwd))>
            <label for="is_pwd">I am a person with a disability</label>
        </div>
    </section>

    <section class="card profile-panel" data-profile-panel="education" hidden>
        <div class="card-header">
            <div>
                <h2 class="card-title">Education & Career</h2>
                <p class="card-subtitle">Help us tailor learning and opportunity support.</p>
            </div>
        </div>

        <div class="grid">
            <div class="form-group">
                <label for="education_level">Education level</label>
                <input id="education_level" name="education_level" value="{{ old('education_level', $user->profile?->education_level) }}" placeholder="e.g. Diploma, Bachelor's degree">
            </div>

            <div class="form-group">
                <label for="employment_status">Employment status</label>
                <input id="employment_status" name="employment_status" value="{{ old('employment_status', $user->profile?->employment_status) }}" placeholder="e.g. Student, Employed, Self-employed">
            </div>
        </div>

        <div class="form-group">
            <label for="career_interests">Career interests</label>
            <textarea id="career_interests" name="career_interests" placeholder="e.g. Software development, data analysis, digital marketing, entrepreneurship">{{ old('career_interests', $user->profile?->career_interests) }}</textarea>
        </div>
    </section>

    <section class="card profile-panel" data-profile-panel="preferences" hidden>
        <div class="card-header">
            <div>
                <h2 class="card-title">Preferences</h2>
                <p class="card-subtitle">Set your preferred platform language.</p>
            </div>
        </div>

        <div class="form-group">
            <label for="preferred_language">Preferred language</label>
            <select id="preferred_language" name="preferred_language">
                @foreach(['English', 'Luganda', 'Kiswahili'] as $language)
                    <option value="{{ $language }}" @selected(old('preferred_language', $user->profile?->preferred_language ?? 'English') === $language)>
                        {{ $language }}
                    </option>
                @endforeach
            </select>
        </div>
    </section>

    <section class="card profile-panel" data-profile-panel="security" hidden>
        <div class="card-header">
            <div>
                <h2 class="card-title">Security</h2>
                <p class="card-subtitle">Leave these fields blank if you do not want to change your password.</p>
            </div>
        </div>

        <div class="grid">
            <div class="form-group">
                <label for="current_password">Current password</label>
                <input id="current_password" type="password" name="current_password" placeholder="Enter your current password" autocomplete="current-password">
            </div>

            <div class="form-group">
                <label for="password">New password</label>
                <input id="password" type="password" name="password" placeholder="Create a new password" autocomplete="new-password">
            </div>

            <div class="form-group">
                <label for="password_confirmation">Confirm new password</label>
                <input id="password_confirmation" type="password" name="password_confirmation" placeholder="Re-enter your new password" autocomplete="new-password">
            </div>
        </div>
    </section>

    <div class="d-flex justify-content-between gap-2 flex-wrap mt-3">
        <a href="{{ route('dashboard') }}" class="btn btn-outline">
            <i class="fas fa-xmark"></i> Cancel
        </a>
        <button type="submit" class="btn btn-primary">
            <i class="fas fa-floppy-disk"></i> Save Changes
        </button>
    </div>
</form>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const tabs = document.querySelectorAll('[data-profile-tab]');
    const panels = document.querySelectorAll('[data-profile-panel]');

    tabs.forEach(function (tab) {
        tab.addEventListener('click', function () {
            const target = tab.dataset.profileTab;

            tabs.forEach(item => item.classList.toggle('active', item === tab));
            panels.forEach(panel => {
                panel.hidden = panel.dataset.profilePanel !== target;
            });
        });
    });
});
</script>
@endpush
'@ | Set-Content -Encoding UTF8 resources\views\profile\edit.blade.php

@'
@extends('layouts.app')

@section('title', 'Notifications | ElevateHer360')

@section('content')

<div class="page-header">
    <div>
        <div class="eyebrow">Updates</div>
        <h1>Notifications</h1>
        <p>{{ $unreadCount }} unread {{ \Illuminate\Support\Str::plural('notification', $unreadCount) }}.</p>
    </div>

    @if($unreadCount > 0)
        <form method="POST" action="{{ route('notifications.read-all') }}">
            @csrf
            @method('PATCH')
            <button type="submit" class="btn btn-outline btn-sm">
                <i class="fas fa-check-double"></i> Mark all as read
            </button>
        </form>
    @endif
</div>

<form method="GET" action="{{ route('notifications.index') }}" class="filters">
    <div class="filter-item">
        <label for="notification_search">Search</label>
        <div class="search-box">
            <i class="fas fa-magnifying-glass search-icon"></i>
            <input id="notification_search" name="search" value="{{ request('search') }}" placeholder="Search notifications...">
        </div>
    </div>

    <div class="filter-item">
        <label for="status">Status</label>
        <select id="status" name="status">
            <option value="">All notifications</option>
            <option value="unread" @selected(request('status') === 'unread')>Unread</option>
            <option value="read" @selected(request('status') === 'read')>Read</option>
        </select>
    </div>

    <button class="btn btn-primary" type="submit">
        <i class="fas fa-filter"></i> Filter
    </button>

    @if(request()->hasAny(['search', 'status']))
        <a href="{{ route('notifications.index') }}" class="btn btn-outline">Clear</a>
    @endif
</form>

<div class="grid-1">
    @forelse($notifications as $notification)
        <article class="card {{ $notification->read_at ? '' : 'card-gold' }}">
            <div class="card-header">
                <div>
                    <h2 class="card-title">
                        @if(!$notification->read_at)
                            <i class="fas fa-circle text-secondary" style="font-size:.5rem;vertical-align:middle"></i>
                        @endif
                        {{ $notification->title }}
                    </h2>
                    <p class="card-subtitle">
                        {{ $notification->created_at?->diffForHumans() }}
                        · {{ $notification->type }}
                    </p>
                </div>

                <span class="badge {{ $notification->read_at ? '' : 'badge-gold' }}">
                    {{ $notification->read_at ? 'Read' : 'Unread' }}
                </span>
            </div>

            @if($notification->message)
                <p>{{ $notification->message }}</p>
            @endif

            @if(!$notification->read_at || $notification->action_url)
                <div class="card-footer">
                    <form method="POST" action="{{ route('notifications.read', $notification) }}">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="btn btn-primary btn-sm">
                            <i class="fas {{ $notification->action_url ? 'fa-arrow-right' : 'fa-check' }}"></i>
                            {{ $notification->action_url ? 'Open' : 'Mark as read' }}
                        </button>
                    </form>
                </div>
            @endif
        </article>
    @empty
        <div class="empty-state card">
            <div class="empty-state-icon"><i class="fas fa-bell-slash"></i></div>
            <h3>No notifications found</h3>
            <p>New updates and reminders will appear here.</p>
        </div>
    @endforelse
</div>

{{ $notifications->links() }}

@endsection
'@ | Set-Content -Encoding UTF8 resources\views\notifications\index.blade.php

@'
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserNotification extends Model
{
    protected $fillable = [
        'user_id',
        'type',
        'title',
        'message',
        'action_url',
        'data',
        'read_at',
    ];

    protected $casts = [
        'data' => 'array',
        'read_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
'@ | Set-Content -Encoding UTF8 app\Models\UserNotification.php

@'
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('profiles') && ! Schema::hasColumn('profiles', 'branch_id')) {
            Schema::table('profiles', function (Blueprint $table) {
                $table->foreignId('branch_id')
                    ->nullable()
                    ->after('user_id')
                    ->constrained('branches')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('profiles') && Schema::hasColumn('profiles', 'branch_id')) {
            Schema::table('profiles', function (Blueprint $table) {
                $table->dropConstrainedForeignId('branch_id');
            });
        }
    }
};
'@ | Set-Content -Encoding UTF8 database\migrations\2026_09_22_160000_add_branch_id_to_profiles_table.php

# Update phase 2 routes safely.
$routeFile = "routes\web.phase2.php"
$route = Get-Content $routeFile -Raw

if ($route -notmatch "Controllers\\Participant\\DashboardController") {
    $route = $route.Replace(
        "use App\Http\Controllers\Auth\StaffAuthController;",
        "use App\Http\Controllers\Auth\StaffAuthController;`r`nuse App\Http\Controllers\Participant\DashboardController;`r`nuse App\Http\Controllers\Participant\NotificationController;`r`nuse App\Http\Controllers\Participant\ProfileController;"
    )
}

$oldDashboard = @"
Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('/dashboard', 'dashboard')->name('dashboard');
});
"@

$newDashboard = @"
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');

    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::patch('/notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.read-all');
    Route::patch('/notifications/{notification}/read', [NotificationController::class, 'read'])->name('notifications.read');
});
"@

if ($route.Contains($oldDashboard)) {
    $route = $route.Replace($oldDashboard, $newDashboard)
} elseif ($route -notmatch "notifications/read-all") {
    throw "Could not find the expected participant dashboard route block in routes\web.phase2.php. No route changes were written."
}

Set-Content -Encoding UTF8 $routeFile $route

Write-Host ""
Write-Host "Participant core files created." -ForegroundColor Green
Write-Host "Running Laravel verification..." -ForegroundColor Cyan

composer dump-autoload
php artisan optimize:clear
php artisan migrate
php artisan route:list --name=dashboard
php artisan route:list --name=profile
php artisan route:list --name=notifications

Write-Host ""
Write-Host "Now run: php artisan test" -ForegroundColor Yellow
Write-Host "Then: npm run build" -ForegroundColor Yellow
