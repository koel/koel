<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Translation\PotentiallyTranslatedString;

class ValidRadioStationUrl implements ValidationRule
{
    // Aid in testing by allowing the rule to be bypassed.
    // No need for overengineered abstractions and factories.
    public bool $bypass = false;

    /**
     * Run the validation rule.
     *
     * @param string $value The url to validate
     * @param Closure(string, ?string=): PotentiallyTranslatedString $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($this->bypass) {
            return;
        }

        $contentType = Http::head($value)->header('Content-Type');

        if (!$contentType || !Str::startsWith($contentType, 'audio/')) {
            $fail("The $attribute doesn't look like a valid radio station URL.");
        }
    }
}
