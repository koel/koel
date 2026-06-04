<?php

namespace App\Helpers;

use Illuminate\Support\Uri;
use Throwable;

// @mago-expect lint:cyclomatic-complexity -- SSRF guard inherently branches on host shape (IP literal vs DNS, IPv4 vs IPv6, NAT64/6to4 wrapper extraction); splitting further would scatter the safety story.
class Network
{
    private const array SAFE_URL_SCHEMES = ['http', 'https'];

    /**
     * Check if a URL is safe to reach: HTTP/HTTPS scheme + a public host.
     * Does NOT perform any network calls beyond DNS resolution.
     * For full validation including effective-URL-after-redirect, use the SafeUrl validation rule.
     */
    public function isSafeUrl(string $url): bool
    {
        try {
            $uri = Uri::of($url);
        } catch (Throwable) {
            return false;
        }

        if (!in_array($uri->scheme(), self::SAFE_URL_SCHEMES, true)) {
            return false;
        }

        $host = $uri->host();

        return $host !== '' && $this->isPublicHost($host);
    }

    /**
     * Check if a host resolves only to public (non-private, non-reserved) IP addresses.
     * Validates both A (IPv4) and AAAA (IPv6) records. All resolved IPs must be public.
     */
    public function isPublicHost(string $host): bool
    {
        return $this->resolveToPublicIps($host) !== null;
    }

    /**
     * Public-IP test that closes IPv6-transition wrapper holes left open by
     * filter_var's NO_PRIV_RANGE|NO_RES_RANGE flags: NAT64 well-known prefix
     * (64:ff9b::/96, RFC 6052) and 6to4 (2002::/16, RFC 3056) deterministically
     * embed an IPv4 the kernel will route to, so an IPv6 in those ranges must be
     * re-checked against the embedded v4.
     */
    private static function isPublicIp(string $ip): bool
    {
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
            return false;
        }

        $embedded = self::extractEmbeddedIpv4($ip);

        if ($embedded === null) {
            return true;
        }

        return (
            filter_var($embedded, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false
        );
    }

    /**
     * Resolve a host to its public IP addresses. Returns null if the host can't
     * be resolved, has no records, or has any non-public record. Callers use the
     * returned list with CURLOPT_RESOLVE to pin the resolved IPs into the HTTP
     * client, closing the DNS-rebinding TOCTOU window between validation and connect.
     *
     * @return list<string>|null
     */
    public function resolveToPublicIps(string $host): ?array
    {
        if (filter_var($host, FILTER_VALIDATE_IP)) {
            return self::isPublicIp($host) ? [$host] : null;
        }

        try {
            $records = array_merge(dns_get_record($host, DNS_A) ?: [], dns_get_record($host, DNS_AAAA) ?: []);
        } catch (Throwable) {
            return null;
        }

        if ($records === []) {
            return null;
        }

        $ips = [];

        foreach ($records as $record) {
            $ip = $record['ip'] ?? $record['ipv6'] ?? null;

            if (!$ip || !self::isPublicIp($ip)) {
                return null;
            }

            $ips[] = $ip;
        }

        return $ips;
    }

    /** Return the IPv4 embedded inside a NAT64 or 6to4 IPv6 wrapper, or null if not a wrapper. */
    private static function extractEmbeddedIpv4(string $ip): ?string
    {
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) === false) {
            return null;
        }

        $packed = inet_pton($ip);

        if ($packed === false || strlen($packed) !== 16) {
            return null;
        }

        // NAT64 well-known prefix: 64:ff9b:: + 96 zero bits + 32-bit IPv4
        if (str_starts_with($packed, "\x00\x64\xff\x9b" . str_repeat("\x00", 8))) {
            return inet_ntop(substr($packed, 12, 4)) ?: null;
        }

        // 6to4: 2002:<v4-hi>:<v4-lo>::/48
        if (str_starts_with($packed, "\x20\x02")) {
            return inet_ntop(substr($packed, 2, 4)) ?: null;
        }

        return null;
    }
}
