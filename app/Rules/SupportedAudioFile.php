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
        $passes = in_array(
            Str::lower(File::mimeType($value->getRealPath())),
            config('koel.streaming.supported_mime_types'),
            true,
        );

        if (!$passes) {
            $fail('Unsupported audio file');
        }
    }
}
