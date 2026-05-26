<?php

namespace App\Rules;

use App\Helpers\Network;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Uri;
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

    public function __construct(
        private ?Network $network = null,
    ) {
        $this->network ??= app(Network::class);
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

        if (!$this->network->isPublicHost($uri->host())) {
            $fail('The :attribute must point to a public URL.');

            return;
        }

        try {
            $response = Http::head((string) $value);
        } catch (Throwable) {
            // Some streaming servers don't support HEAD — try GET
            try {
                $response = Http::withOptions(['stream' => true])->get((string) $value);
            } catch (Throwable) {
                $fail("The $attribute couldn't be reached.");

                return;
            }
        }

        $effectiveHost = $response->effectiveUri()?->getHost();

        if ($effectiveHost && !$this->network->isPublicHost($effectiveHost)) {
            $fail('The :attribute must point to a public URL.');
        }
    }
}
