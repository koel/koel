<?php

namespace Tests\Feature\KoelPlus\Ai;

use App\Ai\AiAssistantResult;
use App\Ai\AiRequestContext;
use App\Ai\Tools\PlaySongsByLyrics;
use App\Models\Song;
use App\Models\User;
use App\Repositories\SongRepository;
use Illuminate\Database\Eloquent\Collection;
use Laravel\Ai\Tools\Request;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;
use Tests\PlusTestCase;

use function Tests\create_user;

class PlaySongsByLyricsToolTest extends PlusTestCase
{
    private AiAssistantResult $result;
    private User $user;
    private PlaySongsByLyrics $tool;
    private SongRepository|MockInterface $songRepository;

    public function setUp(): void
    {
        parent::setUp();

        $this->user = create_user();
        $this->result = new AiAssistantResult();
        $this->songRepository = Mockery::mock(SongRepository::class);

        app()->instance(AiAssistantResult::class, $this->result);
        app()->instance(SongRepository::class, $this->songRepository);
        app()->instance(AiRequestContext::class, new AiRequestContext($this->user));
        $this->tool = app()->make(PlaySongsByLyrics::class);
    }

    #[Test]
    public function findsSongsByLyrics(): void
    {
        $song = Song::factory()->for($this->user, 'owner')->createOne(['title' => 'Bohemian Rhapsody']);

        $this->songRepository
            ->shouldReceive('searchByLyrics')
            ->with('real life', 50, $this->user)
            ->andReturn(new Collection([$song]));

        $response = $this->tool->handle(new Request(['lyrics' => 'real life']));

        self::assertSame('play_songs', $this->result->action);
        self::assertNotEmpty($this->result->data['songs']);
        self::assertStringContainsString('Bohemian Rhapsody', (string) $response);
    }

    #[Test]
    public function returnsNotFoundMessageWhenNoLyricsMatch(): void
    {
        $this->songRepository
            ->shouldReceive('searchByLyrics')
            ->with('zzzznonexistentgibberishxyz', 50, $this->user)
            ->andReturn(new Collection());

        $response = $this->tool->handle(new Request(['lyrics' => 'zzzznonexistentgibberishxyz']));

        self::assertNull($this->result->action);
        self::assertStringContainsString('No songs found', (string) $response);
    }

    #[Test]
    public function matchesPartialLyrics(): void
    {
        $song = Song::factory()->for($this->user, 'owner')->createOne(['title' => 'Stairway to Heaven']);

        $this->songRepository
            ->shouldReceive('searchByLyrics')
            ->with('glitters is gold', 50, $this->user)
            ->andReturn(new Collection([$song]));

        $response = $this->tool->handle(new Request(['lyrics' => 'glitters is gold']));

        self::assertSame('play_songs', $this->result->action);
        self::assertNotEmpty($this->result->data['songs']);
        self::assertStringContainsString('Stairway to Heaven', (string) $response);
    }
}
