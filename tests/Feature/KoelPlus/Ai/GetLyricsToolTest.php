<?php

namespace Tests\Feature\KoelPlus\Ai;

use App\Ai\AiAssistantResult;
use App\Ai\AiRequestContext;
use App\Ai\Tools\GetLyrics;
use App\Models\Song;
use App\Models\User;
use Laravel\Ai\Tools\Request;
use PHPUnit\Framework\Attributes\Test;
use Tests\PlusTestCase;

use function Tests\create_user;

class GetLyricsToolTest extends PlusTestCase
{
    private AiAssistantResult $result;
    private User $user;
    private GetLyrics $tool;

    public function setUp(): void
    {
        parent::setUp();

        $this->user = create_user();
        $this->result = new AiAssistantResult();

        app()->instance(AiAssistantResult::class, $this->result);
        app()->instance(AiRequestContext::class, new AiRequestContext($this->user));
        $this->tool = app()->make(GetLyrics::class);
    }

    #[Test]
    public function getsLyricsOfCurrentlyPlayingSong(): void
    {
        $song = Song::factory()->for($this->user, 'owner')->createOne([
            'title' => 'Bohemian Rhapsody',
            'lyrics' => "Is this the real life?\nIs this just fantasy?",
        ]);

        app()->instance(AiRequestContext::class, new AiRequestContext($this->user, currentSongId: $song->id));
        $this->tool = app()->make(GetLyrics::class);

        $response = $this->tool->handle(new Request([]));

        self::assertSame('show_lyrics', $this->result->action);
        self::assertStringContainsString('Is this the real life?', $this->result->data['lyrics']);
        self::assertStringContainsString('Bohemian Rhapsody', (string) $response);
    }

    #[Test]
    public function returnsMessageWhenNoLyricsAvailable(): void
    {
        $song = Song::factory()->for($this->user, 'owner')->createOne([
            'title' => 'Instrumental Track',
            'lyrics' => '',
        ]);

        app()->instance(AiRequestContext::class, new AiRequestContext($this->user, currentSongId: $song->id));
        $this->tool = app()->make(GetLyrics::class);

        $response = $this->tool->handle(new Request([]));

        self::assertNull($this->result->action);
        self::assertStringContainsString('No lyrics available', (string) $response);
        self::assertStringContainsString('Instrumental Track', (string) $response);
    }

    #[Test]
    public function returnsErrorWhenNoSongAvailable(): void
    {
        $response = $this->tool->handle(new Request([]));

        self::assertNull($this->result->action);
        self::assertStringContainsString('Could not find', (string) $response);
    }
}
