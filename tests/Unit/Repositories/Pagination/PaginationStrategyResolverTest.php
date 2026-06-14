<?php

namespace Tests\Unit\Repositories\Pagination;

use App\Repositories\Pagination\CursorStrategy;
use App\Repositories\Pagination\OffsetStrategy;
use App\Repositories\Pagination\PaginationStrategyResolver;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PaginationStrategyResolverTest extends TestCase
{
    #[Test]
    public function resolvesCursorStrategyWhenCursorPresent(): void
    {
        $strategy = PaginationStrategyResolver::resolve(self::createRequest(['cursor' => 'eyJpZCI6MX0']));

        self::assertInstanceOf(CursorStrategy::class, $strategy);
    }

    #[Test]
    public function resolvesCursorStrategyWhenCursorPresentButEmpty(): void
    {
        $strategy = PaginationStrategyResolver::resolve(self::createRequest(['cursor' => '']));

        self::assertInstanceOf(CursorStrategy::class, $strategy);
    }

    #[Test]
    public function resolvesOffsetStrategyWhenCursorAbsent(): void
    {
        $strategy = PaginationStrategyResolver::resolve(self::createRequest());

        self::assertInstanceOf(OffsetStrategy::class, $strategy);
    }

    /** @param array<string, string> $queryParams */
    private static function createRequest(array $queryParams = []): Request
    {
        return Request::create('/', 'GET', $queryParams);
    }
}
