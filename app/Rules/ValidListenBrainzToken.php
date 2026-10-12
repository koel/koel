<?php

namespace App\Rules;

use App\Services\Integrations\ListenBrainzService;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidListenBrainzToken implements ValidationRule
{
    /** @param string $value */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!app(ListenBrainzService::class)->validateToken($value)) {
            $fail('Invalid ListenBrainz token.');
        }
    }
}
