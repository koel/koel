<?php

namespace Tests\Unit\Services;

use App\Services\ITunesService;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Log\Logger;
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
                'https://itunes.apple.com/bar',
                'https://itunes.apple.com/bar?at=foo',
                '2ce68c30758ed9496c72c36ff49c50b2',
            ], [
                'Foo',
                '',
                'Baz',
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
        string $trackViewUrl,
        string $affiliateUrl,
        string $cacheKey
    ): void {
        config(['koel.itunes.affiliate_id' => 'foo']);

        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'resultCount' => 1,
                'results' => [['trackViewUrl' => $trackViewUrl]],
            ])),
        ]);

        $client = new Client(['handler' => HandlerStack::create($mock)]);
        $cache = Mockery::mock(Cache::class);
        $logger = Mockery::mock(Logger::class);

        $service = new ITunesService($client, $cache, $logger);

        $cache
            ->shouldReceive('remember')
            ->with($cacheKey, 10080, Mockery::on(static function (callable $generator) use ($affiliateUrl): bool {
                return $generator() === $affiliateUrl;
            }));

        $service->getTrackUrl($term, $album, $artist);
    }
}
