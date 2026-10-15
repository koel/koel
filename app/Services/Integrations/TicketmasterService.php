<?php

namespace App\Services\Integrations;

use App\Facades\License;
use App\Http\Integrations\Ticketmaster\Requests\AttractionSearchRequest;
use App\Http\Integrations\Ticketmaster\Requests\EventSearchRequest;
use App\Http\Integrations\Ticketmaster\TicketmasterConnector;
use App\Models\Artist;
use App\Services\Geolocation\Contracts\GeolocationService;
use App\Values\Ticketmaster\ArtistEventSearchResult;
use App\Values\Ticketmaster\TicketmasterAttraction;
use App\Values\Ticketmaster\TicketmasterEvent;
use Illuminate\Container\Attributes\Config;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class TicketmasterService
{
    public function __construct(
        private readonly TicketmasterConnector $connector,
        private readonly GeolocationService $geolocator,
        #[Config('koel.services.ticketmaster.default_country_code')]
        private readonly string $defaultCountryCode = '',
    ) {}

    public static function used(): bool
    {
        return License::isPlus() && config('koel.services.ticketmaster.key');
    }

    public function searchEventForArtist(Artist $artist, string $ip): ArtistEventSearchResult
    {
        if (!$artist->is_standard) {
            return ArtistEventSearchResult::make(collect(), nearby: true);
        }

        $countryCode = $this->geolocator->getCountryCodeFromIp($ip) ?: $this->defaultCountryCode;

        if (!$countryCode) {
            return ArtistEventSearchResult::make($this->searchEvents($artist->name, null), nearby: true);
        }

        $nearbyEvents = $this->searchEvents($artist->name, $countryCode);

        if ($nearbyEvents->isNotEmpty()) {
            return ArtistEventSearchResult::make($nearbyEvents, nearby: true);
        }

        return ArtistEventSearchResult::make($this->searchEvents($artist->name, null), nearby: false);
    }

    /** @return Collection<int, TicketmasterEvent> */
    private function searchEvents(string $artistName, ?string $countryCode): Collection
    {
        return rescue(function () use ($artistName, $countryCode) {
            return Cache::remember(
                cache_key('Ticketmaster events', $artistName, $countryCode ?? 'any country'),
                now()->addDay(),
                function () use ($artistName, $countryCode): Collection {
                    $attractionId = $this->getAttractionIdForArtist($artistName);

                    return $attractionId
                        ? $this->connector->send(new EventSearchRequest($attractionId, $countryCode))->dto()
                        : collect();
                },
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

                    return $attractions->firstWhere(static function (TicketmasterAttraction $attraction) use (
                        $artistName,
                    ) {
                        return Str::lower($attraction->name) === Str::lower($artistName);
                    })?->id;
                },
            );
        });
    }
}
