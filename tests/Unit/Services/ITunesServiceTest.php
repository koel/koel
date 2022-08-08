<?php

namespace Tests\Unit\Services;

use App\Services\ApiClients\ITunesClient;
use App\Services\ITunesService;
use Illuminate\Cache\Repository as Cache;
use Mockery;
use Tests\TestCase;

class ITunesServiceTest extends TestCase
{
    public function testConfiguration(): void
    {
        config(['koel.itunes.enabled' => true]);
        /** @var ITunesService $iTunes */
        $iTunes = app()->make(ITunesService::class);
        self::assertTrue($iTunes->used());

        config(['koel.itunes.enabled' => false]);
        self::assertFalse($iTunes->used());
    }

    /** @return array<mixed> */
    public function provideGetTrackUrlData(): array
    {
        return [
            [
                'Foo',
                'Bar',
                'Baz',
                'Foo Bar Baz',
                'https://itunes.apple.com/bar',
                'https://itunes.apple.com/bar?at=foo',
                '2ce68c30758ed9496c72c36ff49c50b2',
            ], [
                'Foo',
                '',
                'Baz',
                'Foo Baz',
                'https://itunes.apple.com/bar?qux=qux',
                'https://itunes.apple.com/bar?qux=qux&at=foo',
                'cda57916eb80c2ee79b16e218bdb70d2',
            ],
        ];
    }

    /** @dataProvider provideGetTrackUrlData */
    public function testGetTrackUrl(
        string $term,
        string $album,
        string $artist,
        string $constructedTerm,
        string $trackViewUrl,
        string $affiliateUrl,
        string $cacheKey
    ): void {
        config(['koel.itunes.affiliate_id' => 'foo']);
        $cache = Mockery::mock(Cache::class);
        $client = Mockery::mock(ITunesClient::class);

        $client->shouldReceive('get')
            ->with('/', [
                'term' => $constructedTerm,
                'media' => 'music',
                'entity' => 'song',
                'limit' => 1,
            ])
        ->andReturn(json_decode(json_encode([
            'resultCount' => 1,
            'results' => [['trackViewUrl' => $trackViewUrl]],
        ])));

        $service = new ITunesService($client, $cache);

        $cache
            ->shouldReceive('remember')
            ->with($cacheKey, 10080, Mockery::on(static function (callable $generator) use ($affiliateUrl): bool {
                return $generator() === $affiliateUrl;
            }));

        $service->getTrackUrl($term, $album, $artist);
    }
}
