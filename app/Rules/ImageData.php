<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use Throwable;

class ImageData implements Rule
{
    public function passes($attribute, $value): bool
    {
        try {
            [$header, $_] = explode(';', $value);

            return (bool) preg_match('/data:image\/(jpe?g|png|gif)/i', $header);
        } catch (Throwable $exception) {
            return false;
        }
    }

    public function message(): string
    {
        return 'Invalid DataURL string';
    }
}
