<?php

namespace Tests\Feature;

use App\Http\Resources\LibraryStatisticResource;
use App\Models\Organization;
use App\Models\Podcast;
use App\Models\RadioStation;
use App\Models\Song;
use App\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_user;

class LibraryStatisticsTest extends TestCase
{
    #[Test]
    public function countWhatTheUserCanPlay(): void
    {
        $user = create_user();
        $user->organization->claimOwnership(create_user());

        Song::factory()->createMany(2);

        $podcast = Podcast::factory()->hasAttached($user, relationship: 'subscribers')->createOne();
        Song::factory()->asEpisode()->state(['podcast_id' => $podcast->id])->createMany(3);
        Song::factory()->asEpisode()->createOne();

        RadioStation::factory()->for($user)->createOne();
        RadioStation::factory()->createOne();

        $statistics = $this
            ->getAs('api/me/library-statistics', $user)
            ->assertOk()
            ->assertJsonStructure(['*' => LibraryStatisticResource::JSON_STRUCTURE])
            ->collect()
            ->pluck('value', 'key')
            ->all();

        self::assertSame(
            ['songs' => 2, 'albums' => 2, 'artists' => 2, 'podcasts' => 1, 'episodes' => 3, 'radio_stations' => 1],
            $statistics,
        );
    }

    #[Test]
    public function showTheOwnerTheStorageUsedBySongFiles(): void
    {
        $owner = create_user();
        $owner->organization->claimOwnership($owner);

        Song::factory()->createOne(['file_size' => 1_000]);
        Song::factory()->createOne(['file_size' => 500]);
        Song::factory()->createOne(['file_size' => null]);
        Song::factory()->asEpisode()->createOne(['file_size' => 5_000]);
        Song::factory()->for(User::factory()->for(Organization::factory()), 'owner')->createOne(['file_size' => 7_000]);

        $this
            ->getAs('api/me/library-statistics', $owner)
            ->assertOk()
            ->assertJsonFragment(['key' => 'storage', 'value' => 1_500, 'unit' => 'bytes']);
    }
}
