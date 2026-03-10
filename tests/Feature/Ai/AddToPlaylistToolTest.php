<?php

namespace Tests\Feature\Ai;

use App\Ai\AiAssistantResult;
use App\Ai\AiRequestContext;
use App\Ai\Tools\AddToPlaylist;
use App\Helpers\Uuid;
use App\Models\Playlist;
use App\Models\Song;
use Laravel\Ai\Tools\Request;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_user;

class AddToPlaylistToolTest extends TestCase
{
    #[Test]
    public function addsCurrentSongToPlaylist(): void
    {
        $playlist = Playlist::factory()->create(['name' => 'My Rock Playlist']);
        $user = $playlist->owner;
        $song = Song::factory()->for($user, 'owner')->create(['title' => 'Test Song']);

        app()->instance(AiAssistantResult::class, new AiAssistantResult());
        app()->instance(AiRequestContext::class, new AiRequestContext($user, currentSongId: $song->id));
        $tool = app()->make(AddToPlaylist::class);
        $response = $tool->handle(new Request(['playlist_name' => 'Rock Playlist']));

        self::assertStringContainsString('Test Song', (string) $response);
        self::assertStringContainsString('My Rock Playlist', (string) $response);
        self::assertTrue($playlist->playables()->where('songs.id', $song->id)->exists());
    }

    #[Test]
    public function returnsErrorWhenPlaylistNotFound(): void
    {
        $user = create_user();

        app()->instance(AiAssistantResult::class, new AiAssistantResult());
        app()->instance(AiRequestContext::class, new AiRequestContext($user));
        $tool = app()->make(AddToPlaylist::class);
        $response = $tool->handle(new Request(['playlist_name' => 'Nonexistent']));

        self::assertStringContainsString('No playlist matching', (string) $response);
    }

    #[Test]
    public function cannotAddToSmartPlaylist(): void
    {
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
        $user = $playlist->owner;
        $song = Song::factory()->for($user, 'owner')->create();

        app()->instance(AiAssistantResult::class, new AiAssistantResult());
        app()->instance(AiRequestContext::class, new AiRequestContext($user, currentSongId: $song->id));
        $tool = app()->make(AddToPlaylist::class);
        $response = $tool->handle(new Request(['playlist_name' => 'Smart Jazz']));

        self::assertStringContainsString('smart playlist', (string) $response);
    }

    #[Test]
    public function returnsErrorWhenNoSongAvailable(): void
    {
        $playlist = Playlist::factory()->create(['name' => 'My Playlist']);
        $user = $playlist->owner;

        app()->instance(AiAssistantResult::class, new AiAssistantResult());
        app()->instance(AiRequestContext::class, new AiRequestContext($user));
        $tool = app()->make(AddToPlaylist::class);
        $response = $tool->handle(new Request(['playlist_name' => 'My Playlist']));

        self::assertStringContainsString('Could not find', (string) $response);
    }
}
