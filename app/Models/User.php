<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name','email','phone','password','user_type','status','last_login_at','email_verified_at',
    ];

    protected $hidden = ['password','remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    protected static function booted(): void
    {
        // Every participant gets a permanent, human-readable ID, e.g. EH26-000123.
        static::saved(function (User $user) {
            if ($user->user_type === 'participant' && ! $user->participant_code) {
                $user->participant_code = sprintf('EH%s-%06d', ($user->created_at ?? now())->format('y'), $user->id);
                $user->saveQuietly();
            }
        });
    }

    public function profile(): HasOne { return $this->hasOne(Profile::class); }
    public function participantIdAliases(): HasMany { return $this->hasMany(ParticipantIdAlias::class); }
    public function consents(): HasMany { return $this->hasMany(Consent::class); }
    public function goals(): HasMany { return $this->hasMany(ParticipantGoal::class); }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_user')->withTimestamps();
    }

    public function hasRole(string|array $roles): bool
    {
        $roles = array_values(array_filter((array) $roles));
        if ($roles === []) return false;

        return $this->roles()
            ->where(function ($query) use ($roles) {
                $query->whereIn('slug', $roles)->orWhereIn('name', $roles);
            })->exists();
    }

    public function hasAnyRole(string|array ...$roles): bool
    {
        return $this->hasRole(collect($roles)->flatten()->filter()->values()->all());
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasRole([
            'super-administrator','super-admin','administrator',
            'Super Administrator','Super Admin','Administrator',
        ]);
    }

    public function hasPermission(string $permission): bool
    {
        if (! $this->isActive()) return false;
        if ($this->isSuperAdmin()) return true;

        return $this->roles()
            ->whereHas('permissions', function ($query) use ($permission) {
                $query->where('slug', $permission)->orWhere('name', $permission);
            })->exists();
    }

    public function hasAnyPermission(string|array ...$permissions): bool
    {
        $flattened = collect($permissions)->flatten()->filter()->values();

        if ($flattened->isEmpty() || ! $this->isActive()) return false;
        if ($this->isSuperAdmin()) return true;

        return $this->roles()
            ->whereHas('permissions', function ($query) use ($flattened) {
                $query->whereIn('slug', $flattened->all())
                    ->orWhereIn('name', $flattened->all());
            })->exists();
    }

    /**
     * Branches a staff member (e.g. an instructor or trainer) works across.
     * Participants belong to a single branch through their profile.
     */
    public function branches(): BelongsToMany
    {
        return $this->belongsToMany(Branch::class)->withTimestamps();
    }

    public function instructedCourses(): BelongsToMany
    {
        return $this->belongsToMany(Course::class, 'course_instructors', 'user_id', 'course_id')
            ->withPivot('is_lead')
            ->withTimestamps();
    }

    public function isStaff(): bool { return $this->user_type === 'staff'; }
    public function isParticipant(): bool { return $this->user_type === 'participant'; }
    public function isActive(): bool { return $this->status === 'active'; }
}
