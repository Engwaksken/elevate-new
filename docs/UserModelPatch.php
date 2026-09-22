<?php
// Merge these members into App\Models\User.
public function profile(): \Illuminate\Database\Eloquent\Relations\HasOne { return $this->hasOne(\App\Models\Profile::class); }
public function roles(): \Illuminate\Database\Eloquent\Relations\BelongsToMany { return $this->belongsToMany(\App\Models\Role::class)->withTimestamps(); }
public function hasRole(string|array $roles): bool { return $this->roles()->whereIn('slug',(array)$roles)->exists(); }
public function hasPermission(string $permission): bool { return $this->roles()->whereHas('permissions',fn($q)=>$q->where('slug',$permission))->exists(); }
public function isStaff(): bool { return $this->user_type === 'staff'; }
