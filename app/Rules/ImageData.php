<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use Throwable;

class ImageData implements Rule
{
    public function passes($attribute, $value): bool
    {
        try {
            [$header,] = explode(';', $value);

            return (bool) preg_match('/data:image\/(jpe?g|png|webp|gif)/i', $header);
        } catch (Throwable) {
            return false;
        }
    }

    public function message(): string
    {
        return 'Invalid DataURL string';
    }
}
