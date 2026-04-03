<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Validates that a URL does not resolve to a private or reserved IP address,
 * preventing SSRF attacks against internal services.
 */
class SafeUrl implements ValidationRule
{
    private const array ALLOWED_SCHEMES = ['http', 'https'];

    /** @param Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString $fail */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $scheme = parse_url((string) $value, PHP_URL_SCHEME);

        if (!$scheme || !in_array(strtolower($scheme), self::ALLOWED_SCHEMES, true)) {
            $fail('The :attribute must use HTTP or HTTPS.');

            return;
        }

        $host = parse_url((string) $value, PHP_URL_HOST);

        if (!$host) {
            $fail('The :attribute has an invalid host.');

            return;
        }

        // If the host is already an IP, use it directly; otherwise resolve it.
        if (filter_var($host, FILTER_VALIDATE_IP)) {
            $ip = $host;
        } else {
            $ip = gethostbyname($host);

            // gethostbyname returns the original hostname if resolution fails
            if ($ip === $host) {
                $fail('The :attribute could not be resolved.');

                return;
            }
        }

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
            $fail('The :attribute must not resolve to a private or reserved IP address.');
        }
    }
}
