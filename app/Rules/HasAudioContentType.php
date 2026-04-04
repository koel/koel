<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Translation\PotentiallyTranslatedString;
use Throwable;

/**
 * Validates that a URL serves audio content.
 * Should be used after SafeUrl to ensure the URL is safe to reach.
 */
class HasAudioContentType implements ValidationRule
{
    /** @param Closure(string, ?string=): PotentiallyTranslatedString $fail */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        try {
            $contentType = Http::head((string) $value)->header('Content-Type');
        } catch (Throwable) {
            $fail("The $attribute couldn't be reached.");

            return;
        }

        if (!$contentType || !Str::startsWith($contentType, 'audio/')) {
            $fail("The $attribute doesn't look like a valid radio station URL.");
        }
    }
}
