<?php

namespace Tests\Integration\KoelPlus\Repositories;

use App\Models\Song;
use App\Repositories\SongRepository;
use PHPUnit\Framework\Attributes\Test;
use Tests\PlusTestCase;

use function Tests\create_user;

class SongRepositoryTest extends PlusTestCase
{
    #[Test]
    public function searchFindsOwnSongsRankedBehindOtherUsersPrivateOnes(): void
    {
        config(['scout.driver' => 'collection']);

        $user = create_user();
        $ownSong = Song::factory()->for($user, 'owner')->createOne([
            'id' => '00000000-0000-0000-0000-000000000000',
            'title' => 'Love Me Tender',
        ]);

        Song::factory()->for(create_user(), 'owner')->private()->state(['title' => 'Love Hurts'])->createMany(5);

        $results = app(SongRepository::class)->search('love', 5, $user);

        self::assertTrue($results->sole()->is($ownSong));
    }
}
