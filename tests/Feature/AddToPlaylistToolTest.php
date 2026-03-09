<?php

namespace Tests\Feature;

use App\Ai\Tools\AddToPlaylist;
use App\Helpers\Uuid;
use App\Models\Playlist;
use App\Models\Song;
use App\Repositories\PlaylistRepository;
use App\Repositories\SongRepository;
use App\Services\PlaylistService;
use Laravel\Ai\Tools\Request;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_user;

class AddToPlaylistToolTest extends TestCase
{
    #[Test]
    public function addsCurrentSongToPlaylist(): void
    {
        $user = create_user();
        $song = Song::factory()->for($user, 'owner')->create(['title' => 'Test Song']);
        $playlist = Playlist::factory()->create(['name' => 'My Rock Playlist']);
        $playlist->users()->attach($user, ['role' => 'owner']);

        $tool = new AddToPlaylist(
            $user,
            app(SongRepository::class),
            app(PlaylistRepository::class),
            app(PlaylistService::class),
            $song->id,
        );
        $response = $tool->handle(new Request(['playlist_name' => 'Rock Playlist']));

        self::assertStringContainsString('Test Song', (string) $response);
        self::assertStringContainsString('My Rock Playlist', (string) $response);
        self::assertTrue($playlist->playables()->where('songs.id', $song->id)->exists());
    }

    #[Test]
    public function returnsErrorWhenPlaylistNotFound(): void
    {
        $user = create_user();

        $tool = new AddToPlaylist(
            $user,
            app(SongRepository::class),
            app(PlaylistRepository::class),
            app(PlaylistService::class),
            null,
        );
        $response = $tool->handle(new Request(['playlist_name' => 'Nonexistent']));

        self::assertStringContainsString('No playlist matching', (string) $response);
    }

    #[Test]
    public function cannotAddToSmartPlaylist(): void
    {
        $user = create_user();
        $song = Song::factory()->for($user, 'owner')->create();
        $playlist = Playlist::factory()->create([
            'name' => 'Smart Jazz',
            'rules' => [
                [
                    'id' => Uuid::generate(),
                    'rules' => [
                        ['id' => Uuid::generate(), 'model' => 'genre', 'operator' => 'is', 'value' => ['Jazz']],
                    ],
                ],
            ],
        ]);
        $playlist->users()->attach($user, ['role' => 'owner']);

        $tool = new AddToPlaylist(
            $user,
            app(SongRepository::class),
            app(PlaylistRepository::class),
            app(PlaylistService::class),
            $song->id,
        );
        $response = $tool->handle(new Request(['playlist_name' => 'Smart Jazz']));

        self::assertStringContainsString('smart playlist', (string) $response);
    }

    #[Test]
    public function returnsErrorWhenNoSongAvailable(): void
    {
        $user = create_user();
        $playlist = Playlist::factory()->create(['name' => 'My Playlist']);
        $playlist->users()->attach($user, ['role' => 'owner']);

        $tool = new AddToPlaylist(
            $user,
            app(SongRepository::class),
            app(PlaylistRepository::class),
            app(PlaylistService::class),
            null,
        );
        $response = $tool->handle(new Request(['playlist_name' => 'My Playlist']));

        self::assertStringContainsString('Could not find', (string) $response);
    }
}
