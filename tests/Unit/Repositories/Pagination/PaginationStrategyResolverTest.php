<?php

namespace Tests\Unit\Repositories\Pagination;

use App\Repositories\Pagination\CursorStrategy;
use App\Repositories\Pagination\OffsetStrategy;
use App\Repositories\Pagination\PaginationStrategyResolver;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class PaginationStrategyResolverTest extends TestCase
{
    #[Test]
    #[TestWith(['eyJpZCI6MX0'])]
    #[TestWith([''])]
    public function resolvesCursorStrategyWhenCursorPresent(string $cursor): void
    {
        $strategy = PaginationStrategyResolver::resolve(self::createRequest(['cursor' => $cursor]));

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
