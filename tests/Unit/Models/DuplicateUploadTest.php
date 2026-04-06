<?php

namespace Tests\Unit\Models;

use App\Models\DuplicateUpload;
use App\Models\Song;
use App\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DuplicateUploadTest extends TestCase
{
    #[Test]
    public function belongsToUser(): void
    {
        $duplicateUpload = DuplicateUpload::factory()->createOne();

        self::assertInstanceOf(User::class, $duplicateUpload->user);
    }

    #[Test]
    public function belongsToExistingSong(): void
    {
        $duplicateUpload = DuplicateUpload::factory()->createOne();

        self::assertInstanceOf(Song::class, $duplicateUpload->existingSong);
    }
}
