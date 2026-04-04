<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

/**
 * Validates that a URL does not resolve to a private or reserved IP address,
 * preventing SSRF attacks against internal services.
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

        if (!$host) {
            $fail('The :attribute has an invalid host.');

            return;
        }

        $ips = self::resolveAllIps($host);

        if (!$ips) {
            $fail('The :attribute could not be resolved.');

            return;
        }

        foreach ($ips as $ip) {
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
                $fail('The :attribute must not resolve to a private or reserved IP address.');

                return;
            }
        }
    }

    /** @return array<string> */
    private static function resolveAllIps(string $host): array
    {
        // Normalize bracketed IPv6 (e.g. "[::1]" → "::1")
        $normalized = trim($host, '[]');

        if (filter_var($normalized, FILTER_VALIDATE_IP)) {
            return [$normalized];
        }

        // Convert internationalized domain names (IDN) to ASCII for DNS functions.
        $lookupHost = $host;

        if (function_exists('idn_to_ascii')) {
            $ascii = idn_to_ascii($host);

            if ($ascii !== false && $ascii !== '') {
                $lookupHost = $ascii;
            }
        }

        $ips = [];

        // Collect both A (IPv4) and AAAA (IPv6) records
        if (function_exists('dns_get_record')) {
            $records = @dns_get_record($lookupHost, DNS_A | DNS_AAAA) ?: [];

            foreach ($records as $record) {
                if (!empty($record['ip'])) {
                    $ips[] = $record['ip'];
                }

                if (!empty($record['ipv6'])) {
                    $ips[] = $record['ipv6'];
                }
            }
        }

        // Fallback: gethostbynamel returns all IPv4 addresses
        if (!$ips) {
            $v4 = gethostbynamel($lookupHost);

            if ($v4) {
                $ips = $v4;
            } else {
                $ip = gethostbyname($lookupHost);

                if ($ip !== $lookupHost) {
                    $ips[] = $ip;
                }
            }
        }

        return array_values(array_unique($ips));
    }
}
