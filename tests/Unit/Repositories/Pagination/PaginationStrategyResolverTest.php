<?php

namespace Tests\Unit\Repositories\Pagination;

use App\Http\Requests\Request;
use App\Repositories\Pagination\CursorStrategy;
use App\Repositories\Pagination\OffsetStrategy;
use App\Repositories\Pagination\PaginationStrategyResolver;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PaginationStrategyResolverTest extends TestCase
{
    #[Test]
    public function resolvesCursorStrategyWhenCursorPresent(): void
    {
        $strategy = PaginationStrategyResolver::resolve($this->requestWithQuery(['cursor' => 'eyJpZCI6MX0']));

        self::assertInstanceOf(CursorStrategy::class, $strategy);
    }

    #[Test]
    public function resolvesCursorStrategyWhenCursorPresentButEmpty(): void
    {
        $strategy = PaginationStrategyResolver::resolve($this->requestWithQuery(['cursor' => '']));

        self::assertInstanceOf(CursorStrategy::class, $strategy);
    }

    #[Test]
    public function resolvesOffsetStrategyWhenCursorAbsent(): void
    {
        $strategy = PaginationStrategyResolver::resolve($this->requestWithQuery([]));

        self::assertInstanceOf(OffsetStrategy::class, $strategy);
    }

    /** @param array<string, string> $query */
    private function requestWithQuery(array $query): Request
    {
        return new class($query, [], [], [], [], ['REQUEST_METHOD' => 'GET']) extends Request {};
    }
}
