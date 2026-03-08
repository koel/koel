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

        $host = parse_url($value, PHP_URL_HOST);

        if (!$host || !$this->isPublicHost($host)) {
            $fail("The $attribute must point to a public URL.");

            return;
        }

        $contentType = Http::head($value)->header('Content-Type');

        if (!$contentType || !Str::startsWith($contentType, 'audio/')) {
            $fail("The $attribute doesn't look like a valid radio station URL.");
        }
    }

    /**
     * Ensure the host resolves to a public (non-private, non-reserved) IP address.
     */
    private function isPublicHost(string $host): bool
    {
        $ip = gethostbyname($host);

        // gethostbyname returns the input string if resolution fails
        if ($ip === $host && !filter_var($host, FILTER_VALIDATE_IP)) {
            return false;
        }

        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false;
    }
}
