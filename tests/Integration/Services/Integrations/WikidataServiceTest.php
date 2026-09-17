<?php

namespace Tests\Integration\Services\Integrations;

use App\Models\Artist;
use App\Pipelines\Encyclopedia\GetArtistImageUsingWikidataId;
use App\Pipelines\Encyclopedia\GetArtistWikidataIdUsingMbid;
use App\Services\Integrations\WikidataService;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class WikidataServiceTest extends TestCase
{
    private WikidataService $service;

    public function setUp(): void
    {
        parent::setUp();

        $this->service = app(WikidataService::class);
    }

    private function mockPipelinePipe(string $class, mixed $input, mixed $output): void
    {
        $this
            ->mock($class)
            ->expects('__invoke')
            ->with($input, Mockery::on(is_callable(...)))
            ->andReturnUsing(static fn ($_, $next) => $next($output));
    }

    #[Test]
    public function getTheArtistImage(): void
    {
        $artist = Artist::factory()->createOne(['mbid' => '66c662b6-6e2f-4930-8610-912e24c63ed1']);

        $this->mockPipelinePipe(GetArtistWikidataIdUsingMbid::class, '66c662b6-6e2f-4930-8610-912e24c63ed1', 'Q27593');

        $this->mockPipelinePipe(
            GetArtistImageUsingWikidataId::class,
            'Q27593',
            'https://commons.wikimedia.org/wiki/Special:FilePath/AC%20DC.jpg?width=640',
        );

        self::assertSame(
            'https://commons.wikimedia.org/wiki/Special:FilePath/AC%20DC.jpg?width=640',
            $this->service->tryGetArtistImage($artist),
        );
    }

    #[Test]
    public function getNothingWithoutAnArtistMbid(): void
    {
        $this->mock(GetArtistWikidataIdUsingMbid::class)->expects('__invoke')->never();

        self::assertNull($this->service->tryGetArtistImage(Artist::factory()->createOne(['mbid' => null])));
    }

    #[Test]
    public function getNothingWhenMusicBrainzIsDisabled(): void
    {
        config(['koel.services.musicbrainz.enabled' => false]);
        $this->mock(GetArtistWikidataIdUsingMbid::class)->expects('__invoke')->never();

        $artist = Artist::factory()->createOne(['mbid' => '66c662b6-6e2f-4930-8610-912e24c63ed1']);

        self::assertNull($this->service->tryGetArtistImage($artist));
    }
}
