<?php

namespace Tests\Unit\Repositories\Pagination;

use App\Models\Song;
use App\Repositories\Pagination\OffsetStrategy;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class OffsetStrategyTest extends TestCase
{
    #[Test]
    public function breakTiesByIdSoPagesStayStable(): void
    {
        $query = Song::query()->orderBy('songs.title');

        (new OffsetStrategy())->apply($query, 'songs.id', 10);

        self::assertSame(
            [['column' => 'songs.title', 'direction' => 'asc'], ['column' => 'songs.id', 'direction' => 'asc']],
            $query->getQuery()->orders,
        );
    }
}
