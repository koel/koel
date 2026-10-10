<?php

namespace Tests\Concerns;

use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;

trait AssertsFlatQueryCount
{
    /**
     * Fail when a request runs more queries once more records exist, the mark of a query per record (N+1).
     *
     * @param Closure(int): mixed $createRecords creates the given number of records the request lists
     * @param Closure(): TestResponse $makeRequest
     */
    protected function assertQueryCountStaysFlat(Closure $createRecords, Closure $makeRequest): void
    {
        $createRecords(2);
        $makeRequest()->assertOk();
        $queriesWithFewRecords = self::countQueries($makeRequest);

        $createRecords(8);
        $queriesWithManyRecords = self::countQueries($makeRequest);

        self::assertSame(
            $queriesWithFewRecords,
            $queriesWithManyRecords,
            sprintf(
                'The query count grew from %d to %d with more records, which points at a query per record.',
                $queriesWithFewRecords,
                $queriesWithManyRecords,
            ),
        );
    }

    /** @param Closure(): TestResponse $makeRequest */
    private static function countQueries(Closure $makeRequest): int
    {
        DB::flushQueryLog();
        DB::enableQueryLog();

        $makeRequest()->assertOk();
        $queryCount = count(DB::getQueryLog());

        DB::disableQueryLog();

        return $queryCount;
    }
}
