<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Intervention\Image\Decoders\Base64ImageDecoder;
use Intervention\Image\Decoders\DataUriImageDecoder;
use Intervention\Image\Laravel\Facades\Image;
use Throwable;

class ValidImageData implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        try {
            Image::read($value, [
                Base64ImageDecoder::class,
                DataUriImageDecoder::class,
            ]);
        } catch (Throwable) {
            $fail("Invalid image for $attribute");
        }
    }
}
