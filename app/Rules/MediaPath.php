<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\File;

class MediaPath implements Rule
{
    public function passes($attribute, $value): bool
    {
        if (config('koel.storage_driver') === 'local') {
            return $value && File::isDirectory($value) && File::isReadable($value);
        }

        // Setting a media path is not required for non-local storage drivers.
        return false;
    }

    public function message(): string
    {
        if (config('koel.storage_driver') === 'local') {
            return 'Media path is required for local storage.';
        }

        return 'Media path is not required for non-local storage.';
    }
}
