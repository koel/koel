<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ConsentService
{
    public function recordConsent(User $user, Request $request): void
    {
        Log::info('User consent recorded', [
            'user_id' => $user->id,
            'email' => $user->email,
            'terms_accepted' => (bool) $request->terms_accepted,
            'privacy_accepted' => (bool) $request->privacy_accepted,
            'age_verified' => (bool) $request->age_verified,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
    }
}
