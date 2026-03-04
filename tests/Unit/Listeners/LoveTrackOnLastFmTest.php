<?php

namespace Tests\Unit\Listeners;

use App\Events\SongFavoriteToggled;
use App\Listeners\LoveTrackOnLastfm;
use App\Models\Song;
use App\Services\LastfmService;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_user;

class LoveTrackOnLastFmTest extends TestCase
{
    #[Test]
    public function handleFavoriteCase(): void
    {
        /** @var Song $song */
        $song = Song::factory()->create();

        $user = create_user();

        $lastfm = Mockery::mock(LastfmService::class, ['enabled' => true]);
        $lastfm->expects('toggleLoveTrack')->with($song, $user, true);

        (new LoveTrackOnLastfm($lastfm))->handle(new SongFavoriteToggled($song, true, $user));
    }

    #[Test]
    public function handleUndoFavoriteCase(): void
    {
        /** @var Song $song */
        $song = Song::factory()->create();

        $user = create_user();

        $lastfm = Mockery::mock(LastfmService::class, ['enabled' => true]);
        $lastfm->expects('toggleLoveTrack')->with($song, $user, false);

        (new LoveTrackOnLastfm($lastfm))->handle(new SongFavoriteToggled($song, false, $user));
    }
}
