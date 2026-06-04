<?php

namespace App\Helpers;

use Illuminate\Support\Arr;
use Illuminate\Support\Uri;
use IPLib\Factory;
use IPLib\Range\Type as RangeType;
use Throwable;

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

        return $host && $this->isPublicHost($host);
    }

    /**
     * Check if a host resolves only to public (non-private, non-reserved) IP addresses.
     * Validates both A (IPv4) and AAAA (IPv6) records. All resolved IPs must be public.
     */
    public function isPublicHost(string $host): bool
    {
        return (bool) $this->resolveToPublicIps($host);
    }

    /**
     * Resolve a host to its public IP addresses, or an empty list if the host
     * can't be resolved, has no records, or has any non-public record. Callers
     * use the list with CURLOPT_RESOLVE to pin the resolved IPs into the HTTP
     * client, closing the DNS-rebinding TOCTOU window between validation and connect.
     *
     * "Public" means ip-lib's RangeType::T_PUBLIC — rejects private, loopback,
     * link-local, multicast, broadcast, reserved, documentation, NAT64 (T_RESERVED),
     * 6to4 wrappers of private IPv4 (classified by embedded v4), Teredo, CGNAT.
     *
     * @return list<string>
     */
    public function resolveToPublicIps(string $host): array
    {
        $literal = Factory::parseAddressString($host);

        if ($literal) {
            return $literal->getRangeType() === RangeType::T_PUBLIC ? [$host] : [];
        }

        try {
            $records = array_merge(dns_get_record($host, DNS_A) ?: [], dns_get_record($host, DNS_AAAA) ?: []);
        } catch (Throwable) {
            return [];
        }

        $ips = [];

        foreach ($records as $record) {
            $ip = Arr::get($record, 'ip') ?? Arr::get($record, 'ipv6');

            if ($ip && Factory::parseAddressString($ip)?->getRangeType() === RangeType::T_PUBLIC) {
                $ips[] = $ip;
            }
        }

        return $ips;
    }
}
