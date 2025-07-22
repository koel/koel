<?php

namespace Tests\Feature\KoelPlus;

use App\Models\Album;
use App\Models\Artist;
use App\Models\Song;
use PHPUnit\Framework\Attributes\Test;
use Tests\PlusTestCase;

class FavoriteTest extends PlusTestCase
{
    #[Test]
    public function toggleIsProhibitedIfSongIsNotAccessible(): void
    {
        /** @var Song $song */
        $song = Song::factory()->private()->create();

        $this->postAs('api/favorites/toggle', [
            'type' => 'playable',
            'id' => $song->id,
        ])
            ->assertForbidden();
    }

    #[Test]
    public function toggleIsProhibitedIfAlbumIsNotAccessible(): void
    {
        /** @var Album $album */
        $album = Album::factory()->create();

        $this->postAs('api/favorites/toggle', [
            'type' => 'album',
            'id' => $album->id,
        ])
            ->assertForbidden();
    }

    #[Test]
    public function toggleIsProhibitedIfArtistIsNotAccessible(): void
    {
        /** @var Artist $artist */
        $artist = Artist::factory()->create();

        $this->postAs('api/favorites/toggle', [
            'type' => 'artist',
            'id' => $artist->id,
        ])
            ->assertForbidden();
    }

    #[Test]
    public function batchFavoriteIsProhibitedIfAnySongIsNotAccessible(): void
    {
        $songs = Song::factory()->count(2)->create();
        $songs->first()->update(['is_public' => false]);

        $this->postAs('api/favorites', [
            'type' => 'playable',
            'ids' => $songs->pluck('id')->toArray(),
        ])
            ->assertForbidden();
    }

    #[Test]
    public function batchUndoFavoriteIsProhibitedIfAnySongIsNotAccessible(): void
    {
        $songs = Song::factory()->count(2)->create();
        $songs->first()->update(['is_public' => false]);

        $this->deleteAs('api/favorites', [
            'type' => 'playable',
            'ids' => $songs->pluck('id')->toArray(),
        ])
            ->assertForbidden();
    }
}
