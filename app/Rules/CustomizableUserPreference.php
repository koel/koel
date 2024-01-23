<?php

namespace App\Rules;

use App\Values\UserPreferences;
use Illuminate\Contracts\Validation\Rule;

class CustomizableUserPreference implements Rule
{
    public function passes($attribute, $value): bool
    {
        return UserPreferences::customizable($value);
    }

    public function message(): string
    {
        return 'Invalid or uncustomizable user preference key.';
    }
}
