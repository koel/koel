<?php

namespace Tests\Unit\Models;

use App\Models\DuplicateUpload;
use App\Models\Song;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DuplicateUploadTest extends TestCase
{
    #[Test]
    public function belongsToExistingSong(): void
    {
        $duplicateUpload = DuplicateUpload::factory()->createOne();

        self::assertInstanceOf(Song::class, $duplicateUpload->existingSong);
    }
}
