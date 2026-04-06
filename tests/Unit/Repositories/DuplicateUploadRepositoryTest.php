<?php

namespace Tests\Unit\Repositories;

use App\Models\DuplicateUpload;
use App\Repositories\DuplicateUploadRepository;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_user;

class DuplicateUploadRepositoryTest extends TestCase
{
    private DuplicateUploadRepository $repository;

    public function setUp(): void
    {
        parent::setUp();

        $this->repository = app(DuplicateUploadRepository::class);
    }

    #[Test]
    public function getAllForUserReturnsOnlyThatUsersDuplicateUploads(): void
    {
        $userA = create_user();
        $userB = create_user();

        $recordA = DuplicateUpload::factory()->createOne(['user_id' => $userA->id]);
        DuplicateUpload::factory()->createOne(['user_id' => $userB->id]);

        $results = $this->repository->getAllForUser($userA);

        self::assertCount(1, $results);
        self::assertTrue($recordA->is($results->first()));
    }
}
