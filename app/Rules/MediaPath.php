<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\File;

class MediaPath implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $passes = false;

        if (config('koel.storage_driver') === 'local') {
            $passes = $value && File::isDirectory($value) && File::isReadable($value);
        }

        if (!$passes) {
            $fail(
                config('koel.storage_driver') === 'local'
                    ? 'Media path is required for local storage.'
                    : 'Media path is not required for non-local storage.'
            );
        }
    }
}
