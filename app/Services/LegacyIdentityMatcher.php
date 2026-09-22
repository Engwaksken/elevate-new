<?php

namespace App\Services;

use App\Models\User;

class LegacyIdentityMatcher
{
    public function match(array $record): array
    {
        $email = isset($record['email']) ? strtolower(trim((string)$record['email'])) : null;
        $phone = isset($record['phone']) ? preg_replace('/\D+/', '', (string)$record['phone']) : null;

        if ($email) {
            $user = User::whereRaw('LOWER(email) = ?', [$email])->first();
            if ($user) {
                return ['status'=>'matched_email','user'=>$user];
            }
        }

        if ($phone) {
            $candidates = User::whereNotNull('phone')->get()->filter(
                fn($u) => preg_replace('/\D+/', '', (string)$u->phone) === $phone
            );

            if ($candidates->count() === 1) {
                return ['status'=>'matched_phone','user'=>$candidates->first()];
            }

            if ($candidates->count() > 1) {
                return ['status'=>'ambiguous_phone','user'=>null];
            }
        }

        return ['status'=>'unmatched','user'=>null];
    }
}
