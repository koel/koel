<?php

namespace Tests\Unit\Repositories\Pagination;

use App\Builders\SongBuilder;
use App\Models\Album;
use App\Models\Song;
use App\Repositories\Pagination\CursorStrategy;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CursorStrategyTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        Song::factory()->for(Album::factory()->createOne())->createMany([
            ['title' => 'Alpha', 'year' => null],
            ['title' => 'Alpha', 'year' => 2001],
            ['title' => 'Alpha', 'year' => null],
            ['title' => 'Bravo', 'year' => 1999],
            ['title' => 'Bravo', 'year' => 2001],
            ['title' => 'Charlie', 'year' => null],
            ['title' => 'Delta', 'year' => 1999],
            ['title' => 'Delta', 'year' => 2001],
            ['title' => 'Echo', 'year' => null],
            ['title' => '', 'year' => 1999, 'path' => '/music/Zulu.mp3'],
            ['title' => '', 'year' => null, 'path' => '/music/Yankee.mp3'],
        ]);
    }

    /** @return array<string, array{array<int, array{string, string}>}> */
    public static function provideSortOrders(): array
    {
        return [
            'single column ascending' => [[['songs.title', 'asc']]],
            'single column descending' => [[['songs.title', 'desc']]],
            'nullable column ascending' => [[['songs.year', 'asc']]],
            'nullable column descending' => [[['songs.year', 'desc']]],
            'multiple columns in one direction' => [[
                ['songs.year',  'asc'],
                ['songs.title', 'asc'],
            ]],
            'multiple columns in mixed directions' => [[
                ['songs.year',  'desc'],
                ['songs.title', 'asc'],
            ]],
        ];
    }

    /** @param array<int, array{string, string}> $sortOrders */
    #[Test]
    #[DataProvider('provideSortOrders')]
    public function walkingAllPagesReturnsEverySongInSortOrder(array $sortOrders): void
    {
        $makeQuery = static function () use ($sortOrders): SongBuilder {
            $query = Song::query();

            foreach ($sortOrders as [$column, $direction]) {
                $query->orderBy($column, $direction);
            }

            return $query;
        };

        $expectedIds = $makeQuery()->orderBy('songs.id', $sortOrders[0][1])->pluck('songs.id')->all();

        self::assertSame($expectedIds, self::walkAllPages($makeQuery));
    }

    #[Test]
    public function walkingAllPagesSortedByAliasReturnsEverySongInSortOrder(): void
    {
        $makeQuery = static fn (): SongBuilder => Song::query()
            ->addSelect(DB::raw('(COALESCE(songs.year, 0)) as year_or_zero'))
            ->orderBy('year_or_zero', 'desc');

        $expectedIds = $makeQuery()->orderBy('songs.id', 'desc')->pluck('songs.id')->all();

        self::assertSame($expectedIds, self::walkAllPages($makeQuery));
    }

    #[Test]
    public function previousCursorReturnsThePreviousPage(): void
    {
        $makeQuery = static fn (): SongBuilder => Song::query()->orderBy('songs.year');

        $firstPage = (new CursorStrategy(null))->apply($makeQuery(), 'songs.id', 2);
        $secondPage = (new CursorStrategy($firstPage->nextCursor()->encode()))->apply($makeQuery(), 'songs.id', 2);
        $backToFirstPage = (new CursorStrategy($secondPage->previousCursor()->encode()))->apply(
            $makeQuery(),
            'songs.id',
            2,
        );

        self::assertSame(
            collect($firstPage->items())->pluck('id')->all(),
            collect($backToFirstPage->items())->pluck('id')->all(),
        );
    }

    /**
     * @param callable(): SongBuilder $makeQuery
     *
     * @return array<int, string>
     */
    private static function walkAllPages(callable $makeQuery): array
    {
        $ids = [];
        $cursor = null;

        $pagesLeft = 50;

        do {
            $page = (new CursorStrategy($cursor))->apply($makeQuery(), 'songs.id', 2);
            array_push($ids, ...collect($page->items())->pluck('id')->all());
            $cursor = $page->nextCursor()?->encode();
        } while ($cursor && --$pagesLeft);

        return $ids;
    }
}
