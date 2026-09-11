<?php

namespace Tests\Unit\Listeners;

use App\Events\AlbumMbidsResolved;
use App\Listeners\StoreAlbumMbids;
use App\Models\Album;
use App\Models\Artist;
use App\Models\Song;
use Illuminate\Database\Eloquent\Collection;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_user;

class StoreAlbumMbidsTest extends TestCase
{
    /**
     * @param array<string> $titles
     *
     * @return array<mixed>
     */
    private static function makeTracks(array $titles): array
    {
        return collect($titles)->map(static fn (string $title, int $index): array => [
            'id' => "track-mbid-$index",
            'title' => $title,
            'recording' => ['id' => "recording-mbid-$index"],
        ])->all();
    }

    /** @param array<string> $titles */
    private static function makeAlbumWithSongs(array $titles): Album
    {
        $user = create_user();
        $artist = Artist::factory()->for($user)->createOne();

        /** @var Album $album */
        $album = Album::factory()->for($artist)->createOne([
            'artist_name' => $artist->name,
            'user_id' => $user->id,
        ]);

        foreach ($titles as $title) {
            Song::factory()->for($album)->for($artist)->createOne(['title' => $title, 'owner_id' => $user->id]);
        }

        return $album;
    }

    private static function getSongs(Album $album): Collection
    {
        return $album->fresh()->songs;
    }

    #[Test]
    public function storeAlbumAndRecordingMbids(): void
    {
        $album = self::makeAlbumWithSongs(['Monkey Business', 'Slave to the Grind']);
        $tracks = self::makeTracks(['Monkey Business', 'Slave to the Grind']);

        (new StoreAlbumMbids())->handle(new AlbumMbidsResolved($album, 'sample-album-mbid', $tracks));

        $songs = self::getSongs($album);

        self::assertSame('sample-album-mbid', $album->refresh()->mbid);
        self::assertSame('recording-mbid-0', $songs->firstWhere('title', 'Monkey Business')->mbid);
        self::assertSame('recording-mbid-1', $songs->firstWhere('title', 'Slave to the Grind')->mbid);
    }

    #[Test]
    public function matchRecordingsRegardlessOfTitleCasingAndPadding(): void
    {
        $album = self::makeAlbumWithSongs(['Monkey Business']);

        (new StoreAlbumMbids())->handle(
            new AlbumMbidsResolved($album, 'sample-album-mbid', self::makeTracks(['  MONKEY BUSINESS '])),
        );

        self::assertSame('recording-mbid-0', self::getSongs($album)->first()->mbid);
    }

    #[Test]
    public function storeNoRecordingMbidWhenTheReleaseIsADifferentAlbum(): void
    {
        $album = self::makeAlbumWithSongs(['Monkey Business']);

        (new StoreAlbumMbids())->handle(
            new AlbumMbidsResolved($album, 'sample-album-mbid', self::makeTracks(['A Totally Different Song'])),
        );

        self::assertSame('sample-album-mbid', $album->refresh()->mbid);
        self::assertNull(self::getSongs($album)->first()->mbid);
    }

    #[Test]
    public function storeNoRecordingMbidForAmbiguousTitles(): void
    {
        $album = self::makeAlbumWithSongs(['Monkey Business']);

        (new StoreAlbumMbids())->handle(
            new AlbumMbidsResolved(
                $album,
                'sample-album-mbid',
                self::makeTracks(['Monkey Business', 'Monkey Business']),
            ),
        );

        self::assertNull(self::getSongs($album)->first()->mbid);
    }

    #[Test]
    public function keepExistingMbids(): void
    {
        $album = self::makeAlbumWithSongs(['Monkey Business']);
        $album->update(['mbid' => 'album-mbid-from-tags']);
        $album->songs->first()->update(['mbid' => 'recording-mbid-from-tags']);

        (new StoreAlbumMbids())->handle(
            new AlbumMbidsResolved($album, 'sample-album-mbid', self::makeTracks(['Monkey Business'])),
        );

        self::assertSame('album-mbid-from-tags', $album->refresh()->mbid);
        self::assertSame('recording-mbid-from-tags', self::getSongs($album)->first()->mbid);
    }

    #[Test]
    public function storeAlbumMbidWithoutTracks(): void
    {
        $album = self::makeAlbumWithSongs(['Monkey Business']);

        (new StoreAlbumMbids())->handle(new AlbumMbidsResolved($album, 'sample-album-mbid'));

        self::assertSame('sample-album-mbid', $album->refresh()->mbid);
        self::assertNull(self::getSongs($album)->first()->mbid);
    }
}
