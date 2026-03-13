<?php

namespace Tests\Feature;

use App\Http\Resources\SongResource;
use App\Models\Favorite;
use App\Models\Song;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_user;

class FetchFavoriteSongsTest extends TestCase
{
    #[Test]
    public function index(): void
    {
        $user = create_user();
        $songs = Song::factory()->for($user, 'owner')->createMany(2);

        $songs->each(static function (Song $song) use ($user): void {
            Favorite::factory()->for($user)->createOne([
                'favoriteable_id' => $song->id,
            ]);
        });

        $this->getAs('api/songs/favorite', $user)->assertJsonStructure([0 => SongResource::JSON_STRUCTURE]);
    }
}
