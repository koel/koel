<?php

namespace Tests\Feature\KoelPlus;

use App\Models\Genre;
use App\Models\Song;
use PHPUnit\Framework\Attributes\Test;
use Tests\PlusTestCase;

use function Tests\create_user;

class GenreTest extends PlusTestCase
{
    #[Test]
    public function genreListCountsOnlySongsTheUserCanSee(): void
    {
        $viewer = create_user();
        $viewer->preferences->includePublicMedia = true;
        $viewer->save();

        $owner = create_user();
        $genre = Genre::factory()->createOne(['name' => 'Rock']);

        Song::factory()->for($viewer, 'owner')->hasAttached($genre)->createOne(['length' => 10]);
        Song::factory()->for($owner, 'owner')->public()->hasAttached($genre)->createOne(['length' => 20]);
        Song::factory()->for($owner, 'owner')->private()->hasAttached($genre)->createOne(['length' => 40]);

        $this->getAs('api/genres', $viewer)->assertJsonFragment(['name' => 'Rock', 'song_count' => 2, 'length' => 30]);
    }

    #[Test]
    public function genreListLeavesOutPublicSongsOfOthersWhenPublicMediaIsExcluded(): void
    {
        $viewer = create_user();
        $viewer->preferences->includePublicMedia = false;
        $viewer->save();

        $genre = Genre::factory()->createOne(['name' => 'Rock']);

        Song::factory()->for($viewer, 'owner')->hasAttached($genre)->createOne(['length' => 10]);
        Song::factory()->for(create_user(), 'owner')->public()->hasAttached($genre)->createOne(['length' => 20]);

        $this->getAs('api/genres', $viewer)->assertJsonFragment(['name' => 'Rock', 'song_count' => 1, 'length' => 10]);
    }
}
