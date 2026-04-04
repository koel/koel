<?php

namespace App\Rules;

use App\Helpers\Network;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Http;
use Illuminate\Translation\PotentiallyTranslatedString;
use Throwable;

/**
 * Validates that a URL does not resolve to a private or reserved IP address,
 * preventing SSRF attacks against internal services.
 * Also follows redirects and validates the effective URL.
 */
class SafeUrl implements ValidationRule
{
    private const array ALLOWED_SCHEMES = ['http', 'https'];

    /** @param Closure(string, ?string=): PotentiallyTranslatedString $fail */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $scheme = parse_url((string) $value, PHP_URL_SCHEME);

        if (!$scheme || !in_array(strtolower($scheme), self::ALLOWED_SCHEMES, true)) {
            $fail('The :attribute must use HTTP or HTTPS.');

            return;
        }

        $host = parse_url((string) $value, PHP_URL_HOST);

        if (!$host || !Network::isPublicHost($host)) {
            $fail('The :attribute must point to a public URL.');

            return;
        }

        try {
            $response = Http::head((string) $value);
        } catch (Throwable) {
            $fail("The $attribute couldn't be reached.");

            return;
        }

        $effectiveHost = $response->effectiveUri()?->getHost();

        if ($effectiveHost && !Network::isPublicHost($effectiveHost)) {
            $fail('The :attribute must point to a public URL.');
        }
    }
}
