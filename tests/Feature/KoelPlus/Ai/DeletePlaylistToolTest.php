<?php

namespace Tests\Feature\KoelPlus\Ai;

use App\Ai\AiRequestContext;
use App\Ai\Tools\DeletePlaylist;
use App\Models\Playlist;
use App\Models\User;
use Laravel\Ai\Tools\Request;
use PHPUnit\Framework\Attributes\Test;
use Tests\PlusTestCase;

use function Tests\create_user;

class DeletePlaylistToolTest extends PlusTestCase
{
    private User $user;
    private DeletePlaylist $tool;

    public function setUp(): void
    {
        parent::setUp();

        $this->user = create_user();

        app()->instance(AiRequestContext::class, new AiRequestContext($this->user));
        $this->tool = app()->make(DeletePlaylist::class);
    }

    #[Test]
    public function deletesOwnedPlaylist(): void
    {
        $playlist = Playlist::factory()->createOne(['name' => 'My Old Playlist']);
        $user = $playlist->owner;

        app()->instance(AiRequestContext::class, new AiRequestContext($user));
        $this->tool = app()->make(DeletePlaylist::class);

        $response = $this->tool->handle(new Request(['playlist_name' => 'Old Playlist']));

        self::assertStringContainsString('Deleted', (string) $response);
        self::assertStringContainsString('My Old Playlist', (string) $response);
        $this->assertModelMissing($playlist);
    }

    #[Test]
    public function returnsErrorWhenPlaylistNotFound(): void
    {
        $response = $this->tool->handle(new Request(['playlist_name' => 'Nonexistent']));

        self::assertStringContainsString('No playlist matching', (string) $response);
    }
}
