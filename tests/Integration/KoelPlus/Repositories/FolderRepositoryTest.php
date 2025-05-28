<?php

namespace Tests\Integration\KoelPlus\Repositories;

use App\Models\Folder;
use App\Repositories\FolderRepository;
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

        /** @var Folder $foo */
        $foo = Folder::factory()->create(['path' => 'foo']);

        /** @var Folder $bar */
        $bar = Folder::factory()->create(['path' => 'foo/bar']);

        // This folder is not browsable by the user and should not be returned
        Folder::factory()->create(['path' => '__KOEL_UPLOADS_$1__']);

        $results = $this->repository->getByPaths(['foo', 'foo/bar', '__KOEL_UPLOADS_$1__'], $user);

        self::assertEqualsCanonicalizing($results->pluck('id')->toArray(), [$foo->id, $bar->id]);
    }
}
