<?php

namespace Tests\Feature\Ai;

use App\Ai\AiRequestContext;
use App\Ai\Tools\GetCurrentSong;
use App\Models\RadioStation;
use App\Models\Song;
use Laravel\Ai\Tools\Request;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_user;

class GetCurrentSongToolTest extends TestCase
{
    #[Test]
    public function returnsCurrentSongInfo(): void
    {
        $user = create_user();
        $song = Song::factory()->for($user, 'owner')->create([
            'title' => 'Bohemian Rhapsody',
            'artist_name' => 'Queen',
            'album_name' => 'A Night at the Opera',
        ]);

        app()->instance(AiRequestContext::class, new AiRequestContext($user, currentSongId: $song->id));
        $tool = app()->make(GetCurrentSong::class);

        $response = (string) $tool->handle(new Request([]));

        self::assertStringContainsString('Bohemian Rhapsody', $response);
        self::assertStringContainsString('Queen', $response);
        self::assertStringContainsString('A Night at the Opera', $response);
    }

    #[Test]
    public function returnsCurrentRadioStationInfo(): void
    {
        $user = create_user();
        $station = RadioStation::factory()->create(['name' => 'Jazz FM']);

        app()->instance(AiRequestContext::class, new AiRequestContext($user, currentRadioStationId: $station->id));
        $tool = app()->make(GetCurrentSong::class);

        $response = (string) $tool->handle(new Request([]));

        self::assertStringContainsString('Jazz FM', $response);
        self::assertStringContainsString('radio station', $response);
    }

    #[Test]
    public function radioStationTakesPrecedenceOverSong(): void
    {
        $user = create_user();
        $song = Song::factory()->for($user, 'owner')->create(['title' => 'Some Song']);
        $station = RadioStation::factory()->create(['name' => 'Rock Radio']);

        app()->instance(
            AiRequestContext::class,
            new AiRequestContext($user, currentSongId: $song->id, currentRadioStationId: $station->id),
        );
        $tool = app()->make(GetCurrentSong::class);

        $response = (string) $tool->handle(new Request([]));

        self::assertStringContainsString('Rock Radio', $response);
        self::assertStringNotContainsString('Some Song', $response);
    }

    #[Test]
    public function returnsNothingPlayingWhenNoContext(): void
    {
        $user = create_user();

        app()->instance(AiRequestContext::class, new AiRequestContext($user));
        $tool = app()->make(GetCurrentSong::class);

        $response = (string) $tool->handle(new Request([]));

        self::assertStringContainsString('Nothing is currently playing', $response);
    }
}
