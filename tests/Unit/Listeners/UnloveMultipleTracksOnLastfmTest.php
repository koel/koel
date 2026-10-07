<?php

namespace Tests\Unit\Listeners;

use App\Events\MultipleSongsUnliked;
use App\Listeners\UnloveMultipleTracksOnLastfm;
use App\Models\Artist;
use App\Models\Song;
use App\Models\User;
use App\Services\Integrations\LastfmService;
use Illuminate\Support\Collection;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_user;

class UnloveMultipleTracksOnLastfmTest extends TestCase
{
    #[Test]
    public function unlovesOnlySongsLastfmKnows(): void
    {
        $user = create_user();
        $song = Song::factory()->createOne();
        $episode = Song::factory()->asEpisode()->createOne();
        $unknownArtist = Artist::factory()->createOne(['name' => Artist::UNKNOWN_NAME]);
        $songByUnknownArtist = Song::factory()->for($unknownArtist)->createOne();

        $lastfm = Mockery::mock(LastfmService::class);
        $lastfm
            ->expects('batchToggleLoveTracks')
            ->withArgs(
                static fn (Collection $songs, User $unlikingUser, bool $love): bool => (
                    $songs->count() === 1
                    && $song->is($songs->first())
                    && $user->is($unlikingUser)
                    && !$love
                ),
            );

        (new UnloveMultipleTracksOnLastfm($lastfm))->handle(
            new MultipleSongsUnliked(collect([$song, $episode, $songByUnknownArtist]), $user),
        );
    }
}
