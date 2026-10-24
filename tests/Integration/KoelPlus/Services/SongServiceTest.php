<?php

namespace Tests\Integration\KoelPlus\Services;

use App\Models\Song;
use App\Services\SongService;
use Illuminate\Database\Eloquent\Collection;
use PHPUnit\Framework\Attributes\Test;
use Tests\PlusTestCase;

use function Tests\create_playlists;
use function Tests\create_user;

class SongServiceTest extends PlusTestCase
{
    #[Test]
    public function returnTheIdsOfSongsMadePrivateAsAList(): void
    {
        $owner = create_user();
        [$sharedSong, $soloSong] = Song::factory()->for($owner, 'owner')->public()->createMany(2)->all();
        $sharedPlaylist = create_playlists(1, owner: $owner)->sole();
        $sharedPlaylist->addPlayables($sharedSong);
        $sharedPlaylist->addCollaborator(create_user());

        $privatizedIds = app(SongService::class)->markSongsAsPrivate(new Collection([$sharedSong, $soloSong]));

        self::assertSame([$soloSong->id], $privatizedIds);
    }
}
