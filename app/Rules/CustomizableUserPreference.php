<?php

namespace App\Rules;

use App\Values\UserPreferences;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class CustomizableUserPreference implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!UserPreferences::customizable($value)) {
            $fail('Invalid or uncustomizable user preference key.');
        }
    }
}
