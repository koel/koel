<?php

namespace Tests\Integration\Services;

use App\Http\Integrations\iTunes\Requests\GetTrackRequest;
use App\Models\Album;
use App\Models\Artist;
use App\Services\ITunesService;
use Illuminate\Support\Facades\Cache;
use Saloon\Http\Faking\MockResponse;
use Saloon\Laravel\Saloon;
use Tests\TestCase;

class ITunesServiceTest extends TestCase
{
    private ITunesService $service;

    public function setUp(): void
    {
        parent::setUp();

        $this->service = app(ITunesService::class);
    }

    public function testConfiguration(): void
    {
        config(['koel.itunes.enabled' => true]);
        self::assertTrue($this->service->used());

        config(['koel.itunes.enabled' => false]);
        self::assertFalse($this->service->used());
    }

    public function testGetTrackUrl(): void
    {
        config(['koel.itunes.enabled' => true]);
        config(['koel.itunes.affiliate_id' => 'foo']);

        Saloon::fake([
            GetTrackRequest::class => MockResponse::make(body: [
                'resultCount' => 1,
                'results' => [['trackViewUrl' => 'https://itunes.apple.com/bar']],
            ]),
        ]);

        /** @var Album $album */
        $album = Album::factory()
            ->for(Artist::factory()->create(['name' => 'Queen']))
            ->create(['name' => 'A Night at the Opera']);

        self::assertSame(
            'https://itunes.apple.com/bar?at=foo',
            $this->service->getTrackUrl('Bohemian Rhapsody', $album)
        );

        self::assertSame(
            'https://itunes.apple.com/bar?at=foo',
            Cache::get('itunes.track.5f0467bebbb2b26bf9dc7b19f3d85077')
        );

        Saloon::assertSent(static function (GetTrackRequest $request): bool {
            self::assertSame([
                'term' => 'Bohemian Rhapsody A Night at the Opera Queen',
                'media' => 'music',
                'entity' => 'song',
                'limit' => 1,
            ], $request->query()->all());

            return true;
        });
    }
}
