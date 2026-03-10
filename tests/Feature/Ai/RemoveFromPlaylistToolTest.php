<?php

namespace Tests\Feature\Ai;

use App\Ai\AiAssistantResult;
use App\Ai\AiRequestContext;
use App\Ai\Tools\RemoveFromPlaylist;
use App\Helpers\Uuid;
use App\Models\Playlist;
use App\Models\Song;
use Laravel\Ai\Tools\Request;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_user;

class RemoveFromPlaylistToolTest extends TestCase
{
    #[Test]
    public function removesCurrentSongFromPlaylist(): void
    {
        $playlist = Playlist::factory()->create(['name' => 'My Rock Playlist']);
        $user = $playlist->owner;
        $song = Song::factory()->for($user, 'owner')->create(['title' => 'Test Song']);
        $playlist->playables()->attach($song, ['user_id' => $user->id]);

        $result = new AiAssistantResult();
        app()->instance(AiAssistantResult::class, $result);
        app()->instance(AiRequestContext::class, new AiRequestContext($user, currentSongId: $song->id));
        $tool = app()->make(RemoveFromPlaylist::class);
        $response = $tool->handle(new Request(['playlist_name' => 'Rock Playlist']));

        self::assertSame('remove_from_playlist', $result->action);
        self::assertStringContainsString('Test Song', (string) $response);
        self::assertStringContainsString('My Rock Playlist', (string) $response);
        self::assertFalse($playlist->playables()->where('songs.id', $song->id)->exists());
    }

    #[Test]
    public function returnsErrorWhenPlaylistNotFound(): void
    {
        $user = create_user();

        app()->instance(AiAssistantResult::class, new AiAssistantResult());
        app()->instance(AiRequestContext::class, new AiRequestContext($user));
        $tool = app()->make(RemoveFromPlaylist::class);
        $response = $tool->handle(new Request(['playlist_name' => 'Nonexistent']));

        self::assertStringContainsString('No playlist matching', (string) $response);
    }

    #[Test]
    public function cannotRemoveFromSmartPlaylist(): void
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
        $tool = app()->make(RemoveFromPlaylist::class);
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
        $tool = app()->make(RemoveFromPlaylist::class);
        $response = $tool->handle(new Request(['playlist_name' => 'My Playlist']));

        self::assertStringContainsString('Could not find', (string) $response);
    }
}
