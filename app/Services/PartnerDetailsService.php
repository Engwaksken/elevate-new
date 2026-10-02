<?php

namespace App\Services;

use App\Models\Employer;
use App\Models\MentorProfile;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PartnerDetailsService
{
    public function save(Request $request, string $type, MentorProfile|Employer|null $profile = null, bool $public = false): MentorProfile|Employer
    {
        if (is_string($email = $request->input('email'))) $request->merge(['email' => strtolower(trim($email))]);
        $user = $profile ? ($type === 'mentor' ? $profile->user : $profile->owner) : null;
        if (! $public && ! $profile) {
            $request->validate(['user_id' => ['nullable', 'integer', Rule::exists('users', 'id')->where('user_type', 'participant')]]);
        }
        $selectedId = ! $public && ! $profile ? $request->input('user_id') : null;
        $rules = [
            'name' => [$selectedId ? 'nullable' : 'required', 'string', 'max:190'],
            'email' => [$selectedId ? 'nullable' : 'required', 'email', 'max:190', Rule::unique('users', 'email')->ignore($user?->id ?? $selectedId)],
            'phone' => ['nullable', 'string', 'max:30'],
            'country' => ['nullable', 'string', 'max:100'],
        ];
        if (! $public) {
            $rules['user_id'] = ['nullable', 'integer', Rule::exists('users', 'id')->where('user_type', 'participant')];
            $rules['status'] = ['required', 'in:pending,approved,rejected'];
        } else {
            $rules['password'] = ['required', 'string', 'min:8', 'max:72', 'confirmed'];
            $rules['consent'] = ['accepted'];
        }
        if ($type === 'mentor') {
            $rules += [
                'organisation' => ['nullable', 'string', 'max:190'], 'job_title' => ['nullable', 'string', 'max:190'],
                'industry' => ['nullable', 'string', 'max:190'], 'years_experience' => ['nullable', 'integer', 'min:0', 'max:80'],
                'professional_bio' => ['nullable', 'string', 'max:5000'], 'linkedin_url' => ['nullable', 'url:http,https', 'max:255'],
                'skills_text' => ['nullable', 'string', 'max:2000'], 'languages_text' => ['nullable', 'string', 'max:1000'],
                'mentoring_areas_text' => ['nullable', 'string', 'max:2000'],
            ];
        } else {
            $rules += [
                'company_name' => ['required', 'string', 'max:190'], 'company_type' => ['nullable', 'string', 'max:100'],
                'industry' => ['nullable', 'string', 'max:150'], 'website' => ['nullable', 'url:http,https', 'max:255'],
                'contact_person' => ['nullable', 'string', 'max:190'], 'location' => ['nullable', 'string', 'max:190'],
                'description' => ['nullable', 'string', 'max:10000'],
            ];
        }
        $data = $request->validate($rules);

        return DB::transaction(function () use ($data, $type, $profile, $public, $user, $selectedId) {
            if ($selectedId) {
                $user = User::whereKey($selectedId)->lockForUpdate()->firstOrFail();
                $duplicate = $type === 'mentor' ? MentorProfile::where('user_id', $user->id)->exists() : Employer::where('owner_user_id', $user->id)->exists();
                if ($duplicate) throw \Illuminate\Validation\ValidationException::withMessages(['user_id' => 'This account already has a profile. Edit that profile instead.']);
            } elseif ($user) {
                $user->update(collect($data)->only(['name', 'email', 'phone'])->all());
            } else {
                $user = User::create([
                    'name' => $data['name'], 'email' => strtolower($data['email']), 'phone' => $data['phone'] ?? null,
                    'password' => $public ? $data['password'] : Str::random(64),
                    'user_type' => 'participant', 'status' => $public ? 'pending' : 'active',
                ]);
            }
            $role = Role::firstOrCreate(['slug' => $type], ['name' => ucfirst($type), 'is_system' => true]);
            $user->roles()->syncWithoutDetaching([$role->id]);
            $fields = $type === 'mentor'
                ? ['organisation', 'job_title', 'industry', 'years_experience', 'professional_bio', 'linkedin_url', 'country']
                : ['company_name', 'company_type', 'industry', 'website', 'contact_person', 'email', 'phone', 'country', 'location', 'description'];
            $attributes = collect($data)->only($fields)->all();
            if ($type === 'mentor') {
                foreach (['skills', 'languages', 'mentoring_areas'] as $key) {
                    $attributes[$key] = array_values(array_filter(array_map('trim', explode(',', $data[$key.'_text'] ?? ''))));
                }
            }
            $attributes['status'] = $public ? 'pending' : $data['status'];
            $attributes['approved_at'] = $attributes['status'] === 'approved' ? now() : null;
            $attributes['approved_by'] = $attributes['status'] === 'approved' ? auth()->id() : null;
            if (! $public && $attributes['status'] === 'approved' && $user->status === 'pending') {
                $user->update(['status' => 'active']);
            }
            if ($type === 'employer') {
                $attributes['email'] = $attributes['email'] ?? $user->email;
                $attributes['phone'] = $attributes['phone'] ?? $user->phone;
            }
            if ($profile) {
                $profile->update($attributes);
            } else {
                $profile = $type === 'mentor' ? MentorProfile::create($attributes + ['user_id' => $user->id])
                    : Employer::create($attributes + ['owner_user_id' => $user->id]);
            }
            if ($public) {
                foreach (['privacy_policy', 'terms'] as $consent) {
                    \App\Models\Consent::create(['user_id' => $user->id, 'consent_type' => $consent, 'policy_version' => config('app.policy_version', '1.0'), 'accepted' => true, 'accepted_at' => now(), 'ip_address' => request()->ip()]);
                }
            }
            return $profile;
        });
    }
}
