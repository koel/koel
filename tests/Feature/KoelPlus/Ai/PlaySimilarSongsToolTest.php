<?php

namespace Tests\Feature\KoelPlus\Ai;

use App\Ai\AiAssistantResult;
use App\Ai\AiRequestContext;
use App\Ai\Tools\PlaySimilarSongs;
use App\Models\Song;
use App\Models\User;
use Laravel\Ai\Tools\Request;
use PHPUnit\Framework\Attributes\Test;
use Tests\PlusTestCase;

use function Tests\create_user;

class PlaySimilarSongsToolTest extends PlusTestCase
{
    private AiAssistantResult $result;
    private User $user;
    private PlaySimilarSongs $tool;

    public function setUp(): void
    {
        parent::setUp();

        $this->user = create_user();
        $this->result = new AiAssistantResult();

        app()->instance(AiAssistantResult::class, $this->result);
        app()->instance(AiRequestContext::class, new AiRequestContext($this->user));
        $this->tool = app()->make(PlaySimilarSongs::class);
    }

    #[Test]
    public function findsSimilarSongsByArtist(): void
    {
        $referenceSong = Song::factory()->for($this->user, 'owner')->createOne(['title' => 'One']);
        Song::factory()
            ->for($this->user, 'owner')
            ->for($referenceSong->artist)
            ->createOne(['title' => 'Two']);
        Song::factory()->for($this->user, 'owner')->createOne(['title' => 'Unrelated']);

        app()->instance(AiRequestContext::class, new AiRequestContext($this->user, currentSongId: $referenceSong->id));
        $this->tool = app()->make(PlaySimilarSongs::class);

        $response = $this->tool->handle(new Request([]));

        self::assertSame('play_songs', $this->result->action);
        self::assertCount(1, $this->result->data['songs']);
        self::assertSame('Two', $this->result->data['songs']->first()->title);
        self::assertStringContainsString('One', (string) $response);
    }

    #[Test]
    public function findsSimilarSongsByGenre(): void
    {
        $referenceSong = Song::factory()->for($this->user, 'owner')->createOne(['title' => 'Rock Song']);
        $referenceSong->syncGenres('Rock');
        $similarSong = Song::factory()->for($this->user, 'owner')->createOne(['title' => 'Another Rock Song']);
        $similarSong->syncGenres('Rock');

        Song::factory()->for($this->user, 'owner')->createOne(['title' => 'Jazz Song']);

        app()->instance(AiRequestContext::class, new AiRequestContext($this->user, currentSongId: $referenceSong->id));
        $this->tool = app()->make(PlaySimilarSongs::class);

        $response = $this->tool->handle(new Request([]));

        self::assertSame('play_songs', $this->result->action);
        self::assertNotEmpty($this->result->data['songs']);
        self::assertTrue($this->result->data['songs']->contains('title', 'Another Rock Song'));
        self::assertFalse($this->result->data['songs']->contains('title', 'Jazz Song'));
    }

    #[Test]
    public function returnsNotFoundWhenNoSimilarSongs(): void
    {
        $song = Song::factory()->for($this->user, 'owner')->createOne(['title' => 'Lonely Song']);

        app()->instance(AiRequestContext::class, new AiRequestContext($this->user, currentSongId: $song->id));
        $this->tool = app()->make(PlaySimilarSongs::class);

        $response = $this->tool->handle(new Request([]));

        self::assertNull($this->result->action);
        self::assertStringContainsString('No similar songs', (string) $response);
    }

    #[Test]
    public function usesCurrentlyPlayingSongWhenNoTitleSpecified(): void
    {
        $referenceSong = Song::factory()->for($this->user, 'owner')->createOne(['title' => 'Master of Puppets']);
        Song::factory()
            ->for($this->user, 'owner')
            ->for($referenceSong->artist)
            ->createOne(['title' => 'Battery']);

        app()->instance(AiRequestContext::class, new AiRequestContext($this->user, currentSongId: $referenceSong->id));
        $this->tool = app()->make(PlaySimilarSongs::class);

        $response = $this->tool->handle(new Request([]));

        self::assertSame('play_songs', $this->result->action);
        self::assertNotEmpty($this->result->data['songs']);
        self::assertStringContainsString('Master of Puppets', (string) $response);
    }

    #[Test]
    public function returnsErrorWhenNoSongSpecifiedAndNoneIsPlaying(): void
    {
        $response = $this->tool->handle(new Request([]));

        self::assertNull($this->result->action);
        self::assertStringContainsString('Could not determine', (string) $response);
    }
}
