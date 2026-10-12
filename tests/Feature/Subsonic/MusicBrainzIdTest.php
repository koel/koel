<?php

namespace Tests\Feature\Subsonic;

use App\Models\Album;
use App\Models\Artist;
use App\Models\Song;
use PHPUnit\Framework\Attributes\Test;

use function Tests\create_user;

class MusicBrainzIdTest extends SubsonicTestCase
{
    private const string RECORDING_MBID = 'e3c3d3ee-3333-4ccc-8ddd-2f2f2f2f2f2f';
    private const string RELEASE_MBID = 'd2b2c2dd-2222-4bbb-8ccc-1e1e1e1e1e1e';
    private const string ARTIST_MBID = 'c1a1b1cc-1111-4aaa-8bbb-0d0d0d0d0d0d';

    #[Test]
    public function exposeTheRecordingIdentifierOnASong(): void
    {
        $user = create_user();
        $song = Song::factory()->createOne(['owner_id' => $user->id, 'mbid' => self::RECORDING_MBID]);

        $this->getSubsonic('getSong.view', $user, [
            'id' => $song->id,
        ])->assertSubsonicOk()->assertJsonPath('subsonic-response.song.musicBrainzId', self::RECORDING_MBID);
    }

    #[Test]
    public function exposeTheReleaseIdentifierOnAnAlbum(): void
    {
        $user = create_user();
        $album = Album::factory()->createOne(['user_id' => $user->id, 'mbid' => self::RELEASE_MBID]);
        Song::factory()->for($album)->createOne(['owner_id' => $user->id]);

        $this->getSubsonic('getAlbum.view', $user, [
            'id' => $album->id,
        ])->assertSubsonicOk()->assertJsonPath('subsonic-response.album.musicBrainzId', self::RELEASE_MBID);
    }

    #[Test]
    public function exposeTheArtistIdentifier(): void
    {
        $user = create_user();
        $artist = Artist::factory()->createOne(['user_id' => $user->id, 'mbid' => self::ARTIST_MBID]);
        Album::factory()->for($artist)->createOne(['user_id' => $user->id, 'artist_name' => $artist->name]);

        $this->getSubsonic('getArtist.view', $user, [
            'id' => $artist->id,
        ])->assertSubsonicOk()->assertJsonPath('subsonic-response.artist.musicBrainzId', self::ARTIST_MBID);
    }

    #[Test]
    public function omitTheIdentifierWhenUnknown(): void
    {
        $user = create_user();
        $artist = Artist::factory()->createOne(['user_id' => $user->id, 'mbid' => null]);
        $album = Album::factory()->for($artist)->createOne([
            'user_id' => $user->id,
            'artist_name' => $artist->name,
            'mbid' => null,
        ]);
        $song = Song::factory()
            ->for($album)
            ->for($artist)
            ->createOne([
                'owner_id' => $user->id,
                'mbid' => null,
            ]);

        $this->getSubsonic('getSong.view', $user, [
            'id' => $song->id,
        ])->assertSubsonicOk()->assertJsonMissingPath('subsonic-response.song.musicBrainzId');

        $this->getSubsonic('getAlbum.view', $user, [
            'id' => $album->id,
        ])->assertSubsonicOk()->assertJsonMissingPath('subsonic-response.album.musicBrainzId');

        $this->getSubsonic('getArtist.view', $user, [
            'id' => $artist->id,
        ])->assertSubsonicOk()->assertJsonMissingPath('subsonic-response.artist.musicBrainzId');
    }
}
