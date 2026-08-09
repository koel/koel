<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Image;
use Illuminate\Support\Str;
use Throwable;

class ValidImageData implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        try {
            // Images are read lazily, so the data must actually be inspected to be validated.
            Image::fromBase64(Str::after($value, 'base64,'))->dimensions();
        } catch (Throwable) {
            $fail("Invalid image for $attribute");
        }
    }
}
