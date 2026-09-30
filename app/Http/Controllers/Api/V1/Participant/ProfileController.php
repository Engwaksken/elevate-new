<?php

namespace App\Http\Controllers\Api\V1\Participant;

use App\Http\Controllers\Controller;
use App\Models\Profile;
use App\Models\User;
use App\Services\Participant\ParticipantLessonService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * Participant self-service profile: GET/PUT /profile, photo upload/stream/delete, password change.
 */
class ProfileController extends Controller
{
    private const PHOTO_DISK = 'local';

    /** Fields the participant may change herself. Email, branch, user_type, status stay admin-owned. */
    public const EDITABLE_FIELDS = [
        'name', 'phone',
        'surname', 'given_name', 'other_name',
        'gender', 'date_of_birth',
        'country', 'district', 'location',
        'education_level', 'employment_status', 'career_interests', 'preferred_language',
        'is_pwd', 'disability_types', 'disability_other',
    ];

    private const PROFILE_FIELDS = [
        'surname', 'given_name', 'other_name', 'gender', 'date_of_birth',
        'country', 'district', 'location', 'education_level', 'employment_status',
        'career_interests', 'preferred_language', 'is_pwd', 'disability_types', 'disability_other',
    ];

    public function __construct(private readonly ParticipantLessonService $lessons)
    {
    }

    public function show(Request $request)
    {
        return response()->json($this->payload($request->user()));
    }

    public function update(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:30'],
            'surname' => ['sometimes', 'nullable', 'string', 'max:120'],
            'given_name' => ['sometimes', 'nullable', 'string', 'max:120'],
            'other_name' => ['sometimes', 'nullable', 'string', 'max:120'],
            'gender' => ['sometimes', 'nullable', Rule::in(['female', 'male', 'other', 'prefer_not_to_say'])],
            'date_of_birth' => ['sometimes', 'nullable', 'date', 'after:1900-01-01', 'before:today'],
            'country' => ['sometimes', 'nullable', 'string', 'max:190'],
            'district' => ['sometimes', 'nullable', 'string', 'max:190'],
            'location' => ['sometimes', 'nullable', 'string', 'max:190'],
            'education_level' => ['sometimes', 'nullable', 'string', 'max:190'],
            'employment_status' => ['sometimes', 'nullable', 'string', 'max:190'],
            'career_interests' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'preferred_language' => ['sometimes', 'nullable', 'string', 'max:50'],
            'is_pwd' => ['sometimes', 'boolean'],
            'disability_types' => ['sometimes', 'nullable', 'array', 'max:20'],
            'disability_types.*' => ['nullable', 'string', 'max:100'],
            'disability_other' => ['sometimes', 'nullable', 'string', 'max:255'],
        ]);

        $userFields = array_intersect_key($data, array_flip(['name', 'phone']));
        if ($userFields !== []) {
            $user->forceFill($userFields)->save();
        }

        $profileFields = array_intersect_key($data, array_flip(self::PROFILE_FIELDS));
        if ($profileFields !== []) {
            if (array_key_exists('preferred_language', $profileFields) && blank($profileFields['preferred_language'])) {
                $profileFields['preferred_language'] = 'English';
            }
            if (array_key_exists('disability_types', $profileFields)) {
                $profileFields['disability_types'] = array_values(array_filter(
                    array_map(fn ($value) => trim((string) $value), (array) $profileFields['disability_types']),
                    fn ($value) => $value !== ''
                ));
            }

            $profile = $this->profileFor($user);
            $profile->fill($profileFields);
            $profile->user_id = $user->id;
            $profile->save();
        }

        return response()->json($this->payload($user->fresh()));
    }

    public function uploadPhoto(Request $request)
    {
        $request->validate([
            'photo' => ['required', 'file', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $user = $request->user();
        $profile = $this->profileFor($user);
        $old = $profile->photo_path;

        $path = $request->file('photo')->store('profile-photos/'.$user->id, self::PHOTO_DISK);

        $profile->user_id = $user->id;
        $profile->photo_path = $path;
        $profile->save();

        if ($old && $old !== $path) {
            Storage::disk(self::PHOTO_DISK)->delete($old);
        }

        return response()->json(['message' => 'Profile photo updated.'] + $this->payload($user->fresh()));
    }

    public function photo(Request $request)
    {
        $path = $this->profileFor($request->user())->photo_path;

        abort_unless($path && Storage::disk(self::PHOTO_DISK)->exists($path), 404, 'No profile photo.');

        return $this->lessons->fileResponse(self::PHOTO_DISK, $path, 'profile-photo.'.pathinfo($path, PATHINFO_EXTENSION), null, true);
    }

    public function deletePhoto(Request $request)
    {
        $user = $request->user();
        $profile = $this->profileFor($user);

        if ($profile->photo_path) {
            Storage::disk(self::PHOTO_DISK)->delete($profile->photo_path);
            $profile->photo_path = null;
            $profile->save();
        }

        return response()->json(['message' => 'Profile photo removed.'] + $this->payload($user->fresh()));
    }

    public function updatePassword(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'confirmed', Password::defaults(), 'different:current_password'],
        ]);

        if (! Hash::check($data['current_password'], (string) $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => 'The current password is incorrect.',
            ]);
        }

        $user->forceFill(['password' => Hash::make($data['password'])])->save();

        // Sign out the participant's other devices; keep the token used for this request.
        $current = $user->currentAccessToken();
        if ($current instanceof PersonalAccessToken && $current->exists && $current->getKey()) {
            $user->tokens()->whereKeyNot($current->getKey())->delete();
        }

        return response()->noContent();
    }

    /**
     * Always read the profile row fresh (the authenticated user instance may carry a stale relation).
     */
    private function profileFor(User $user): Profile
    {
        return Profile::where('user_id', $user->id)->first() ?? new Profile(['user_id' => $user->id]);
    }

    private function payload(User $user): array
    {
        $profile = Profile::with('branch')->where('user_id', $user->id)->first();
        $photoPath = $profile?->photo_path;
        $hasPhoto = $photoPath && Storage::disk(self::PHOTO_DISK)->exists($photoPath);
        $version = $hasPhoto ? substr(md5($photoPath), 0, 12) : null;

        return [
            'profile' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'photo_url' => $hasPhoto ? route('api.participant.profile.photo', ['v' => $version]) : null,
                'photo_path' => $hasPhoto ? '/profile/photo?v='.$version : null,
                'has_photo' => (bool) $hasPhoto,
                'surname' => $profile?->surname,
                'given_name' => $profile?->given_name,
                'other_name' => $profile?->other_name,
                'gender' => $profile?->gender,
                'date_of_birth' => $profile?->date_of_birth?->format('Y-m-d'),
                'country' => $profile?->country,
                'district' => $profile?->district,
                'location' => $profile?->location,
                'education_level' => $profile?->education_level,
                'employment_status' => $profile?->employment_status,
                'career_interests' => $profile?->career_interests,
                'preferred_language' => $profile?->preferred_language,
                'is_pwd' => (bool) ($profile?->is_pwd ?? false),
                'disability_types' => array_values((array) ($profile?->disability_types ?? [])),
                'disability_other' => $profile?->disability_other,
                'branch' => $profile?->branch ? ['id' => $profile->branch->id, 'name' => $profile->branch->name] : null,
                'user_type' => $user->user_type,
                'status' => $user->status,
                'updated_at' => ($profile?->updated_at ?? $user->updated_at)?->toIso8601String(),
            ],
            'editable_fields' => self::EDITABLE_FIELDS,
        ];
    }
}
