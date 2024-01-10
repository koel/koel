<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Str;

class ImageData implements Rule
{
    public function passes($attribute, $value): bool
    {
        return attempt(static function () use ($value) {
            return (bool) preg_match('/data:image\/(jpe?g|png|webp|gif)/i', Str::before($value, ';'));
        }, false) ?? false;
    }

    public function message(): string
    {
        return 'Invalid DataURL string';
    }
}
