<?php

namespace Tests\Feature;

use App\Http\Resources\ListeningStatisticsResource;
use App\Models\Play;
use App\Models\Song;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_user;

class ListeningStatisticsTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-10-09 15:30:00');
    }

    #[Test]
    public function countOnlyPlaysInThePeriod(): void
    {
        $user = create_user();
        $song = Song::factory()->createOne(['length' => 200]);

        Play::factory()
            ->for($user)
            ->for($song)
            ->state(['played_at' => now()->subDays(2)])
            ->createMany(2);
        Play::factory()
            ->for($user)
            ->for($song)
            ->createOne(['played_at' => now()->subDays(20)]);

        $this
            ->getAs('api/me/listening-statistics?period=week', $user)
            ->assertOk()
            ->assertJsonStructure(ListeningStatisticsResource::JSON_STRUCTURE)
            ->assertJsonPath('summary.plays', 2)
            ->assertJsonPath('summary.listening_time', 400)
            ->assertJsonPath('summary.song_count', 1);

        $this->getAs('api/me/listening-statistics?period=month', $user)->assertJsonPath('summary.plays', 3);
    }

    #[Test]
    public function leaveOutOtherUsersPlays(): void
    {
        $user = create_user();
        Play::factory()->for($user)->createOne();
        Play::factory()->createMany(3);

        $this->getAs('api/me/listening-statistics?period=all', $user)->assertJsonPath('summary.plays', 1);
    }

    #[Test]
    public function rankTheMostPlayedSongsFirst(): void
    {
        $user = create_user();
        $favorite = Song::factory()->createOne();
        $other = Song::factory()->createOne();

        Play::factory()->for($user)->for($other)->createOne();
        Play::factory()->for($user)->for($favorite)->createMany(3);

        $this
            ->getAs('api/me/listening-statistics?period=all', $user)
            ->assertJsonPath('top_songs.0.song.id', $favorite->id)
            ->assertJsonPath('top_songs.0.plays', 3)
            ->assertJsonPath('top_songs.1.song.id', $other->id);
    }

    #[Test]
    public function countPlaysPerUtcHour(): void
    {
        $user = create_user();

        Play::factory()->for($user)->createOne(['played_at' => '2026-10-09 14:05:00']);
        Play::factory()->for($user)->createOne(['played_at' => '2026-10-09 14:55:00']);
        Play::factory()->for($user)->createOne(['played_at' => '2026-10-09 15:10:00']);

        $this->getAs('api/me/listening-statistics?period=week', $user)->assertJsonPath('hourly_plays', [
            ['hour' => '2026-10-09T14:00:00Z', 'plays' => 2],
            ['hour' => '2026-10-09T15:00:00Z', 'plays' => 1],
        ]);
    }

    #[Test]
    public function rejectAnUnknownPeriod(): void
    {
        $this->getAs('api/me/listening-statistics?period=decade')->assertUnprocessable();
    }
}
