<?php

namespace App\Services;

use App\Facades\License;
use App\Http\Integrations\Ticketmaster\Requests\AttractionSearchRequest;
use App\Http\Integrations\Ticketmaster\Requests\EventSearchRequest;
use App\Http\Integrations\Ticketmaster\TicketmasterConnector;
use App\Services\Geolocation\Contracts\GeolocationService;
use App\Values\Ticketmaster\TicketmasterAttraction;
use App\Values\Ticketmaster\TicketmasterEvent;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class TicketmasterService
{
    public function __construct(
        private readonly TicketmasterConnector $connector,
        private readonly GeolocationService $geolocator,
        private readonly string $defaultCountryCode,
    ) {
    }

    public static function used(): bool
    {
        return License::isPlus() && config('koel.services.ticketmaster.key');
    }

    /** @return Collection<TicketmasterEvent>|array<array-key, TicketmasterEvent> */
    public function searchEventForArtist(string $artistName, string $ip): Collection
    {
        $countryCode = $this->geolocator->getCountryCodeFromIp($ip) ?: $this->defaultCountryCode;

        return rescue(function () use ($artistName, $countryCode) {
            return Cache::remember(
                cache_key('Ticketmaster events', $artistName, $countryCode),
                now()->addDay(),
                function () use ($artistName, $countryCode): Collection {
                    $attractionId = $this->getAttractionIdForArtist($artistName);

                    return $attractionId
                        ? $this->connector->send(new EventSearchRequest($attractionId, $countryCode))->dto()
                        : collect();
                }
            );
        }, collect());
    }

    private function getAttractionIdForArtist(string $artistName): ?string
    {
        return rescue(function () use ($artistName): ?string {
            return Cache::remember(
                cache_key('Ticketmaster attraction id', $artistName),
                now()->addMonth(),
                function () use ($artistName): ?string {
                    /** @var Collection<TicketmasterAttraction>|array<array-key, TicketmasterAttraction> $attractions */
                    $attractions = $this->connector->send(new AttractionSearchRequest($artistName))->dto();

                    return $attractions->firstWhere(
                        static function (TicketmasterAttraction $attraction) use ($artistName) {
                            return Str::lower($attraction->name) === Str::lower($artistName);
                        }
                    )?->id;
                }
            );
        });
    }
}
