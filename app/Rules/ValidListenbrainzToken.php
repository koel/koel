<?php

namespace App\Rules;

use App\Services\Integrations\ListenbrainzService;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidListenbrainzToken implements ValidationRule
{
    /** @param string $value */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!app(ListenbrainzService::class)->validateToken($value)) {
            $fail('ListenBrainz did not accept this token.');
        }
    }
}
