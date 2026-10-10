<?php

namespace Tests\Feature\KoelPlus;

use App\Models\Interaction;
use App\Models\Playlist;
use App\Models\Song;
use Tests\Feature\QueryCountTest as CommunityQueryCountTest;
use Tests\PlusTestCase;

use function Tests\create_user;

class QueryCountTest extends CommunityQueryCountTest
{
    public function setUp(): void
    {
        parent::setUp();

        PlusTestCase::enablePlusLicense();

        $this->user->preferences->includePublicMedia = true;
        $this->user->save();
    }

    protected function createPlayedSongs(int $count): void
    {
        Song::factory()
            ->public()
            ->createMany($count)
            ->each(fn (Song $song) => Interaction::factory()->for($this->user)->for($song)->createOne());
    }

    protected function createPlaylists(int $count): void
    {
        Playlist::factory()
            ->createMany($count)
            ->each(function (Playlist $playlist): void {
                $playlist->users()->sync([create_user()->id => ['role' => 'owner']]);
                $playlist->users()->attach($this->user, ['role' => 'collaborator']);
            });
    }
}
