<?php

namespace Tests\Integration;

use App\Http\Integrations\IPinfo\Requests\GetLiteDataRequest;
use App\Http\Integrations\Ticketmaster\Requests\AttractionSearchRequest;
use App\Http\Integrations\Ticketmaster\Requests\EventSearchRequest;
use App\Models\Artist;
use App\Services\TicketmasterService;
use App\Values\Ticketmaster\TicketmasterEvent;
use App\Values\Ticketmaster\TicketmasterVenue;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\Test;
use Saloon\Http\Faking\MockResponse;
use Saloon\Http\PendingRequest;
use Saloon\Laravel\Facades\Saloon;
use Tests\TestCase;

use function Tests\test_path;

class TicketmasterServiceTest extends TestCase
{
    private TicketmasterService $service;

    public function setUp(): void
    {
        parent::setUp();

        config(['koel.services.ticketmaster.key' => 'tm-key']);
        config(['koel.services.ipinfo.token' => 'ipinfo-token']);

        $this->service = app(TicketmasterService::class);
    }

    #[Test]
    public function searchEventForArtist(): void
    {
        Saloon::fake([
            AttractionSearchRequest::class => static function (PendingRequest $request) {
                self::assertEqualsCanonicalizing([
                    'keyword' => 'Slayer',
                    'size' => 5,
                    'classificationName' => ['Music'],
                    'apikey' => 'tm-key',
                ], $request->query()->all());

                $attractionSearchJson = File::json(test_path('fixtures/ticketmaster/attraction-search.json'));

                return MockResponse::make(body: $attractionSearchJson);
            },
            EventSearchRequest::class => static function (PendingRequest $request) {
                self::assertEqualsCanonicalizing([
                    'attractionId' => 'slayer-id-1234567890',
                    'countryCode' => 'DE',
                    'classificationName' => ['Music'],
                    'apikey' => 'tm-key',
                ], $request->query()->all());

                $eventSearchJson = File::json(test_path('fixtures/ticketmaster/event-search.json'));

                return MockResponse::make(body: $eventSearchJson);
            },
            GetLiteDataRequest::class => static function (PendingRequest $request) {
                self::assertSame('https://api.ipinfo.io/lite/84.124.22.13', $request->getUrl());
                self::assertSame('ipinfo-token', $request->query()->get('token'));

                $liteDataJson = File::json(test_path('fixtures/ipinfo/lite-data.json'));

                return MockResponse::make(body: $liteDataJson);
            },
        ]);

        /** @var Artist $artist */
        $artist = Artist::factory()->create([
            'name' => 'Slayer',
        ]);

        $events = $this->service->searchEventForArtist($artist->name, '84.124.22.13');
        self::assertCount(2, $events);
    }

    #[Test]
    public function searchEventsCached(): void
    {
        Saloon::fake([]);

        $event = TicketmasterEvent::make(
            id: '1234567890',
            name: 'Slayer',
            url: 'https://www.ticketmaster.com/event/1234567890',
            image: 'https://www.ticketmaster.com/image/1234567890',
            start: now()->addWeek(),
            end: now()->addWeek()->addDay(),
            venue: TicketmasterVenue::make(
                name: 'Sample Venue',
                url: 'https://www.ticketmaster.com/venue/1234567890',
                city: 'Sample City',
            ),
        );

        $events = collect([$event]);

        Cache::put(cache_key('Ticketmaster events', 'Coolio', 'BR'), $events, now()->addDay());
        Cache::put(cache_key('IP to country code', '84.124.22.13'), 'BR', now()->addDay());

        self::assertSame($events, $this->service->searchEventForArtist('Coolio', '84.124.22.13'));
        Saloon::assertNothingSent();
    }
}
