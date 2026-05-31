<?php

namespace Tests\Feature\Subsonic;

use App\Models\Podcast;
use App\Services\Podcast\PodcastService;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;

use function Tests\create_user;

class RefreshPodcastsTest extends SubsonicTestCase
{
    #[Test]
    public function refreshesSubscribedPodcasts(): void
    {
        $user = create_user();
        $podcastA = Podcast::factory()->createOne();
        $podcastB = Podcast::factory()->createOne();
        $podcastA->subscribers()->attach($user);
        $podcastB->subscribers()->attach($user);

        $service = $this->mock(PodcastService::class);
        $service
            ->expects('refreshPodcast')
            ->twice()
            ->with(Mockery::on(static fn (Podcast $p) => $p->is($podcastA) || $p->is($podcastB)))
            ->andReturnUsing(static fn (Podcast $p) => $p);

        $this->getSubsonic('refreshPodcasts.view', $user)->assertSubsonicOk();
    }

    #[Test]
    public function swallowsRefreshFailuresAndKeepsGoing(): void
    {
        $user = create_user();
        $brokenPodcast = Podcast::factory()->createOne();
        $workingPodcast = Podcast::factory()->createOne();
        $brokenPodcast->subscribers()->attach($user);
        $workingPodcast->subscribers()->attach($user);

        $service = $this->mock(PodcastService::class);
        $service
            ->shouldReceive('refreshPodcast')
            ->andReturnUsing(static function (Podcast $podcast) use ($brokenPodcast): Podcast {
                if ($podcast->is($brokenPodcast)) {
                    throw new RuntimeException('Feed unreachable');
                }

                return $podcast;
            });

        $this->getSubsonic('refreshPodcasts.view', $user)->assertSubsonicOk();
    }
}
