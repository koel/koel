<?php

namespace Tests\Unit\Repositories;

use App\Models\DuplicateUpload;
use App\Models\Song;
use App\Repositories\DuplicateUploadRepository;
use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_user;

class DuplicateUploadRepositoryTest extends TestCase
{
    #[Test]
    public function createSavesDuplicateUploadWithCorrectFields(): void
    {
        $user = create_user();
        $song = Song::factory()->createOne();
        $filePath = '/tmp/duplicate_uploads/some-file.mp3';

        app(DuplicateUploadRepository::class)->create($user, $filePath, $song);

        $this->assertDatabaseHas('duplicate_uploads', [
            'user_id' => $user->id,
            'file_path' => $filePath,
            'existing_song_id' => $song->id,
        ]);
    }

    #[Test]
    public function findForUserReturnsOnlyThatUsersDuplicateUploads(): void
    {
        $userA = create_user();
        $userB = create_user();

        $recordA = DuplicateUpload::factory()->createOne(['user_id' => $userA->id]);
        DuplicateUpload::factory()->createOne(['user_id' => $userB->id]);

        $results = app(DuplicateUploadRepository::class)->findForUser($userA);

        self::assertCount(1, $results);
        self::assertTrue($recordA->is($results->first()));
    }

    #[Test]
    public function deleteExpiredRemovesRecordsOlderThanTtl(): void
    {
        $expired = DuplicateUpload::factory()->createOne(['created_at' => now()->subHours(25)]);
        $fresh = DuplicateUpload::factory()->createOne(['created_at' => now()->subHour()]);

        File::shouldReceive('delete')->once()->with($expired->file_path);

        app(DuplicateUploadRepository::class)->deleteExpired(24);

        $this->assertDatabaseMissing('duplicate_uploads', ['id' => $expired->id]);
        $this->assertDatabaseHas('duplicate_uploads', ['id' => $fresh->id]);
    }
}
