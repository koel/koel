<?php

namespace Tests\Feature\KoelPlus\Ai;

use App\Ai\AiRequestContext;
use App\Ai\Tools\RenamePlaylist;
use App\Models\Playlist;
use App\Models\User;
use Laravel\Ai\Tools\Request;
use PHPUnit\Framework\Attributes\Test;
use Tests\PlusTestCase;

use function Tests\create_user;

class RenamePlaylistToolTest extends PlusTestCase
{
    private User $user;
    private RenamePlaylist $tool;

    public function setUp(): void
    {
        parent::setUp();

        $this->user = create_user();

        app()->instance(AiRequestContext::class, new AiRequestContext($this->user));
        $this->tool = app()->make(RenamePlaylist::class);
    }

    #[Test]
    public function renamesOwnedPlaylist(): void
    {
        $playlist = Playlist::factory()->createOne(['name' => 'Old Name']);
        $user = $playlist->owner;

        app()->instance(AiRequestContext::class, new AiRequestContext($user));
        $this->tool = app()->make(RenamePlaylist::class);

        $response = $this->tool->handle(new Request(['current_name' => 'Old Name', 'new_name' => 'New Name']));

        self::assertStringContainsString('Renamed', (string) $response);
        self::assertStringContainsString('Old Name', (string) $response);
        self::assertStringContainsString('New Name', (string) $response);
        self::assertSame('New Name', $playlist->fresh()->name);
    }

    #[Test]
    public function returnsErrorWhenPlaylistNotFound(): void
    {
        $response = $this->tool->handle(new Request(['current_name' => 'Nonexistent', 'new_name' => 'Whatever']));

        self::assertStringContainsString('No playlist matching', (string) $response);
    }
}
