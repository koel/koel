<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class ImageData implements Rule
{
    public function passes($attribute, $value): bool
    {
        return attempt(static function () use ($value) {
            [$header,] = explode(';', $value);

            return (bool) preg_match('/data:image\/(jpe?g|png|webp|gif)/i', $header);
        }, false) ?? false;
    }

    public function message(): string
    {
        return 'Invalid DataURL string';
    }
}
