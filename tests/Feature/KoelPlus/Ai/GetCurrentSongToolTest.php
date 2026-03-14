<?php

namespace Tests\Feature\KoelPlus\Ai;

use App\Ai\AiRequestContext;
use App\Ai\Tools\GetCurrentSong;
use App\Models\RadioStation;
use App\Models\Song;
use App\Models\User;
use Laravel\Ai\Tools\Request;
use PHPUnit\Framework\Attributes\Test;
use Tests\PlusTestCase;

use function Tests\create_user;

class GetCurrentSongToolTest extends PlusTestCase
{
    private User $user;
    private GetCurrentSong $tool;

    public function setUp(): void
    {
        parent::setUp();

        $this->user = create_user();

        app()->instance(AiRequestContext::class, new AiRequestContext($this->user));
        $this->tool = app()->make(GetCurrentSong::class);
    }

    #[Test]
    public function returnsCurrentSongInfo(): void
    {
        $song = Song::factory()->for($this->user, 'owner')->createOne([
            'title' => 'Bohemian Rhapsody',
            'artist_name' => 'Queen',
            'album_name' => 'A Night at the Opera',
        ]);

        app()->instance(AiRequestContext::class, new AiRequestContext($this->user, currentSongId: $song->id));
        $this->tool = app()->make(GetCurrentSong::class);

        $response = (string) $this->tool->handle(new Request([]));

        self::assertStringContainsString('Bohemian Rhapsody', $response);
        self::assertStringContainsString('Queen', $response);
        self::assertStringContainsString('A Night at the Opera', $response);
    }

    #[Test]
    public function returnsCurrentRadioStationInfo(): void
    {
        $station = RadioStation::factory()->createOne(['name' => 'Jazz FM']);

        app()->instance(
            AiRequestContext::class,
            new AiRequestContext($this->user, currentRadioStationId: $station->id),
        );
        $this->tool = app()->make(GetCurrentSong::class);

        $response = (string) $this->tool->handle(new Request([]));

        self::assertStringContainsString('Jazz FM', $response);
        self::assertStringContainsString('radio station', $response);
    }

    #[Test]
    public function radioStationTakesPrecedenceOverSong(): void
    {
        $song = Song::factory()->for($this->user, 'owner')->createOne(['title' => 'Some Song']);
        $station = RadioStation::factory()->createOne(['name' => 'Rock Radio']);

        app()->instance(
            AiRequestContext::class,
            new AiRequestContext($this->user, currentSongId: $song->id, currentRadioStationId: $station->id),
        );
        $this->tool = app()->make(GetCurrentSong::class);

        $response = (string) $this->tool->handle(new Request([]));

        self::assertStringContainsString('Rock Radio', $response);
        self::assertStringNotContainsString('Some Song', $response);
    }

    #[Test]
    public function returnsNothingPlayingWhenNoContext(): void
    {
        $response = (string) $this->tool->handle(new Request([]));

        self::assertStringContainsString('Nothing is currently playing', $response);
    }
}
