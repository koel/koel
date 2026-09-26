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

    private static function writeBuildId(string $content): string
    {
        $path = tempnam(sys_get_temp_dir(), 'build-id');
        file_put_contents($path, $content);

        return $path;
    }

    #[Test]
    public function readTheIdTheBuildWrote(): void
    {
        $path = self::writeBuildId("61b52f5f215511f5\n");

        self::assertSame('61b52f5f215511f5', self::makeIdentifier()->getId($path));

        unlink($path);
    }

    #[Test]
    public function reportNoBuildWithoutAnId(): void
    {
        self::assertNull(self::makeIdentifier()->getId('/no/such/build-id'));

        $path = self::writeBuildId('');
        self::assertNull(self::makeIdentifier()->getId($path));
        unlink($path);
    }

    #[Test]
    public function reportNoBuildWhileTheDevServerIsRunning(): void
    {
        $path = self::writeBuildId('61b52f5f215511f5');

        self::assertNull(self::makeIdentifier(hot: true)->getId($path));

        unlink($path);
    }
}
