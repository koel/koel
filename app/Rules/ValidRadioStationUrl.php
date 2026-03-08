<?php

namespace App\Rules;

use App\Helpers\Network;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Translation\PotentiallyTranslatedString;
use Throwable;

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

        if (!$host || !Network::isPublicHost($host)) {
            $fail("The $attribute must point to a public URL.");

            return;
        }

        try {
            $response = Http::head($value);
        } catch (Throwable) {
            $fail("The $attribute couldn't be reached.");

            return;
        }

        // After following redirects, validate the final URL's host to prevent SSRF via redirect.
        $effectiveUrl = $response->effectiveUri()?->getHost();

        if ($effectiveUrl && !Network::isPublicHost($effectiveUrl)) {
            $fail("The $attribute must point to a public URL.");

            return;
        }

        $contentType = $response->header('Content-Type');

        if (!$contentType || !Str::startsWith($contentType, 'audio/')) {
            $fail("The $attribute doesn't look like a valid radio station URL.");
        }
    }
}
