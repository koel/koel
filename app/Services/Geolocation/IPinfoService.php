<?php

namespace App\Services\Geolocation;

use App\Http\Integrations\IPinfo\IPinfoConnector;
use App\Http\Integrations\IPinfo\Requests\GetLiteDataRequest;
use App\Services\Geolocation\Contracts\GeolocationService;
use App\Values\IpInfoLiteData;
use Illuminate\Support\Facades\Cache;

class IPinfoService implements GeolocationService
{
    public function __construct(private readonly IPinfoConnector $connector)
    {
    }

    public static function used(): bool
    {
        return (bool) config('koel.services.ipinfo.token');
    }

    public function getCountryCodeFromIp(string $ip): ?string
    {
        if (!static::used()) {
            return null;
        }

        return Cache::rememberForever(
            cache_key('IP to country code', $ip),
            function () use ($ip): string {
                /** @var IpInfoLiteData $data */
                $data = $this->connector->send(new GetLiteDataRequest($ip))->dto();

                return $data->countryCode;
            },
        );
    }
}
