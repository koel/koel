<?php

namespace Tests\Feature\Commands;

use App\Models\Podcast;
use App\Services\PodcastService;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\TestCase;

class SyncPodcastsCommandTest extends TestCase
{
    #[Test]
    public function syncPodcasts(): void
    {
        Podcast::factory()->count(2)->create();

        $podcastService = Mockery::mock(PodcastService::class);

        $podcastService->shouldReceive('isPodcastObsolete')->twice()->andReturn(true);

        $podcastService->shouldReceive('refreshPodcast')->twice();

        $this->app->instance(PodcastService::class, $podcastService);

        $this->artisan('koel:podcasts:sync')->assertSuccessful();
    }

    #[Test]
    public function skipNonObsoletePodcasts(): void
    {
        Podcast::factory()->create();

        $podcastService = Mockery::mock(PodcastService::class);

        $podcastService->shouldReceive('isPodcastObsolete')->once()->andReturn(false);

        $podcastService->shouldReceive('refreshPodcast')->never();

        $this->app->instance(PodcastService::class, $podcastService);

        $this->artisan('koel:podcasts:sync')->assertSuccessful();
    }

    #[Test]
    public function handleExceptionsGracefully(): void
    {
        Podcast::factory()->create();

        $podcastService = Mockery::mock(PodcastService::class);

        $podcastService
            ->shouldReceive('isPodcastObsolete')
            ->once()
            ->andThrow(new RuntimeException('Connection failed'));

        $this->app->instance(PodcastService::class, $podcastService);

        $this->artisan('koel:podcasts:sync')->assertSuccessful();
    }
}
