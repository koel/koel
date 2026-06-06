<?php

namespace Tests\Integration\KoelPlus\Repositories;

use App\Models\Folder;
use App\Repositories\FolderRepository;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\PlusTestCase;

use function Tests\create_user;

class FolderRepositoryTest extends PlusTestCase
{
    private FolderRepository $repository;

    public function setUp(): void
    {
        parent::setUp();

        $this->repository = app(FolderRepository::class);
    }

    #[Test]
    public function getByPaths(): void
    {
        $user = create_user(['id' => 99]);
        $foo = Folder::factory()->createOne(['path' => 'foo']);
        $bar = Folder::factory()->createOne(['path' => 'foo/bar']);

        // This folder is not browsable by the user and should not be returned
        Folder::factory()->createOne(['path' => '__KOEL_UPLOADS_$1__']);

        $results = $this->repository->getByPaths(['foo', 'foo/bar', '__KOEL_UPLOADS_$1__'], $user);

        self::assertEqualsCanonicalizing($results->pluck('id')->toArray(), [$foo->id, $bar->id]);
    }

    #[Test]
    public function getAncestorsReturnsChainOrderedRootFirst(): void
    {
        $music = Folder::factory()->createOne(['path' => 'Music']);
        $rock = Folder::factory()->for($music, 'parent')->createOne(['path' => 'Music/Rock']);
        $floyd = Folder::factory()->for($rock, 'parent')->createOne(['path' => 'Music/Rock/PinkFloyd']);
        $wall = Folder::factory()->for($floyd, 'parent')->createOne(['path' => 'Music/Rock/PinkFloyd/TheWall']);

        $ancestors = $this->repository->getAncestors($wall);

        self::assertSame([$music->id, $rock->id, $floyd->id], $ancestors->pluck('id')->all());
    }

    #[Test]
    public function getAncestorsRunsExactlyOneQuery(): void
    {
        $music = Folder::factory()->createOne(['path' => 'Music']);
        $rock = Folder::factory()->for($music, 'parent')->createOne(['path' => 'Music/Rock']);
        $floyd = Folder::factory()->for($rock, 'parent')->createOne(['path' => 'Music/Rock/PinkFloyd']);
        $wall = Folder::factory()->for($floyd, 'parent')->createOne(['path' => 'Music/Rock/PinkFloyd/TheWall']);

        DB::flushQueryLog();
        DB::enableQueryLog();

        $this->repository->getAncestors($wall);

        self::assertCount(1, DB::getQueryLog());
    }

    #[Test]
    public function getAncestorsReturnsEmptyForRootLevelFolder(): void
    {
        $root = Folder::factory()->createOne(['path' => 'Music']);

        self::assertTrue($this->repository->getAncestors($root)->isEmpty());
    }
}
