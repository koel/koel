<?php

namespace Tests\Feature;

use App\Ai\AiAssistantResult;
use App\Ai\Tools\PlayPlaylist;
use App\Models\Playlist;
use App\Models\Song;
use App\Repositories\PlaylistRepository;
use App\Repositories\SongRepository;
use Laravel\Ai\Tools\Request;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_user;

class PlayPlaylistToolTest extends TestCase
{
    #[Test]
    public function playsPlaylistByName(): void
    {
        $user = create_user();
        $playlist = Playlist::factory()->create(['name' => 'Chill Vibes']);
        $playlist->users()->attach($user, ['role' => 'owner']);

        $songs = Song::factory()
            ->count(3)
            ->for($user, 'owner')
            ->create();
        $playlist->addPlayables($songs, $user);

        $result = new AiAssistantResult();
        $tool = new PlayPlaylist($user, $result, app(PlaylistRepository::class), app(SongRepository::class));
        $response = $tool->handle(new Request(['name' => 'Chill']));

        self::assertSame('play_songs', $result->action);
        self::assertCount(3, $result->data['songs']);
        self::assertStringContainsString('Chill Vibes', (string) $response);
    }

    #[Test]
    public function returnsErrorWhenPlaylistNotFound(): void
    {
        $user = create_user();

        $result = new AiAssistantResult();
        $tool = new PlayPlaylist($user, $result, app(PlaylistRepository::class), app(SongRepository::class));
        $response = $tool->handle(new Request(['name' => 'Nonexistent']));

        self::assertNull($result->action);
        self::assertStringContainsString('No playlist matching', (string) $response);
    }

    #[Test]
    public function returnsErrorWhenPlaylistIsEmpty(): void
    {
        $user = create_user();
        $playlist = Playlist::factory()->create(['name' => 'Empty Playlist']);
        $playlist->users()->attach($user, ['role' => 'owner']);

        $result = new AiAssistantResult();
        $tool = new PlayPlaylist($user, $result, app(PlaylistRepository::class), app(SongRepository::class));
        $response = $tool->handle(new Request(['name' => 'Empty']));

        self::assertNull($result->action);
        self::assertStringContainsString('no songs', (string) $response);
    }

    #[Test]
    public function doesNotPlayOtherUsersPlaylist(): void
    {
        $user = create_user();
        $otherUser = create_user();
        $playlist = Playlist::factory()->create(['name' => 'Secret Playlist']);
        $playlist->users()->attach($otherUser, ['role' => 'owner']);

        $result = new AiAssistantResult();
        $tool = new PlayPlaylist($user, $result, app(PlaylistRepository::class), app(SongRepository::class));
        $response = $tool->handle(new Request(['name' => 'Secret']));

        self::assertNull($result->action);
        self::assertStringContainsString('No playlist matching', (string) $response);
    }
}
