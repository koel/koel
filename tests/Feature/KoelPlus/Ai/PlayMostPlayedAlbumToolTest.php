<?php

namespace Tests\Feature\KoelPlus\Ai;

use App\Ai\AiAssistantResult;
use App\Ai\AiRequestContext;
use App\Ai\Tools\PlayMostPlayedAlbum;
use App\Models\Album;
use App\Models\Interaction;
use App\Models\Song;
use App\Models\User;
use Laravel\Ai\Tools\Request;
use PHPUnit\Framework\Attributes\Test;
use Tests\PlusTestCase;

use function Tests\create_user;

class PlayMostPlayedAlbumToolTest extends PlusTestCase
{
    private AiAssistantResult $result;
    private User $user;
    private PlayMostPlayedAlbum $tool;

    public function setUp(): void
    {
        parent::setUp();

        $this->user = create_user();
        $this->result = new AiAssistantResult();

        app()->instance(AiAssistantResult::class, $this->result);
        app()->instance(AiRequestContext::class, new AiRequestContext($this->user));
        $this->tool = app()->make(PlayMostPlayedAlbum::class);
    }

    #[Test]
    public function playsMostPlayedAlbum(): void
    {
        $album = Album::factory()->for($this->user)->createOne();
        $songs = Song::factory()
            ->count(3)
            ->for($album)
            ->for($this->user, 'owner')
            ->create();

        foreach ($songs as $song) {
            Interaction::factory()
                ->for($this->user)
                ->for($song)
                ->createOne(['play_count' => 10]);
        }

        $response = $this->tool->handle(new Request([]));

        self::assertSame('play_songs', $this->result->action);
        self::assertCount(3, $this->result->data['songs']);
        self::assertStringContainsString($album->name, (string) $response);
    }

    #[Test]
    public function returnsErrorWhenNoPlayHistory(): void
    {
        $response = $this->tool->handle(new Request([]));

        self::assertNull($this->result->action);
        self::assertStringContainsString('No play history', (string) $response);
    }

    #[Test]
    public function queuesInsteadOfPlaying(): void
    {
        $album = Album::factory()->for($this->user)->createOne();
        $songs = Song::factory()
            ->count(2)
            ->for($album)
            ->for($this->user, 'owner')
            ->create();

        foreach ($songs as $song) {
            Interaction::factory()
                ->for($this->user)
                ->for($song)
                ->createOne(['play_count' => 5]);
        }

        $response = $this->tool->handle(new Request(['queue' => true]));

        self::assertSame('play_songs', $this->result->action);
        self::assertTrue($this->result->data['queue']);
        self::assertStringContainsString('Added', (string) $response);
        self::assertStringContainsString('queue', (string) $response);
    }
}
