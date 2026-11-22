<?php

namespace Tests\Feature\KoelPlus;

use App\Http\Resources\SongResource;
use App\Models\Song;
use PHPUnit\Framework\Attributes\Test;
use Tests\PlusTestCase;

use function Tests\create_user;

class HiddenSongTest extends PlusTestCase
{
    #[Test]
    public function hideSongs(): void
    {
        $user = create_user();
        $songs = Song::factory()->for($user, 'owner')->createMany(2);

        $this->postAs('api/songs/hide', ['songs' => $songs->modelKeys()], $user)->assertNoContent();

        self::assertEqualsCanonicalizing($songs->modelKeys(), $user->hiddenSongs->modelKeys());
    }

    #[Test]
    public function unhideSongs(): void
    {
        $user = create_user();
        $songs = Song::factory()->for($user, 'owner')->createMany(2);
        $user->hiddenSongs()->attach($songs->modelKeys());

        $this->postAs('api/songs/unhide', ['songs' => [$songs[0]->id]], $user)->assertNoContent();

        self::assertTrue($user->hiddenSongs->sole()->is($songs[1]));
    }

    #[Test]
    public function cannotHideInaccessibleSongs(): void
    {
        $user = create_user();
        $song = Song::factory()->for(create_user(), 'owner')->private()->createOne();

        $this->postAs('api/songs/hide', ['songs' => [$song->id]], $user)->assertForbidden();

        self::assertTrue($user->hiddenSongs->isEmpty());
    }

    #[Test]
    public function listHiddenSongs(): void
    {
        $user = create_user();
        $song = Song::factory()->for($user, 'owner')->createOne();
        Song::factory()->for($user, 'owner')->createOne();
        $user->hiddenSongs()->attach($song->id);

        $this
            ->getAs('api/songs/hidden', $user)
            ->assertJsonStructure([0 => SongResource::JSON_STRUCTURE])
            ->assertJsonCount(1)
            ->assertJsonPath('0.id', $song->id)
            ->assertJsonPath('0.hidden', true);
    }
}
