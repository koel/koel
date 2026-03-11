<?php

namespace Tests\Feature\Ai;

use App\Ai\AiAssistantResult;
use App\Ai\AiRequestContext;
use App\Ai\Tools\PlayPlaylist;
use App\Models\Playlist;
use App\Models\Song;
use App\Models\User;
use Laravel\Ai\Tools\Request;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_user;

class PlayPlaylistToolTest extends TestCase
{
    private AiAssistantResult $result;
    private User $user;
    private PlayPlaylist $tool;

    public function setUp(): void
    {
        parent::setUp();

        $this->user = create_user();
        $this->result = new AiAssistantResult();

        app()->instance(AiAssistantResult::class, $this->result);
        app()->instance(AiRequestContext::class, new AiRequestContext($this->user));
        $this->tool = app()->make(PlayPlaylist::class);
    }

    #[Test]
    public function playsPlaylistByName(): void
    {
        /** @var Playlist $playlist */
        $playlist = Playlist::factory()->create(['name' => 'Chill Vibes']);
        $playlist->users()->attach($this->user, ['role' => 'owner']);

        $songs = Song::factory()
            ->count(3)
            ->for($this->user, 'owner')
            ->create();
        $playlist->addPlayables($songs, $this->user);

        $response = $this->tool->handle(new Request(['name' => 'Chill']));

        self::assertSame('play_songs', $this->result->action);
        self::assertCount(3, $this->result->data['songs']);
        self::assertStringContainsString('Chill Vibes', (string) $response);
    }

    #[Test]
    public function returnsErrorWhenPlaylistNotFound(): void
    {
        $response = $this->tool->handle(new Request(['name' => 'Nonexistent']));

        self::assertNull($this->result->action);
        self::assertStringContainsString('No playlist matching', (string) $response);
    }

    #[Test]
    public function returnsErrorWhenPlaylistIsEmpty(): void
    {
        /** @var Playlist $playlist */
        $playlist = Playlist::factory()->create(['name' => 'Empty Playlist']);
        $playlist->users()->attach($this->user, ['role' => 'owner']);

        $response = $this->tool->handle(new Request(['name' => 'Empty']));

        self::assertNull($this->result->action);
        self::assertStringContainsString('no songs', (string) $response);
    }

    #[Test]
    public function doesNotPlayOtherUsersPlaylist(): void
    {
        $otherUser = create_user();
        /** @var Playlist $playlist */
        $playlist = Playlist::factory()->create(['name' => 'Secret Playlist']);
        $playlist->users()->attach($otherUser, ['role' => 'owner']);

        $response = $this->tool->handle(new Request(['name' => 'Secret']));

        self::assertNull($this->result->action);
        self::assertStringContainsString('No playlist matching', (string) $response);
    }
}
