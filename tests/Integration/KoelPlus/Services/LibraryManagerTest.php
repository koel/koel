<?php

namespace Tests\Integration\KoelPlus\Services;

use App\Models\Album;
use App\Models\Artist;
use App\Models\Song;
use App\Services\LibraryManager;
use PHPUnit\Framework\Attributes\Test;
use Tests\PlusTestCase;

use function Tests\create_admin;

class LibraryManagerTest extends PlusTestCase
{
    #[Test]
    public function mergeHandedOverSongsIntoTheHeirsSameNamedArtistAndAlbum(): void
    {
        $leavingAdmin = create_admin();
        $heir = create_admin();
        $artist = Artist::factory()->for($leavingAdmin)->createOne(['name' => 'Portishead']);
        $album = Album::factory()->for($leavingAdmin)->for($artist)->createOne(['name' => 'Dummy']);
        $heirsArtist = Artist::factory()->for($heir)->createOne(['name' => 'Portishead']);
        $heirsAlbum = Album::factory()->for($heir)->for($heirsArtist)->createOne(['name' => 'Dummy']);
        $song = Song::factory()
            ->for($leavingAdmin, 'owner')
            ->for($artist)
            ->for($album)
            ->createOne(['path' => '/music/Roads.mp3']);

        app(LibraryManager::class)->handOverScannedLibraryOfUser($leavingAdmin);

        $song->refresh();

        self::assertTrue($song->artist->is($heirsArtist));
        self::assertTrue($song->album->is($heirsAlbum));
    }
}
