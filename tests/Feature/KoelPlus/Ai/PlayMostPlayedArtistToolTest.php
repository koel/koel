<?php

namespace Tests\Feature\KoelPlus\Ai;

use App\Ai\AiAssistantResult;
use App\Ai\AiRequestContext;
use App\Ai\Tools\PlayMostPlayedArtist;
use App\Models\Album;
use App\Models\Artist;
use App\Models\Interaction;
use App\Models\Song;
use App\Models\User;
use Laravel\Ai\Tools\Request;
use PHPUnit\Framework\Attributes\Test;
use Tests\PlusTestCase;

use function Tests\create_user;

class PlayMostPlayedArtistToolTest extends PlusTestCase
{
    private AiAssistantResult $result;
    private User $user;
    private PlayMostPlayedArtist $tool;

    public function setUp(): void
    {
        parent::setUp();

        $this->user = create_user();
        $this->result = new AiAssistantResult();

        app()->instance(AiAssistantResult::class, $this->result);
        app()->instance(AiRequestContext::class, new AiRequestContext($this->user));
        $this->tool = app()->make(PlayMostPlayedArtist::class);
    }

    #[Test]
    public function playsMostPlayedArtist(): void
    {
        $artist = Artist::factory()->for($this->user)->createOne();
        $album = Album::factory()->for($artist)->createOne();
        $songs = Song::factory()
            ->count(3)
            ->for($artist)
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
        self::assertStringContainsString($artist->name, (string) $response);
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
        $artist = Artist::factory()->for($this->user)->createOne();
        $album = Album::factory()->for($artist)->createOne();
        $songs = Song::factory()
            ->count(2)
            ->for($artist)
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
