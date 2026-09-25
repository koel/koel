<?php

namespace Tests\Unit\Services;

use App\Services\BuildIdentifier;
use Illuminate\Foundation\Vite;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BuildIdentifierTest extends TestCase
{
    private static function makeIdentifier(bool $hot = false): BuildIdentifier
    {
        $vite = Mockery::mock(Vite::class);
        $vite->allows('isRunningHot')->andReturn($hot);

        return new BuildIdentifier($vite);
    }

    #[Test]
    public function identifyTheBuildByWhenItsManifestWasWritten(): void
    {
        $manifest = tempnam(sys_get_temp_dir(), 'manifest');
        touch($manifest, 1_790_000_000);

        self::assertSame('1790000000', self::makeIdentifier()->getId($manifest));

        unlink($manifest);
    }

    #[Test]
    public function reportNoBuildWithoutAManifest(): void
    {
        self::assertNull(self::makeIdentifier()->getId('/no/such/manifest.json'));
    }

    #[Test]
    public function reportNoBuildWhileTheDevServerIsRunning(): void
    {
        $manifest = tempnam(sys_get_temp_dir(), 'manifest');

        self::assertNull(self::makeIdentifier(hot: true)->getId($manifest));

        unlink($manifest);
    }
}
