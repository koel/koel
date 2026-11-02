<?php

namespace Tests\Feature\Commands;

use App\Events\NewVersionDeployed;
use App\Services\BuildIdentifier;
use Illuminate\Support\Facades\Event;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AnnounceUpdateCommandTest extends TestCase
{
    private function mockBuild(?string $build): void
    {
        $buildIdentifier = Mockery::mock(BuildIdentifier::class);
        $buildIdentifier->expects('getId')->andReturn($build);
        $this->instance(BuildIdentifier::class, $buildIdentifier);
    }

    #[Test]
    public function broadcastTheLiveBuild(): void
    {
        Event::fake(NewVersionDeployed::class);
        $this->mockBuild('1790000000');

        $this->artisan('koel:announce-update')->assertSuccessful();

        Event::assertDispatched(
            NewVersionDeployed::class,
            static fn (NewVersionDeployed $event): bool => $event->build === '1790000000',
        );
    }

    #[Test]
    public function announceNothingWithoutABuild(): void
    {
        Event::fake(NewVersionDeployed::class);
        $this->mockBuild(null);

        $this->artisan('koel:announce-update')->assertSuccessful();

        Event::assertNotDispatched(NewVersionDeployed::class);
    }
}
