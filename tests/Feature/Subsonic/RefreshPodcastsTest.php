<?php

namespace Tests\Feature\Subsonic;

use App\Models\Podcast;
use App\Models\User;
use App\Services\Podcast\PodcastService;
use Illuminate\Support\Arr;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\TestCase;

use function Tests\create_user;

class RefreshPodcastsTest extends TestCase
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

        $this->getJson(self::urlFor($user))->assertOk()->assertJsonPath('subsonic-response.status', 'ok');
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
            ->andReturnUsing(static function (Podcast $p) use ($brokenPodcast): Podcast {
                if ($p->is($brokenPodcast)) {
                    throw new RuntimeException('Feed unreachable');
                }

                return $p;
            });

        $this->getJson(self::urlFor($user))->assertOk()->assertJsonPath('subsonic-response.status', 'ok');
    }

    private static function urlFor(User $user): string
    {
        return '/rest/refreshPodcasts.view?'
        . Arr::query([
            'apiKey' => $user->subsonic_api_key,
            'f' => 'json',
        ]);
    }
}
