<?php

namespace Tests\Integration\Services\Scanners;

use App\Models\DuplicateUpload;
use App\Services\Scanners\IndividualFileHandler;
use App\Values\Scanning\ScanConfiguration;
use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_admin;
use function Tests\test_path;

class IndividualFileHandlerTest extends TestCase
{
    #[Test]
    public function skipAnUploadThatAwaitsADuplicateDecision(): void
    {
        $uploadDirectory = sys_get_temp_dir() . '/__KOEL_UPLOADS_$1__';
        File::ensureDirectoryExists($uploadDirectory);
        $path = "$uploadDirectory/full.mp3";
        File::copy(test_path('songs/full.mp3'), $path);
        DuplicateUpload::factory()->createOne(['location' => $path]);

        $result = app(IndividualFileHandler::class)->handle($path, ScanConfiguration::make(owner: create_admin()));

        self::assertTrue($result->isSkipped());
        File::deleteDirectory($uploadDirectory);
    }
}
