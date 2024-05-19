<?php

namespace App\Rules;

use App\Values\SmartPlaylistRuleGroupCollection;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Arr;

class ValidSmartPlaylistRulePayload implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $passes = (bool) attempt(static fn () => SmartPlaylistRuleGroupCollection::create(Arr::wrap($value)));

        if (!$passes) {
            $fail('Invalid smart playlist rules');
        }
    }
}
