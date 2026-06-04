<?php

namespace App\Helpers;

use Illuminate\Support\Arr;
use Illuminate\Support\Uri;
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
     * Resolve a host to the subset of its DNS records (A + AAAA) whose IPs are
     * public. Non-public records are dropped — only the public IPs come back.
     * Returns an empty list when nothing public is left (host doesn't resolve,
     * has no records, or every record is private/reserved).
     *
     * Callers feed the returned list to CURLOPT_RESOLVE to pin curl onto the
     * validated IPs, closing the DNS-rebinding TOCTOU window between validation
     * and connect.
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
