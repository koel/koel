<?php

namespace Tests\Feature\KoelPlus\Ai;

use App\Ai\AiAssistantResult;
use App\Ai\AiRequestContext;
use App\Ai\Tools\RemoveFromPlaylist;
use App\Helpers\Uuid;
use App\Models\Playlist;
use App\Models\Song;
use App\Models\User;
use Laravel\Ai\Tools\Request;
use PHPUnit\Framework\Attributes\Test;
use Tests\PlusTestCase;

use function Tests\create_user;

class RemoveFromPlaylistToolTest extends PlusTestCase
{
    private AiAssistantResult $result;
    private User $user;
    private RemoveFromPlaylist $tool;

    public function setUp(): void
    {
        parent::setUp();

        $this->user = create_user();
        $this->result = new AiAssistantResult();

        app()->instance(AiAssistantResult::class, $this->result);
        app()->instance(AiRequestContext::class, new AiRequestContext($this->user));
        $this->tool = app()->make(RemoveFromPlaylist::class);
    }

    #[Test]
    public function removesCurrentSongFromPlaylist(): void
    {
        $playlist = Playlist::factory()->createOne(['name' => 'My Rock Playlist']);
        $user = $playlist->owner;
        $song = Song::factory()->for($user, 'owner')->createOne(['title' => 'Test Song']);
        $playlist->playables()->attach($song, ['user_id' => $user->id]);

        app()->instance(AiRequestContext::class, new AiRequestContext($user, currentSongId: $song->id));
        $this->tool = app()->make(RemoveFromPlaylist::class);

        $response = $this->tool->handle(new Request(['playlist_name' => 'Rock Playlist']));

        self::assertSame('remove_from_playlist', $this->result->action);
        self::assertStringContainsString('Test Song', (string) $response);
        self::assertStringContainsString('My Rock Playlist', (string) $response);
        self::assertFalse($playlist->playables()->where('songs.id', $song->id)->exists());
    }

    #[Test]
    public function returnsErrorWhenPlaylistNotFound(): void
    {
        $response = $this->tool->handle(new Request(['playlist_name' => 'Nonexistent']));

        self::assertStringContainsString('No playlist matching', (string) $response);
    }

    #[Test]
    public function cannotRemoveFromSmartPlaylist(): void
    {
        $playlist = Playlist::factory()->createOne([
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
        $song = Song::factory()->for($user, 'owner')->createOne();

        app()->instance(AiRequestContext::class, new AiRequestContext($user, currentSongId: $song->id));
        $this->tool = app()->make(RemoveFromPlaylist::class);

        $response = $this->tool->handle(new Request(['playlist_name' => 'Smart Jazz']));

        self::assertStringContainsString('smart playlist', (string) $response);
    }

    #[Test]
    public function returnsErrorWhenNoSongAvailable(): void
    {
        $playlist = Playlist::factory()->createOne(['name' => 'My Playlist']);
        $user = $playlist->owner;

        app()->instance(AiRequestContext::class, new AiRequestContext($user));
        $this->tool = app()->make(RemoveFromPlaylist::class);

        $response = $this->tool->handle(new Request(['playlist_name' => 'My Playlist']));

        self::assertStringContainsString('Could not find', (string) $response);
    }
}
