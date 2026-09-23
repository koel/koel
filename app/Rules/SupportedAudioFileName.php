<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Str;

class SupportedAudioFileName implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $extension = Str::lower(pathinfo((string) $value, PATHINFO_EXTENSION));

        if (!in_array($extension, collect_accepted_audio_extensions(), true)) {
            $fail('Unsupported audio file');
        }
    }
}
