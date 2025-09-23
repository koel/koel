<?php

namespace App\Rules;

use App\Enums\Acl\Role;
use App\Facades\License;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class AvailableRole implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param Closure(string, ?string=): PotentiallyTranslatedString $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (License::isPlus()) {
            return;
        }

        if (!Role::from($value)->available()) {
            $fail("Invalid role $value.");
        }
    }
}
