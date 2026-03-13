<?php

namespace Tests\Unit\Ai\Serializers;

use App\Ai\AiAssistantResult;
use App\Ai\Serializers\AiResultSerializerRegistry;
use App\Enums\FavoriteableType;
use App\Models\Album;
use App\Models\Artist;
use App\Models\RadioStation;
use App\Models\Song;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_playlist;
use function Tests\create_user;

class AiResultSerializerRegistryTest extends TestCase
{
    #[Test]
    public function serializePlaySongs(): void
    {
        $songs = Song::factory()
            ->for(create_user(), 'owner')
            ->count(2)
            ->create();

        $result = new AiAssistantResult();
        $result->action = 'play_songs';
        $result->data = ['songs' => $songs, 'queue' => true];

        $serialized = AiResultSerializerRegistry::serialize($result);

        self::assertArrayHasKey('songs', $serialized);
        self::assertArrayHasKey('queue', $serialized);
        self::assertTrue($serialized['queue']);
    }

    #[Test]
    public function serializePlaySongsDefaultsQueueToFalse(): void
    {
        $songs = Song::factory()
            ->for(create_user(), 'owner')
            ->count(1)
            ->create();

        $result = new AiAssistantResult();
        $result->action = 'play_songs';
        $result->data = ['songs' => $songs];

        $serialized = AiResultSerializerRegistry::serialize($result);

        self::assertFalse($serialized['queue']);
    }

    #[Test]
    public function serializeSuggestSongs(): void
    {
        $songs = Song::factory()
            ->for(create_user(), 'owner')
            ->count(3)
            ->create();

        $result = new AiAssistantResult();
        $result->action = 'suggest_songs';
        $result->data = ['songs' => $songs, 'list' => 'some formatted list'];

        $serialized = AiResultSerializerRegistry::serialize($result);

        self::assertArrayHasKey('songs', $serialized);
        self::assertArrayHasKey('list', $serialized);
        self::assertSame('some formatted list', $serialized['list']);
    }

    #[Test]
    public function serializeSuggestSongsDefaultsListToEmpty(): void
    {
        $songs = Song::factory()
            ->for(create_user(), 'owner')
            ->count(1)
            ->create();

        $result = new AiAssistantResult();
        $result->action = 'suggest_songs';
        $result->data = ['songs' => $songs];

        $serialized = AiResultSerializerRegistry::serialize($result);

        self::assertSame('', $serialized['list']);
    }

    #[Test]
    public function serializeAddToFavoritesWithSongs(): void
    {
        $songs = Song::factory()
            ->for(create_user(), 'owner')
            ->count(2)
            ->create();

        $result = new AiAssistantResult();
        $result->action = 'add_to_favorites';
        $result->data = ['type' => FavoriteableType::PLAYABLE, 'entities' => $songs];

        $serialized = AiResultSerializerRegistry::serialize($result);

        self::assertSame('playable', $serialized['type']);
        self::assertArrayHasKey('songs', $serialized);
    }

    #[Test]
    public function serializeRemoveFromFavoritesWithAlbums(): void
    {
        $albums = Album::factory()->createMany(2);

        $result = new AiAssistantResult();
        $result->action = 'remove_from_favorites';
        $result->data = ['type' => FavoriteableType::ALBUM, 'entities' => $albums];

        $serialized = AiResultSerializerRegistry::serialize($result);

        self::assertSame('album', $serialized['type']);
        self::assertArrayHasKey('albums', $serialized);
    }

    #[Test]
    public function serializeFavoriteWithArtists(): void
    {
        $artists = Artist::factory()->createMany(1);

        $result = new AiAssistantResult();
        $result->action = 'add_to_favorites';
        $result->data = ['type' => FavoriteableType::ARTIST, 'entities' => $artists];

        $serialized = AiResultSerializerRegistry::serialize($result);

        self::assertSame('artist', $serialized['type']);
        self::assertArrayHasKey('artists', $serialized);
    }

    #[Test]
    public function serializeFavoriteWithRadioStations(): void
    {
        $stations = RadioStation::factory()->createMany(1);

        $result = new AiAssistantResult();
        $result->action = 'add_to_favorites';
        $result->data = ['type' => FavoriteableType::RADIO_STATION, 'entities' => $stations];

        $serialized = AiResultSerializerRegistry::serialize($result);

        self::assertSame('radio-station', $serialized['type']);
        self::assertArrayHasKey('stations', $serialized);
    }

    #[Test]
    public function serializeAddToPlaylist(): void
    {
        $songs = Song::factory()
            ->for(create_user(), 'owner')
            ->count(2)
            ->create();
        $playlist = create_playlist();

        $result = new AiAssistantResult();
        $result->action = 'add_to_playlist';
        $result->data = ['songs' => $songs, 'playlist' => $playlist];

        $serialized = AiResultSerializerRegistry::serialize($result);

        self::assertArrayHasKey('songs', $serialized);
        self::assertArrayHasKey('playlist', $serialized);
    }

    #[Test]
    public function serializeRemoveFromPlaylist(): void
    {
        $songs = Song::factory()
            ->for(create_user(), 'owner')
            ->count(1)
            ->create();
        $playlist = create_playlist();

        $result = new AiAssistantResult();
        $result->action = 'remove_from_playlist';
        $result->data = ['songs' => $songs, 'playlist' => $playlist];

        $serialized = AiResultSerializerRegistry::serialize($result);

        self::assertArrayHasKey('songs', $serialized);
        self::assertArrayHasKey('playlist', $serialized);
    }

    #[Test]
    public function serializeCreateSmartPlaylist(): void
    {
        $playlist = create_playlist();

        $result = new AiAssistantResult();
        $result->action = 'create_smart_playlist';
        $result->data = ['playlist' => $playlist];

        $serialized = AiResultSerializerRegistry::serialize($result);

        self::assertArrayHasKey('playlist', $serialized);
    }

    #[Test]
    public function serializePlayRadioStation(): void
    {
        $station = RadioStation::factory()->createOne();

        $result = new AiAssistantResult();
        $result->action = 'play_radio_station';
        $result->data = ['station' => $station];

        $serialized = AiResultSerializerRegistry::serialize($result);

        self::assertArrayHasKey('station', $serialized);
    }

    #[Test]
    public function serializeAddRadioStation(): void
    {
        $station = RadioStation::factory()->createOne();

        $result = new AiAssistantResult();
        $result->action = 'add_radio_station';
        $result->data = ['station' => $station];

        $serialized = AiResultSerializerRegistry::serialize($result);

        self::assertArrayHasKey('station', $serialized);
    }

    #[Test]
    public function serializeUpdateAlbum(): void
    {
        $album = Album::factory()->createOne();

        $result = new AiAssistantResult();
        $result->action = 'update_album';
        $result->data = ['album' => $album];

        $serialized = AiResultSerializerRegistry::serialize($result);

        self::assertArrayHasKey('album', $serialized);
    }

    #[Test]
    public function serializeUpdateArtist(): void
    {
        $artist = Artist::factory()->createOne();

        $result = new AiAssistantResult();
        $result->action = 'update_artist';
        $result->data = ['artist' => $artist];

        $serialized = AiResultSerializerRegistry::serialize($result);

        self::assertArrayHasKey('artist', $serialized);
    }

    #[Test]
    public function serializeShowLyrics(): void
    {
        $result = new AiAssistantResult();
        $result->action = 'show_lyrics';
        $result->data = ['lyrics' => 'Is this the real life? Is this just fantasy?'];

        $serialized = AiResultSerializerRegistry::serialize($result);

        self::assertSame('Is this the real life? Is this just fantasy?', $serialized['lyrics']);
    }

    #[Test]
    public function serializeUpdateLyrics(): void
    {
        $song = Song::factory()->for(create_user(), 'owner')->createOne();

        $result = new AiAssistantResult();
        $result->action = 'update_lyrics';
        $result->data = ['lyrics' => 'Updated lyrics', 'song' => $song];

        $serialized = AiResultSerializerRegistry::serialize($result);

        self::assertArrayHasKey('lyrics', $serialized);
        self::assertArrayHasKey('song', $serialized);
        self::assertSame('Updated lyrics', $serialized['lyrics']);
    }

    #[Test]
    public function serializeUpdateLyricsWithoutSong(): void
    {
        $result = new AiAssistantResult();
        $result->action = 'update_lyrics';
        $result->data = ['lyrics' => 'Some lyrics'];

        $serialized = AiResultSerializerRegistry::serialize($result);

        self::assertSame('Some lyrics', $serialized['lyrics']);
        self::assertNull($serialized['song']);
    }

    #[Test]
    public function serializeReturnsEmptyArrayForUnknownAction(): void
    {
        $result = new AiAssistantResult();
        $result->action = 'unknown_action';

        $serialized = AiResultSerializerRegistry::serialize($result);

        self::assertSame([], $serialized);
    }

    #[Test]
    public function serializeReturnsEmptyArrayForNullAction(): void
    {
        $result = new AiAssistantResult();

        $serialized = AiResultSerializerRegistry::serialize($result);

        self::assertSame([], $serialized);
    }
}
