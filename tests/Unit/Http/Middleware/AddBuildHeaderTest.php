<?php

namespace Tests\Unit\Http\Middleware;

use App\Http\Middleware\AddBuildHeader;
use Illuminate\Foundation\Vite;
use Illuminate\Http\Request;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class AddBuildHeaderTest extends TestCase
{
    private static function handleWithBuild(?string $build): Response
    {
        $vite = Mockery::mock(Vite::class);
        $vite->expects('manifestHash')->andReturn($build);

        return (new AddBuildHeader($vite))->handle(new Request(), static fn () => new Response());
    }

    #[Test]
    public function tellTheClientWhichBuildIsLive(): void
    {
        self::assertSame('abc123', self::handleWithBuild('abc123')->headers->get('X-Koel-Build'));
    }

    #[Test]
    public function leaveTheHeaderOutWhenThereIsNoBuild(): void
    {
        self::assertFalse(self::handleWithBuild(null)->headers->has('X-Koel-Build'));
    }
}
