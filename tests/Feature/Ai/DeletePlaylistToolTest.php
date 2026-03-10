<?php

namespace Tests\Feature\Ai;

use App\Ai\AiRequestContext;
use App\Ai\Tools\DeletePlaylist;
use App\Models\Playlist;
use Laravel\Ai\Tools\Request;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_user;

class DeletePlaylistToolTest extends TestCase
{
    #[Test]
    public function deletesOwnedPlaylist(): void
    {
        $playlist = Playlist::factory()->create(['name' => 'My Old Playlist']);
        $user = $playlist->owner;

        app()->instance(AiRequestContext::class, new AiRequestContext($user));
        $tool = app()->make(DeletePlaylist::class);
        $response = $tool->handle(new Request(['playlist_name' => 'Old Playlist']));

        self::assertStringContainsString('Deleted', (string) $response);
        self::assertStringContainsString('My Old Playlist', (string) $response);
        $this->assertModelMissing($playlist);
    }

    #[Test]
    public function returnsErrorWhenPlaylistNotFound(): void
    {
        $user = create_user();

        app()->instance(AiRequestContext::class, new AiRequestContext($user));
        $tool = app()->make(DeletePlaylist::class);
        $response = $tool->handle(new Request(['playlist_name' => 'Nonexistent']));

        self::assertStringContainsString('No playlist matching', (string) $response);
    }
}
