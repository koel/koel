<?php

namespace Tests\Unit\Jobs;

use App\Jobs\ScrobbleJob;
use App\Models\Song;
use App\Services\ScrobbleService;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_user;

class ScrobbleJobTest extends TestCase
{
    #[Test]
    public function handle(): void
    {
        $user = create_user();
        $song = Song::factory()->make();
        $job = new ScrobbleJob($user, $song, 100);
        $scrobbleService = Mockery::mock(ScrobbleService::class);

        $scrobbleService->expects('scrobble')->with($song, $user, 100);

        $job->handle($scrobbleService);
    }
}
