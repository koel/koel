<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Str;

class ImageData implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $passes = attempt(static function () use ($value) {
            return (bool) preg_match('/data:image\/(jpe?g|png|webp|gif)/i', Str::before($value, ';'));
        }, false) ?? false;

        if (!$passes) {
            $fail('Invalid DataURL string');
        }
    }
}
