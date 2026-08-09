<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Image;
use Illuminate\Support\Str;
use Throwable;

class ValidImageData implements ValidationRule
{
    /**
     * Images are read lazily, so the data must actually be inspected for it to be validated.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        try {
            Image::fromBase64(Str::after($value, 'base64,'))->dimensions();
        } catch (Throwable) {
            $fail("Invalid image for $attribute");
        }
    }
}
