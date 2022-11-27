<?php

namespace App\Rules;

use App\Values\SmartPlaylistRuleGroupCollection;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Arr;

class ValidSmartPlaylistRulePayload implements Rule
{
    public function passes($attribute, $value): bool
    {
        return (bool) attempt(static fn () => SmartPlaylistRuleGroupCollection::create(Arr::wrap($value)));
    }

    public function message(): string
    {
        return 'Invalid smart playlist rules';
    }
}
