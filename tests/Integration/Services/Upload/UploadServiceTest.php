<?php

namespace Tests\Integration\Services\Upload;

use App\Models\Song;
use App\Services\Upload\UploadService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_user;

class UploadServiceTest extends TestCase
{
    #[Test]
    public function makeUploadResponseCarriesTheSongItsAlbumAndTheUploadKey(): void
    {
        $uploader = create_user();
        $song = Song::factory()->for($uploader, 'owner')->createOne();

        $response = app(UploadService::class)->makeUploadResponse($song, $uploader, 'upload-key')->toArray();

        self::assertTrue($song->is($response['song']->resource));
        self::assertTrue($song->album->is($response['album']->resource));
        self::assertSame('upload-key', $response['upload_key']);
    }
}
