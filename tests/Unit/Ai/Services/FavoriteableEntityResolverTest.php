<?php

namespace Tests\Unit\Ai\Services;

use App\Ai\AiRequestContext;
use App\Ai\Services\FavoriteableEntityResolver;
use App\Enums\FavoriteableType;
use App\Models\Album;
use App\Models\Artist;
use App\Models\Podcast;
use App\Models\RadioStation;
use App\Models\Song;
use App\Models\User;
use App\Repositories\AlbumRepository;
use App\Repositories\ArtistRepository;
use App\Repositories\PodcastRepository;
use App\Repositories\RadioStationRepository;
use App\Repositories\SongRepository;
use Laravel\Ai\Tools\Request;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_user;

class FavoriteableEntityResolverTest extends TestCase
{
    private SongRepository|MockInterface $songRepository;
    private AlbumRepository|MockInterface $albumRepository;
    private ArtistRepository|MockInterface $artistRepository;
    private RadioStationRepository|MockInterface $radioStationRepository;
    private PodcastRepository|MockInterface $podcastRepository;
    private FavoriteableEntityResolver $resolver;
    private User $user;

    public function setUp(): void
    {
        parent::setUp();

        $this->songRepository = Mockery::mock(SongRepository::class);
        $this->albumRepository = Mockery::mock(AlbumRepository::class);
        $this->artistRepository = Mockery::mock(ArtistRepository::class);
        $this->radioStationRepository = Mockery::mock(RadioStationRepository::class);
        $this->podcastRepository = Mockery::mock(PodcastRepository::class);

        $this->resolver = new FavoriteableEntityResolver(
            $this->songRepository,
            $this->albumRepository,
            $this->artistRepository,
            $this->radioStationRepository,
            $this->podcastRepository,
        );

        $this->user = create_user();
    }

    #[Test]
    public function resolvePlayableByQuery(): void
    {
        $songs = Song::factory()
            ->for($this->user, 'owner')
            ->count(3)
            ->create();

        $this->songRepository
            ->shouldReceive('search')
            ->with('rock', 10, $this->user)
            ->andReturn($songs);

        $context = new AiRequestContext($this->user);
        $result = $this->resolver->resolve(FavoriteableType::PLAYABLE, new Request(['query' => 'rock']), $context);

        self::assertCount(3, $result);
    }

    #[Test]
    public function resolveAlbumByQuery(): void
    {
        $albums = Album::factory()->createMany(1);

        $this->albumRepository
            ->shouldReceive('search')
            ->with('abbey road', 1, $this->user)
            ->andReturn($albums);

        $context = new AiRequestContext($this->user);
        $result = $this->resolver->resolve(FavoriteableType::ALBUM, new Request(['query' => 'abbey road']), $context);

        self::assertCount(1, $result);
    }

    #[Test]
    public function resolveArtistByQuery(): void
    {
        $artists = Artist::factory()->createMany(1);

        $this->artistRepository
            ->shouldReceive('search')
            ->with('beatles', 1, $this->user)
            ->andReturn($artists);

        $context = new AiRequestContext($this->user);
        $result = $this->resolver->resolve(FavoriteableType::ARTIST, new Request(['query' => 'beatles']), $context);

        self::assertCount(1, $result);
    }

    #[Test]
    public function resolveRadioStationByQuery(): void
    {
        $stations = RadioStation::factory()->createMany(1);

        $this->radioStationRepository
            ->shouldReceive('search')
            ->with('jazz fm', 1, $this->user)
            ->andReturn($stations);

        $context = new AiRequestContext($this->user);
        $result = $this->resolver->resolve(
            FavoriteableType::RADIO_STATION,
            new Request(['query' => 'jazz fm']),
            $context,
        );

        self::assertCount(1, $result);
    }

    #[Test]
    public function resolvePodcastByQuery(): void
    {
        $podcasts = Podcast::factory()->createMany(1);

        $this->podcastRepository
            ->shouldReceive('search')
            ->with('tech talk', 1, $this->user)
            ->andReturn($podcasts);

        $context = new AiRequestContext($this->user);
        $result = $this->resolver->resolve(FavoriteableType::PODCAST, new Request(['query' => 'tech talk']), $context);

        self::assertCount(1, $result);
    }

    #[Test]
    public function resolveCurrentlyPlayingSong(): void
    {
        $song = Song::factory()->for($this->user, 'owner')->createOne();

        $this->songRepository
            ->shouldReceive('findOne')
            ->with($song->id, $this->user)
            ->andReturn($song);

        $context = new AiRequestContext($this->user, currentSongId: $song->id);
        $result = $this->resolver->resolve(FavoriteableType::PLAYABLE, new Request([]), $context);

        self::assertCount(1, $result);
        self::assertTrue($result->first()->is($song));
    }

    #[Test]
    public function resolveReturnsEmptyWhenCurrentSongNotFound(): void
    {
        $this->songRepository
            ->shouldReceive('findOne')
            ->with('nonexistent', $this->user)
            ->andReturn(null);

        $context = new AiRequestContext($this->user, currentSongId: 'nonexistent');
        $result = $this->resolver->resolve(FavoriteableType::PLAYABLE, new Request([]), $context);

        self::assertCount(0, $result);
    }

    #[Test]
    public function resolveReturnsEmptyWhenNoQueryAndNoCurrentSong(): void
    {
        $context = new AiRequestContext($this->user);
        $result = $this->resolver->resolve(FavoriteableType::PLAYABLE, new Request([]), $context);

        self::assertCount(0, $result);
    }

    #[Test]
    public function resolveReturnsEmptyForNonPlayableWithoutQuery(): void
    {
        $context = new AiRequestContext($this->user);
        $result = $this->resolver->resolve(FavoriteableType::ALBUM, new Request([]), $context);

        self::assertCount(0, $result);
    }

    #[Test]
    public function resolveIgnoresCurrentSongForNonPlayableType(): void
    {
        $song = Song::factory()->for($this->user, 'owner')->createOne();

        $context = new AiRequestContext($this->user, currentSongId: $song->id);
        $result = $this->resolver->resolve(FavoriteableType::ARTIST, new Request([]), $context);

        self::assertCount(0, $result);
    }

    #[Test]
    public function resolvePrefersQueryOverCurrentSong(): void
    {
        $currentSong = Song::factory()->for($this->user, 'owner')->createOne();
        $queriedSongs = Song::factory()
            ->for($this->user, 'owner')
            ->count(2)
            ->create();

        $this->songRepository
            ->shouldReceive('search')
            ->with('jazz', 10, $this->user)
            ->andReturn($queriedSongs);

        $context = new AiRequestContext($this->user, currentSongId: $currentSong->id);
        $result = $this->resolver->resolve(FavoriteableType::PLAYABLE, new Request(['query' => 'jazz']), $context);

        self::assertCount(2, $result);
    }

    #[Test]
    public function entityNameReturnsTitleForSong(): void
    {
        $song = Song::factory()->for($this->user, 'owner')->createOne(['title' => 'Bohemian Rhapsody']);

        self::assertSame('Bohemian Rhapsody', $this->resolver->entityName($song));
    }

    #[Test]
    public function entityNameReturnsNameForArtist(): void
    {
        $artist = Artist::factory()->createOne(['name' => 'Queen']);

        self::assertSame('Queen', $this->resolver->entityName($artist));
    }
}
