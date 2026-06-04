<?php

namespace App\Helpers;

use Illuminate\Support\Uri;
use IPLib\Address\AddressInterface;
use IPLib\Address\IPv6;
use IPLib\Factory;
use IPLib\Range\Subnet;
use IPLib\Range\Type as RangeType;
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
     * Resolve a host to its public IP addresses. Returns null if the host can't
     * be resolved, has no records, or has any non-public record. Callers use the
     * returned list with CURLOPT_RESOLVE to pin the resolved IPs into the HTTP
     * client, closing the DNS-rebinding TOCTOU window between validation and connect.
     *
     * @return list<string>|null
     */
    public function resolveToPublicIps(string $host): ?array
    {
        $literal = Factory::parseAddressString($host);

        if ($literal !== null) {
            return self::isPublicAddress($literal) ? [$host] : null;
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

            if (!$ip) {
                return null;
            }

            $address = Factory::parseAddressString($ip);

            if ($address === null || !self::isPublicAddress($address)) {
                return null;
            }

            $ips[] = $ip;
        }

        return $ips;
    }

    /**
     * An address is public iff its range type is PUBLIC (rejects private,
     * loopback, link-local, multicast, broadcast, reserved, documentation,
     * Teredo, etc.) AND, if it's an IPv6 NAT64/6to4 wrapper, the embedded
     * IPv4 is also public.
     */
    private static function isPublicAddress(AddressInterface $address): bool
    {
        if ($address->getRangeType() !== RangeType::T_PUBLIC) {
            return false;
        }

        if ($address instanceof IPv6) {
            $embedded = self::extractEmbeddedIpv4($address);

            if ($embedded !== null && !self::isPublicAddress($embedded)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Return the IPv4 embedded inside a NAT64 well-known (64:ff9b::/96) or 6to4
     * (2002::/16) IPv6 wrapper, or null if not a wrapper.
     */
    private static function extractEmbeddedIpv4(IPv6 $address): ?AddressInterface
    {
        $bytes = inet_pton($address->toString());

        if ($bytes === false || strlen($bytes) !== 16) {
            return null;
        }

        if (Subnet::parseString('64:ff9b::/96')->contains($address)) {
            return Factory::parseAddressString((string) inet_ntop(substr($bytes, 12, 4)));
        }

        if (Subnet::parseString('2002::/16')->contains($address)) {
            return Factory::parseAddressString((string) inet_ntop(substr($bytes, 2, 4)));
        }

        return null;
    }
}
