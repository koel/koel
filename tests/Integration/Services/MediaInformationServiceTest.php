<?php

namespace Tests\Integration\Services;

use App\Events\AlbumInformationFetched;
use App\Events\ArtistInformationFetched;
use App\Models\Album;
use App\Models\Artist;
use App\Repositories\AlbumRepository;
use App\Repositories\ArtistRepository;
use App\Services\LastfmService;
use App\Services\MediaInformationService;
use Mockery;
use Mockery\LegacyMockInterface;
use Mockery\MockInterface;
use Tests\TestCase;

class MediaInformationServiceTest extends TestCase
{
    private LastfmService|MockInterface|LegacyMockInterface $lastFmService;
    private MediaInformationService $mediaInformationService;

    public function setUp(): void
    {
        parent::setUp();

        $this->lastFmService = Mockery::mock(LastfmService::class);
        $this->albumRepository = Mockery::mock(AlbumRepository::class);
        $this->artistRepository = Mockery::mock(ArtistRepository::class);

        $this->mediaInformationService = new MediaInformationService(
            $this->lastFmService,
            app(AlbumRepository::class),
            app(ArtistRepository::class)
        );
    }

    public function testGetAlbumInformation(): void
    {
        $this->expectsEvents(AlbumInformationFetched::class);

        /** @var Album $album */
        $album = Album::factory()->create();

        $this->lastFmService
            ->shouldReceive('getAlbumInformation')
            ->once()
            ->with($album->name, $album->artist->name)
            ->andReturn(['foo' => 'bar']);

        $info = $this->mediaInformationService->getAlbumInformation($album);

        self::assertEquals([
            'foo' => 'bar',
            'cover' => $album->cover,
        ], $info);
    }

    public function testGetArtistInformation(): void
    {
        $this->expectsEvents(ArtistInformationFetched::class);

        /** @var Artist $artist */
        $artist = Artist::factory()->create();

        $this->lastFmService
            ->shouldReceive('getArtistInformation')
            ->once()
            ->with($artist->name)
            ->andReturn(['foo' => 'bar']);

        $info = $this->mediaInformationService->getArtistInformation($artist);

        self::assertEquals([
            'foo' => 'bar',
            'image' => $artist->image,
        ], $info);
    }
}
