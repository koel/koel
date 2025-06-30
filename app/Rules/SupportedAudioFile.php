<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class SupportedAudioFile implements ValidationRule
{
    /** @param UploadedFile $value */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $passes = array_key_exists(
            Str::lower(File::mimeType($value->getRealPath())),
            config('koel.streaming.supported_mime_types')
        );

        if (!$passes) {
            $fail('Unsupported audio file');
        }
    }
}
