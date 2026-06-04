<?php

namespace App\Rules;

use App\Exceptions\UnsafeUrlException;
use App\Helpers\SafeHttp;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Uri;
use Illuminate\Translation\PotentiallyTranslatedString;
use Throwable;

/**
 * Validates that a URL is safe to fetch — its host (and every redirect target's
 * host) resolves only to public IP addresses. Delegates the SSRF guarantees to
 * SafeHttp; the rule itself just translates UnsafeUrlException into the
 * appropriate validation failure message.
 */
class SafeUrl implements ValidationRule
{
    private const array ALLOWED_SCHEMES = ['http', 'https'];

    public function __construct(
        private ?SafeHttp $safeHttp = null,
    ) {
        $this->safeHttp ??= app(SafeHttp::class);
    }

    /** @param Closure(string, ?string=): PotentiallyTranslatedString $fail */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        try {
            $uri = Uri::of((string) $value);
        } catch (Throwable) {
            $fail('The :attribute is not a valid URL.');

            return;
        }

        if (!in_array($uri->scheme(), self::ALLOWED_SCHEMES, true)) {
            $fail('The :attribute must use HTTP or HTTPS.');

            return;
        }

        try {
            $this->safeHttp->head((string) $value);
        } catch (UnsafeUrlException) {
            $fail('The :attribute must point to a public URL.');
        } catch (Throwable) {
            // Some streaming servers don't support HEAD — try GET as a stream.
            try {
                $this->safeHttp->getAsStream((string) $value);
            } catch (UnsafeUrlException) {
                $fail('The :attribute must point to a public URL.');
            } catch (Throwable) {
                $fail("The $attribute couldn't be reached.");
            }
        }
    }
}
