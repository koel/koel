<?php

namespace Tests\Integration\Services\Integrations;

use App\Models\Album;
use App\Models\Artist;
use App\Models\Song;
use App\Pipelines\Encyclopedia\GetAlbumTracksUsingMbid;
use App\Pipelines\Encyclopedia\GetMbidForArtist;
use App\Pipelines\Encyclopedia\GetReleaseAndReleaseGroupMbidsForAlbum;
use App\Services\Integrations\MbidService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_user;

class MbidServiceTest extends TestCase
{
    private MbidService $service;

    public function setUp(): void
    {
        parent::setUp();

        $this->service = app(MbidService::class);
    }

    private function allowPipelinePipe(string $class, mixed $output): void
    {
        $this
            ->mock($class)
            ->allows('__invoke')
            ->andReturnUsing(static fn ($_, $next) => $next($output));
    }

    /** @param array<string> $titles */
    private function allowAlbumLookups(array $titles): void
    {
        $tracks = collect($titles)->map(static fn (string $title, int $index): array => [
            'id' => "track-mbid-$index",
            'title' => $title,
            'recording' => ['id' => "recording-mbid-$index"],
        ])->all();

        $this->allowPipelinePipe(GetReleaseAndReleaseGroupMbidsForAlbum::class, ['sample-album-mbid', null]);
        $this->allowPipelinePipe(GetAlbumTracksUsingMbid::class, $tracks);
    }

    /** @param array<string> $titles */
    private static function makeAlbumWith(array $titles): Album
    {
        $user = create_user();
        $artist = Artist::factory()->for($user)->createOne(['name' => 'Skid Row']);

        /** @var Album $album */
        $album = $artist->albums()->create(['name' => 'Slave to the Grind', 'user_id' => $user->id]); // @phpstan-ignore-line

        foreach ($titles as $title) {
            Song::factory()->for($album)->for($artist)->createOne(['title' => $title, 'owner_id' => $user->id]);
        }

        return $album;
    }

    private static function getFirstSongMbid(Album $album): ?string
    {
        return $album->fresh()->songs->first()->mbid;
    }

    #[Test]
    public function fetchAndStoreArtistMbid(): void
    {
        $this->allowPipelinePipe(GetMbidForArtist::class, 'sample-artist-mbid');
        $artist = Artist::factory()->createOne(['name' => 'Skid Row']);

        $this->service->fetchAndStoreArtistMbid($artist);

        self::assertSame('sample-artist-mbid', $artist->refresh()->mbid);
    }

    #[Test]
    public function keepExistingArtistMbid(): void
    {
        $this->allowPipelinePipe(GetMbidForArtist::class, 'sample-artist-mbid');
        $artist = Artist::factory()->createOne(['name' => 'Skid Row', 'mbid' => 'mbid-from-tags']);

        $this->service->fetchAndStoreArtistMbid($artist);

        self::assertSame('mbid-from-tags', $artist->refresh()->mbid);
    }

    #[Test]
    public function storeNothingWhenTheArtistIsUnknownToMusicBrainz(): void
    {
        $this->allowPipelinePipe(GetMbidForArtist::class, null);
        $artist = Artist::factory()->createOne(['name' => 'Skid Row']);

        $this->service->fetchAndStoreArtistMbid($artist);

        self::assertNull($artist->refresh()->mbid);
    }

    #[Test]
    public function storeNothingForAnUnknownArtist(): void
    {
        $this->allowPipelinePipe(GetMbidForArtist::class, 'sample-artist-mbid');
        $artist = Artist::factory()->createOne(['name' => Artist::UNKNOWN_NAME]);

        $this->service->fetchAndStoreArtistMbid($artist);

        self::assertNull($artist->refresh()->mbid);
    }

    #[Test]
    public function fetchAndStoreAlbumAndRecordingMbids(): void
    {
        $this->allowAlbumLookups(['Monkey Business', 'Slave to the Grind']);
        $album = self::makeAlbumWith(['Monkey Business', 'Slave to the Grind']);

        $this->service->fetchAndStoreAlbumMbids($album);

        $songs = $album->fresh()->songs;

        self::assertSame('sample-album-mbid', $album->refresh()->mbid);
        self::assertSame('recording-mbid-0', $songs->firstWhere('title', 'Monkey Business')->mbid);
        self::assertSame('recording-mbid-1', $songs->firstWhere('title', 'Slave to the Grind')->mbid);
    }

    #[Test]
    public function keepExistingAlbumAndRecordingMbids(): void
    {
        $this->allowAlbumLookups(['Monkey Business']);
        $album = self::makeAlbumWith(['Monkey Business']);
        $album->update(['mbid' => 'album-mbid-from-tags']);
        $album->songs->first()->update(['mbid' => 'recording-mbid-from-tags']);

        $this->service->fetchAndStoreAlbumMbids($album);

        self::assertSame('album-mbid-from-tags', $album->refresh()->mbid);
        self::assertSame('recording-mbid-from-tags', self::getFirstSongMbid($album));
    }

    #[Test]
    public function matchRecordingsRegardlessOfTitleCasingAndPadding(): void
    {
        $this->allowAlbumLookups(['  MONKEY BUSINESS ']);
        $album = self::makeAlbumWith(['Monkey Business']);

        $this->service->fetchAndStoreAlbumMbids($album);

        self::assertSame('recording-mbid-0', self::getFirstSongMbid($album));
    }

    #[Test]
    public function storeNoRecordingMbidWhenTheReleaseIsADifferentAlbum(): void
    {
        $this->allowAlbumLookups(['A Totally Different Song']);
        $album = self::makeAlbumWith(['Monkey Business']);

        $this->service->fetchAndStoreAlbumMbids($album);

        self::assertSame('sample-album-mbid', $album->refresh()->mbid);
        self::assertNull(self::getFirstSongMbid($album));
    }

    #[Test]
    public function storeNoRecordingMbidForAmbiguousTitles(): void
    {
        $this->allowAlbumLookups(['Monkey Business', 'Monkey Business']);
        $album = self::makeAlbumWith(['Monkey Business']);

        $this->service->fetchAndStoreAlbumMbids($album);

        self::assertNull(self::getFirstSongMbid($album));
    }

    #[Test]
    public function storeNothingWhenTheAlbumIsUnknownToMusicBrainz(): void
    {
        $this->allowPipelinePipe(GetReleaseAndReleaseGroupMbidsForAlbum::class, [null, null]);
        $album = self::makeAlbumWith(['Monkey Business']);

        $this->service->fetchAndStoreAlbumMbids($album);

        self::assertNull($album->refresh()->mbid);
    }

    #[Test]
    public function fetchAndStoreEvenWhenLastfmSuppliesEncyclopediaEntries(): void
    {
        // Only one service ever supplies entries, and Last.fm wins when configured — identifiers must not
        // depend on that, since they are useful regardless of who writes the prose.
        config([
            'koel.services.lastfm.key' => 'key',
            'koel.services.lastfm.secret' => 'secret',
            'koel.services.musicbrainz.enabled' => true,
        ]);

        $this->allowPipelinePipe(GetMbidForArtist::class, 'sample-artist-mbid');
        $artist = Artist::factory()->createOne(['name' => 'Skid Row']);

        $this->service->fetchAndStoreArtistMbid($artist);

        self::assertSame('sample-artist-mbid', $artist->refresh()->mbid);
    }

    #[Test]
    public function storeNothingWhenMusicBrainzIsDisabled(): void
    {
        config(['koel.services.musicbrainz.enabled' => false]);

        // Let every lookup succeed, so nothing being stored can only be the disabled flag's doing.
        $this->allowPipelinePipe(GetMbidForArtist::class, 'sample-artist-mbid');
        $this->allowAlbumLookups(['Monkey Business']);

        $artist = Artist::factory()->createOne(['name' => 'Skid Row']);
        $album = self::makeAlbumWith(['Monkey Business']);

        $this->service->fetchAndStoreArtistMbid($artist);
        $this->service->fetchAndStoreAlbumMbids($album);

        self::assertNull($artist->refresh()->mbid);
        self::assertNull($album->refresh()->mbid);
    }
}
