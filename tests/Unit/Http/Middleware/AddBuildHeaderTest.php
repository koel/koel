<?php

namespace Tests\Unit\Http\Middleware;

use App\Http\Middleware\AddBuildHeader;
use App\Services\BuildIdentifier;
use Illuminate\Http\Request;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class AddBuildHeaderTest extends TestCase
{
    private static function handleWithBuild(?string $build): Response
    {
        $buildIdentifier = Mockery::mock(BuildIdentifier::class);
        $buildIdentifier->expects('getId')->andReturn($build);

        return (new AddBuildHeader($buildIdentifier))->handle(new Request(), static fn () => new Response());
    }

    #[Test]
    public function tellTheClientWhichBuildIsLive(): void
    {
        self::assertSame('1790000000', self::handleWithBuild('1790000000')->headers->get('X-Koel-Build'));
    }

    #[Test]
    public function leaveTheHeaderOutWhenThereIsNoBuild(): void
    {
        self::assertFalse(self::handleWithBuild(null)->headers->has('X-Koel-Build'));
    }
}
