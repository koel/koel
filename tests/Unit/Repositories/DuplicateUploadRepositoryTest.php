<?php

namespace Tests\Unit\Repositories;

use App\Enums\SongStorageType;
use App\Models\DuplicateUpload;
use App\Models\Song;
use App\Repositories\DuplicateUploadRepository;
use App\Services\SongStorages\SongStorage;
use App\Values\Scanning\ScanConfiguration;
use App\Values\UploadReference;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_user;

class DuplicateUploadRepositoryTest extends TestCase
{
    private SongStorage|MockInterface $storage;
    private DuplicateUploadRepository $repository;

    public function setUp(): void
    {
        parent::setUp();

        $this->storage = Mockery::mock(SongStorage::class);
        $this->repository = new DuplicateUploadRepository($this->storage);
    }

    #[Test]
    public function createSavesDuplicateUploadWithCorrectFields(): void
    {
        $user = create_user();
        $song = Song::factory()->createOne();
        $config = ScanConfiguration::make(owner: $user, makePublic: true, extractFolderStructure: false);
        $reference = UploadReference::make('/var/media/koel/some-file.mp3', '/tmp/some-file.mp3');

        $this->storage->expects('getStorageType')->andReturn(SongStorageType::LOCAL);

        $this->repository->create($config, $reference, $song);

        $this->assertDatabaseHas('duplicate_uploads', [
            'user_id' => $user->id,
            'existing_song_id' => $song->id,
            'location' => '/var/media/koel/some-file.mp3',
            'storage' => SongStorageType::LOCAL->value,
            'make_public' => true,
            'extract_folder_structure' => false,
        ]);
    }

    #[Test]
    public function findForUserReturnsOnlyThatUsersDuplicateUploads(): void
    {
        $userA = create_user();
        $userB = create_user();

        $recordA = DuplicateUpload::factory()->createOne(['user_id' => $userA->id]);
        DuplicateUpload::factory()->createOne(['user_id' => $userB->id]);

        $results = $this->repository->findForUser($userA);

        self::assertCount(1, $results);
        self::assertTrue($recordA->is($results->first()));
    }
}
