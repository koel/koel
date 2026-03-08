<?php

namespace App\Rules;

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

        if (!$host || !$this->isPublicHost($host)) {
            $fail("The $attribute must point to a public URL.");

            return;
        }

        try {
            $response = Http::withOptions(['allow_redirects' => false])->head($value);
        } catch (Throwable) {
            $fail("The $attribute couldn't be reached.");

            return;
        }

        $contentType = $response->header('Content-Type');

        if (!$contentType || !Str::startsWith($contentType, 'audio/')) {
            $fail("The $attribute doesn't look like a valid radio station URL.");
        }
    }

    /**
     * Ensure the host resolves only to public (non-private, non-reserved) IP addresses.
     * Checks both A (IPv4) and AAAA (IPv6) records. All resolved IPs must be public.
     */
    private function isPublicHost(string $host): bool
    {
        if (filter_var($host, FILTER_VALIDATE_IP)) {
            return (
                filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false
            );
        }

        $records = array_merge(dns_get_record($host, DNS_A) ?: [], dns_get_record($host, DNS_AAAA) ?: []);

        if (empty($records)) {
            return false;
        }

        foreach ($records as $record) {
            $ip = $record['ip'] ?? $record['ipv6'] ?? null;

            if (
                !$ip
                || filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false
            ) {
                return false;
            }
        }

        return true;
    }
}
