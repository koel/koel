<?php

namespace Tests\Integration\Observers;

use App\Events\UserUnsubscribedFromPodcast;
use App\Facades\Dispatcher;
use App\Jobs\DeleteSongFilesJob;
use App\Models\Album;
use App\Models\Artist;
use App\Models\Podcast;
use App\Models\RadioStation;
use App\Models\Song;
use Illuminate\Support\Facades\Event;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_admin;
use function Tests\create_playlist;
use function Tests\create_playlists;
use function Tests\create_user;

class UserObserverTest extends TestCase
{
    #[Test]
    public function keepTheScannedLibraryWhenItsOwningAdminIsDeleted(): void
    {
        $owningAdmin = create_admin();
        $otherAdmin = create_admin();
        $artist = Artist::factory()->for($owningAdmin)->createOne();
        $scannedSong = Song::factory()
            ->for($owningAdmin, 'owner')
            ->for($artist)
            ->for(Album::factory()->for($owningAdmin)->for($artist))
            ->createOne(['path' => '/music/Scanned.mp3']);

        $owningAdmin->delete();

        self::assertTrue($scannedSong->refresh()->owner->is($otherAdmin));
    }

    #[Test]
    public function deleteTheUsersSongsAndTheirFiles(): void
    {
        $user = create_user();
        $ownSongs = Song::factory()->for($user, 'owner')->createMany(2);
        $otherSong = Song::factory()->createOne();

        Dispatcher::expects('dispatch')->with(Mockery::on(
            static fn (DeleteSongFilesJob $job): bool => (
                $job->files->pluck('location')->sort()->values()->all() === $ownSongs
                    ->pluck('path')
                    ->sort()
                    ->values()
                    ->all()
            ),
        ));

        $user->delete();

        $ownSongs->each($this->assertModelMissing(...));
        $this->assertModelExists($otherSong);
    }

    #[Test]
    public function deleteTheUsersPlaylistsAndRadioStations(): void
    {
        $user = create_user();
        $ownPlaylist = create_playlists(1, owner: $user)->first();
        $otherPlaylist = create_playlist();
        $ownStation = RadioStation::factory()->for($user)->createOne();

        $user->delete();

        $this->assertModelMissing($ownPlaylist);
        $this->assertModelExists($otherPlaylist);
        $this->assertModelMissing($ownStation);
    }

    #[Test]
    public function unsubscribeTheUserFromTheirPodcasts(): void
    {
        $user = create_user();
        $podcast = Podcast::factory()->createOne();
        $user->podcasts()->attach($podcast);

        Event::fake(UserUnsubscribedFromPodcast::class);

        $user->delete();

        Event::assertDispatched(UserUnsubscribedFromPodcast::class, static fn (UserUnsubscribedFromPodcast $event): bool => $event->podcast->is(
            $podcast,
        ));
    }
}
