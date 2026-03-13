<?php

namespace Tests\Unit\Ai\Services;

use App\Ai\AiRequestContext;
use App\Ai\Services\SongRequestResolver;
use App\Models\Song;
use App\Models\User;
use App\Repositories\SongRepository;
use Laravel\Ai\Tools\Request;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_user;

class SongRequestResolverTest extends TestCase
{
    private SongRepository|MockInterface $songRepository;
    private SongRequestResolver $resolver;
    private User $user;

    public function setUp(): void
    {
        parent::setUp();

        $this->songRepository = Mockery::mock(SongRepository::class);
        $this->resolver = new SongRequestResolver($this->songRepository);
        $this->user = create_user();
    }

    #[Test]
    public function resolveSongByQuery(): void
    {
        $songs = Song::factory()
            ->for($this->user, 'owner')
            ->count(1)
            ->create(['title' => 'Bohemian Rhapsody']);

        $this->songRepository
            ->shouldReceive('search')
            ->with('bohemian', 1, $this->user)
            ->andReturn($songs);

        $context = new AiRequestContext($this->user);
        $result = $this->resolver->resolveSong(new Request(['query' => 'bohemian']), $context);

        self::assertTrue($result->is($songs->first()));
    }

    #[Test]
    public function resolveSongByCurrentSongId(): void
    {
        $song = Song::factory()->for($this->user, 'owner')->createOne();

        $this->songRepository
            ->shouldReceive('findOne')
            ->with($song->id, $this->user)
            ->andReturn($song);

        $context = new AiRequestContext($this->user, currentSongId: $song->id);
        $result = $this->resolver->resolveSong(new Request([]), $context);

        self::assertTrue($result->is($song));
    }

    #[Test]
    public function resolveSongReturnsNullWhenNothingAvailable(): void
    {
        $context = new AiRequestContext($this->user);
        $result = $this->resolver->resolveSong(new Request([]), $context);

        self::assertNull($result);
    }

    #[Test]
    public function resolveSongPrefersQueryOverCurrentSong(): void
    {
        $currentSong = Song::factory()->for($this->user, 'owner')->createOne(['title' => 'Current']);
        $queriedSongs = Song::factory()
            ->for($this->user, 'owner')
            ->count(1)
            ->create(['title' => 'Queried']);

        $this->songRepository
            ->shouldReceive('search')
            ->with('queried', 1, $this->user)
            ->andReturn($queriedSongs);

        $context = new AiRequestContext($this->user, currentSongId: $currentSong->id);
        $result = $this->resolver->resolveSong(new Request(['query' => 'queried']), $context);

        self::assertTrue($result->is($queriedSongs->first()));
    }

    #[Test]
    public function resolveSongWithCustomQueryKey(): void
    {
        $songs = Song::factory()
            ->for($this->user, 'owner')
            ->count(1)
            ->create();

        $this->songRepository
            ->shouldReceive('search')
            ->with('test', 1, $this->user)
            ->andReturn($songs);

        $context = new AiRequestContext($this->user);
        $result = $this->resolver->resolveSong(new Request(['song_title' => 'test']), $context, 'song_title');

        self::assertTrue($result->is($songs->first()));
    }

    #[Test]
    public function resolveSongsByQuery(): void
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
        $result = $this->resolver->resolveSongs(new Request(['song_query' => 'rock']), $context);

        self::assertCount(3, $result);
    }

    #[Test]
    public function resolveSongsByCurrentSongId(): void
    {
        $song = Song::factory()->for($this->user, 'owner')->createOne();

        $this->songRepository
            ->shouldReceive('findOne')
            ->with($song->id, $this->user)
            ->andReturn($song);

        $context = new AiRequestContext($this->user, currentSongId: $song->id);
        $result = $this->resolver->resolveSongs(new Request([]), $context);

        self::assertCount(1, $result);
        self::assertTrue($result->first()->is($song));
    }

    #[Test]
    public function resolveSongsReturnsEmptyCollectionWhenNothingAvailable(): void
    {
        $context = new AiRequestContext($this->user);
        $result = $this->resolver->resolveSongs(new Request([]), $context);

        self::assertCount(0, $result);
    }

    #[Test]
    public function resolveSongsReturnsEmptyCollectionWhenCurrentSongNotFound(): void
    {
        $this->songRepository
            ->shouldReceive('findOne')
            ->with('nonexistent', $this->user)
            ->andReturn(null);

        $context = new AiRequestContext($this->user, currentSongId: 'nonexistent');
        $result = $this->resolver->resolveSongs(new Request([]), $context);

        self::assertCount(0, $result);
    }

    #[Test]
    public function resolveSongsWithCustomLimit(): void
    {
        $songs = Song::factory()
            ->for($this->user, 'owner')
            ->count(5)
            ->create();

        $this->songRepository
            ->shouldReceive('search')
            ->with('jazz', 5, $this->user)
            ->andReturn($songs);

        $context = new AiRequestContext($this->user);
        $result = $this->resolver->resolveSongs(new Request(['song_query' => 'jazz']), $context, limit: 5);

        self::assertCount(5, $result);
    }
}
