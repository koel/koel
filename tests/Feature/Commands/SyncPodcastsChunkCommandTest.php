<?php

namespace Tests\Feature\Commands;

use App\Models\Podcast;
use App\Services\PodcastService;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SyncPodcastsChunkCommandTest extends TestCase
{
    #[Test]
    public function syncChunk(): void
    {
        $podcasts = Podcast::factory()->createMany(2);

        $podcastService = Mockery::mock(PodcastService::class);

        $podcastService->shouldReceive('isPodcastObsolete')->twice()->andReturn(true);
        $podcastService->shouldReceive('refreshPodcast')->twice();

        $this->app->instance(PodcastService::class, $podcastService);

        $this->artisan('koel:podcasts:sync-chunk', ['ids' => $podcasts->pluck('id')->all()])->assertSuccessful();
    }

    #[Test]
    public function skipFreshPodcastsInChunk(): void
    {
        $podcast = Podcast::factory()->createOne();

        $podcastService = Mockery::mock(PodcastService::class);

        $podcastService->shouldReceive('isPodcastObsolete')->once()->andReturn(false);
        $podcastService->shouldReceive('refreshPodcast')->never();

        $this->app->instance(PodcastService::class, $podcastService);

        $this->artisan('koel:podcasts:sync-chunk', ['ids' => [$podcast->id]])->assertSuccessful();
    }
}
