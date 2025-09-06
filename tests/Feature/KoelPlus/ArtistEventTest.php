<?php

namespace Tests\Feature\KoelPlus;

use App\Http\Integrations\IPinfo\Requests\GetLiteDataRequest;
use App\Http\Integrations\Ticketmaster\Requests\AttractionSearchRequest;
use App\Http\Integrations\Ticketmaster\Requests\EventSearchRequest;
use App\Http\Resources\LiveEventResource;
use App\Models\Artist;
use App\Values\Ticketmaster\TicketmasterEvent;
use App\Values\Ticketmaster\TicketmasterVenue;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\Test;
use Saloon\Http\Faking\MockResponse;
use Saloon\Laravel\Facades\Saloon;
use Tests\PlusTestCase;

use function Tests\test_path;

class ArtistEventTest extends PlusTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        config(['koel.services.ticketmaster.key' => 'tm-key']);
        config(['koel.services.ipinfo.token' => 'ipinfo-token']);
    }

    #[Test]
    public function getEvents(): void
    {
        $attractionSearchJson = File::json(test_path('fixtures/ticketmaster/attraction-search.json'));
        $eventSearchJson = File::json(test_path('fixtures/ticketmaster/event-search.json'));
        $liteDataJson = File::json(test_path('fixtures/ipinfo/lite-data.json'));

        Saloon::fake([
            AttractionSearchRequest::class => MockResponse::make(body: $attractionSearchJson),
            EventSearchRequest::class => MockResponse::make(body: $eventSearchJson),
            GetLiteDataRequest::class => MockResponse::make(body: $liteDataJson),
        ]);

        /** @var Artist $artist */
        $artist = Artist::factory()->create([
            'name' => 'Slayer',
        ]);

        $this->getAs("api/artists/{$artist->id}/events")
            ->assertJsonStructure(['*' => LiveEventResource::JSON_STRUCTURE])
            ->assertJsonCount(2)
            ->assertOk();
    }

    #[Test]
    public function getEventsFromCache(): void
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

        Cache::put(cache_key('Ticketmaster events', 'Slayer', 'DE'), collect([$event]), now()->addDay());
        Cache::forever(cache_key('IP to country code', '127.0.0.1'), 'DE');

        /** @var Artist $artist */
        $artist = Artist::factory()->create([
            'name' => 'Slayer',
        ]);

        $this->getAs("api/artists/{$artist->id}/events")
            ->assertJsonStructure(['*' => LiveEventResource::JSON_STRUCTURE])
            ->assertJsonCount(1)
            ->assertOk();

        Saloon::assertNothingSent();
    }
}
