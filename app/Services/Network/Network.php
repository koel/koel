<?php

namespace App\Services\Network;

use Illuminate\Support\Arr;
use Illuminate\Support\Uri;
use IPLib\Address\AddressInterface;
use IPLib\Address\IPv4;
use IPLib\Factory;
use IPLib\Range\Type as RangeType;
use Throwable;

class Network
{
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

        if (!in_array($uri->scheme(), ['http', 'https'], true)) {
            return false;
        }

        return $this->isPublicHost($uri->host());
    }

    /**
     * Check if a host resolves only to public (non-private, non-reserved) IP addresses.
     * Validates both A (IPv4) and AAAA (IPv6) records. All resolved IPs must be public.
     */
    public function isPublicHost(?string $host): bool
    {
        return $host && $this->resolveToPublicIps($host);
    }

    /**
     * Resolve a host to its public IP addresses. Returns an empty list if the
     * host can't be resolved, has no records, or has *any* non-public record —
     * fail-closed semantics: a mixed public/private answer rejects the whole
     * host. The strict behavior is needed by `isPublicHost()` consumers that
     * don't go through curl (e.g. RadioStreamProxy uses fopen, which does its
     * own DNS lookup and could land on the private record).
     *
     * Callers that DO go through curl additionally feed the returned list to
     * CURLOPT_RESOLVE to pin connect-time DNS onto the validated IPs, closing
     * the DNS-rebinding TOCTOU window between validation and connect.
     *
     * "Public" means ip-lib's RangeType::T_PUBLIC — excludes private, loopback,
     * link-local, multicast, broadcast, reserved, documentation, NAT64
     * (T_RESERVED), 6to4 wrappers of private IPv4 (classified by embedded v4),
     * Teredo, CGNAT.
     *
     * @return list<string>
     */
    public function resolveToPublicIps(string $host): array
    {
        $literal = Factory::parseAddressString($host);

        if ($literal) {
            if (self::isAmbiguousIpv4Literal($literal, $host)) {
                return [];
            }

            return $literal->getRangeType() === RangeType::T_PUBLIC ? [$literal->toString()] : [];
        }

        try {
            $a = dns_get_record($host, DNS_A);
            $aaaa = dns_get_record($host, DNS_AAAA);
        } catch (Throwable) {
            return [];
        }

        if ($a === false || $aaaa === false) {
            // dns_get_record returning false is a resolver failure — fail closed
            // rather than treating it as "host has no records".
            return [];
        }

        $ips = [];

        foreach (array_merge($a, $aaaa) as $record) {
            $ip = Arr::get($record, 'ip') ?? Arr::get($record, 'ipv6');

            if (!$ip || Factory::parseAddressString($ip)?->getRangeType() !== RangeType::T_PUBLIC) {
                return [];
            }

            $ips[] = $ip;
        }

        return $ips;
    }

    /**
     * ip-lib reads a dotted-quad octet with a leading zero as decimal, so `0177.0.0.1`
     * parses as the public `177.0.0.1`. glibc reads the same octet as octal and connects
     * to `127.0.0.1` instead, which turns the public-address check into a bypass. Any
     * IPv4 literal that isn't already written in canonical form carries that ambiguity.
     *
     * IPv6 is exempt: its textual forms vary in case and zero-compression but every
     * parser agrees on what they mean.
     */
    private static function isAmbiguousIpv4Literal(AddressInterface $literal, string $host): bool
    {
        return $literal instanceof IPv4 && $literal->toString() !== $host;
    }
}
