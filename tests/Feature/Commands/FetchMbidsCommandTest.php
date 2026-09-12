<?php

namespace Tests\Feature\Commands;

use App\Http\Integrations\MusicBrainz\MusicBrainzConnector;
use App\Http\Integrations\MusicBrainz\ThrottledMusicBrainzConnector;
use App\Models\Album;
use App\Models\Artist;
use App\Models\Song;
use App\Pipelines\Encyclopedia\GetAlbumTracksUsingMbid;
use App\Pipelines\Encyclopedia\GetMbidForArtist;
use App\Pipelines\Encyclopedia\GetReleaseAndReleaseGroupMbidsForAlbum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class FetchMbidsCommandTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        config(['koel.services.musicbrainz.enabled' => true]);
    }

    private function allowPipelinePipe(string $class, mixed $output): void
    {
        $this
            ->mock($class)
            ->allows('__invoke')
            ->andReturnUsing(static fn (mixed $_, callable $next) => $next($output));
    }

    #[Test]
    public function fillInMissingIdentifiers(): void
    {
        $this->allowPipelinePipe(GetMbidForArtist::class, 'found-artist-mbid');
        $this->allowPipelinePipe(GetReleaseAndReleaseGroupMbidsForAlbum::class, ['found-album-mbid', null]);
        $this->allowPipelinePipe(GetAlbumTracksUsingMbid::class, [
            ['title' => 'Schism', 'recording' => ['id' => 'found-recording-mbid']],
        ]);

        $artist = Artist::factory()->createOne(['name' => 'Tool', 'mbid' => null]);
        $album = Album::factory()->for($artist)->createOne([
            'name' => 'Lateralus',
            'artist_name' => $artist->name,
            'mbid' => null,
        ]);
        $song = Song::factory()->for($album)->for($artist)->createOne(['title' => 'Schism', 'mbid' => null]);

        $this
            ->artisan('koel:fetch-mbids')
            ->expectsOutput('Looking up 1 album.')
            ->expectsOutput('Looking up 1 artist.')
            ->assertSuccessful();

        self::assertSame('found-artist-mbid', $artist->refresh()->mbid);
        self::assertSame('found-album-mbid', $album->refresh()->mbid);
        self::assertSame('found-recording-mbid', $song->refresh()->mbid);
    }

    #[Test]
    public function countTheEntitiesItLooksUp(): void
    {
        $this->allowPipelinePipe(GetMbidForArtist::class, 'found-artist-mbid');

        Artist::factory()->createMany(array_map(
            static fn (int $index): array => ['name' => "Artist $index", 'mbid' => null],
            range(1, 3),
        ));

        $this->artisan('koel:fetch-mbids')->expectsOutput('Looking up 3 artists.')->assertSuccessful();
    }

    #[Test]
    public function leaveExistingIdentifiersAlone(): void
    {
        $this->mock(GetMbidForArtist::class)->shouldNotReceive('__invoke');
        $this->mock(GetReleaseAndReleaseGroupMbidsForAlbum::class)->shouldNotReceive('__invoke');
        $this->allowPipelinePipe(GetAlbumTracksUsingMbid::class, []);

        $artist = Artist::factory()->createOne(['mbid' => 'artist-mbid-from-tags']);
        $album = Album::factory()->for($artist)->createOne([
            'artist_name' => $artist->name,
            'mbid' => 'album-mbid-from-tags',
        ]);

        $this
            ->artisan('koel:fetch-mbids')
            ->expectsOutput('Every album and artist already has an identifier.')
            ->assertSuccessful();

        self::assertSame('artist-mbid-from-tags', $artist->refresh()->mbid);
        self::assertSame('album-mbid-from-tags', $album->refresh()->mbid);
    }

    #[Test]
    public function skipUnknownAlbumsAndArtists(): void
    {
        $this->mock(GetMbidForArtist::class)->shouldNotReceive('__invoke');
        $this->mock(GetReleaseAndReleaseGroupMbidsForAlbum::class)->shouldNotReceive('__invoke');

        $unknownArtist = Artist::factory()->createOne(['name' => Artist::UNKNOWN_NAME, 'mbid' => null]);
        Artist::factory()->createOne(['name' => Artist::VARIOUS_NAME, 'mbid' => null]);
        Album::factory()->for($unknownArtist)->createOne([
            'name' => Album::UNKNOWN_NAME,
            'artist_name' => $unknownArtist->name,
            'mbid' => null,
        ]);

        $this
            ->artisan('koel:fetch-mbids')
            ->expectsOutput('Every album and artist already has an identifier.')
            ->assertSuccessful();
    }

    #[Test]
    public function throttleTheLookupsMusicBrainzReceives(): void
    {
        $this->allowPipelinePipe(GetMbidForArtist::class, 'found-artist-mbid');
        Artist::factory()->createOne(['mbid' => null]);

        $this->artisan('koel:fetch-mbids')->assertSuccessful();

        self::assertInstanceOf(ThrottledMusicBrainzConnector::class, app(MusicBrainzConnector::class));
    }

    #[Test]
    public function refuseToRunWhenMusicBrainzIsDisabled(): void
    {
        config(['koel.services.musicbrainz.enabled' => false]);

        $this
            ->artisan('koel:fetch-mbids')
            ->expectsOutput('MusicBrainz is disabled. Enable it before running this command.')
            ->assertFailed();
    }
}
