<?php

namespace Tests\Unit\Services;

use App\Enums\SongStorageType;
use App\Exceptions\SongUploadFailedException;
use App\Models\Song;
use App\Services\Scanners\FileScanner;
use App\Services\SongService;
use App\Services\SongStorages\Contracts\MustDeleteTemporaryLocalFileAfterUpload;
use App\Services\SongStorages\SongStorage;
use App\Services\UploadService;
use App\Values\Scanning\ScanConfiguration;
use App\Values\Scanning\ScanInformation;
use App\Values\UploadReference;
use Exception;
use Illuminate\Support\Facades\File;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_user;

class UploadServiceTest extends TestCase
{
    private SongService|MockInterface $songService;
    private FileScanner|MockInterface $scanner;

    public function setUp(): void
    {
        parent::setUp();

        $this->songService = Mockery::mock(SongService::class);
        $this->scanner = Mockery::mock(FileScanner::class);
    }

    #[Test]
    public function handleUpload(): void
    {
        $storage = Mockery::mock(SongStorage::class, ['getStorageType' => SongStorageType::LOCAL]);
        $scanInfo = ScanInformation::make(path: '/var/media/koel/some-file.mp3');
        $file = '/var/media/koel/some-file.mp3';
        $song = Song::factory()->create();
        $uploader = create_user();

        $reference = UploadReference::make(
            location: '/var/media/koel/some-file.mp3',
            localPath: '/var/media/koel/some-file.mp3',
        );

        $storage->expects('storeUploadedFile')
            ->with($file, $uploader)
            ->andReturn($reference);

        $this->scanner->expects('scan')
            ->with('/var/media/koel/some-file.mp3')
            ->andReturn($scanInfo);

        $this->songService->expects('createOrUpdateSongFromScan')
            ->with($scanInfo, Mockery::on(static function (ScanConfiguration $config) use ($uploader): bool {
                return $config->owner->is($uploader)
                    && $config->makePublic === $uploader->preferences->makeUploadsPublic
                    && $config->extractFolderStructure;
            }))
            ->andReturn($song);

        $result = (new UploadService($this->songService, $storage, $this->scanner))->handleUpload($file, $uploader);

        self::assertSame($song, $result);
    }

    #[Test]
    public function uploadUpdatesSongPathAndStorage(): void
    {
        $storage = Mockery::mock(SongStorage::class, ['getStorageType' => SongStorageType::S3]);
        $file = '/tmp/some-tmp-file.mp3';
        $scanInfo = ScanInformation::make(path: '/tmp/some-tmp-file.mp3');
        $uploader = create_user();

        /** @var Song $song */
        $song = Song::factory()->create([
            'path' => '/tmp/some-tmp-file.mp3', // Initially set to the local path
            'storage' => SongStorageType::LOCAL, // Initially set to local storage
        ]);

        $reference = UploadReference::make(
            location: 's3://koel/some-file.mp3',
            localPath: '/tmp/some-tmp-file.mp3',
        );

        $storage->expects('storeUploadedFile')
            ->with($file, $uploader)
            ->andReturn($reference);

        $this->scanner->expects('scan')
            ->with('/tmp/some-tmp-file.mp3')
            ->andReturn($scanInfo);

        $this->songService->expects('createOrUpdateSongFromScan')
            ->with($scanInfo, Mockery::on(static function (ScanConfiguration $config) use ($uploader): bool {
                return $config->owner->is($uploader)
                    && $config->makePublic === $uploader->preferences->makeUploadsPublic
                    && !$config->extractFolderStructure;
            }))
            ->andReturn($song);

        $result = (new UploadService($this->songService, $storage, $this->scanner))->handleUpload($file, $uploader);

        self::assertSame($song, $result);
        self::assertSame('s3://koel/some-file.mp3', $song->path);
        self::assertSame(SongStorageType::S3, $song->storage);
    }

    #[Test]
    public function deletesTempLocalPathAfterUploading(): void
    {
        $scanInfo = ScanInformation::make(path: '/tmp/some-tmp-file.mp3');

        /** @var SongStorage|MustDeleteTemporaryLocalFileAfterUpload|MockInterface $storage */
        $storage = Mockery::mock(
            SongStorage::class . ',' . MustDeleteTemporaryLocalFileAfterUpload::class,
            ['getStorageType' => SongStorageType::S3]
        );

        /** @var Song $song */
        $song = Song::factory()->create();

        $file = '/tmp/some-tmp-file.mp3';
        $uploader = create_user();

        $reference = UploadReference::make(
            location: 's3://koel/some-file.mp3',
            localPath: '/tmp/some-tmp-file.mp3',
        );

        $storage->expects('storeUploadedFile')->andReturn($reference);
        $this->scanner->expects('scan')->andReturn($scanInfo);
        $this->songService->expects('createOrUpdateSongFromScan')->andReturn($song);

        File::expects('delete')->with('/tmp/some-tmp-file.mp3');

        (new UploadService($this->songService, $storage, $this->scanner))->handleUpload($file, $uploader);
    }

    #[Test]
    public function undoUploadOnScanningProcessException(): void
    {
        $scanInfo = ScanInformation::make(path: '/var/media/koel/some-file.mp3');
        $storage = Mockery::mock(SongStorage::class, ['getStorageType' => SongStorageType::LOCAL]);
        $file = '/tmp/some-file.mp3';
        $uploader = create_user();

        $reference = UploadReference::make(
            location: '/var/media/koel/some-file.mp3',
            localPath: '/var/media/koel/some-file.mp3',
        );

        $storage->expects('storeUploadedFile')->with($file, $uploader)->andReturn($reference);
        $this->scanner->expects('scan')->andReturn($scanInfo);
        $storage->expects('undoUpload')->with($reference);

        $this->songService
            ->expects('createOrUpdateSongFromScan')
            ->andThrow(new Exception('File supports racism'));

        $this->expectException(SongUploadFailedException::class);
        $this->expectExceptionMessage('File supports racism');

        (new UploadService($this->songService, $storage, $this->scanner))->handleUpload($file, $uploader);
    }

    #[Test]
    public function undoUploadOnScanningError(): void
    {
        $storage = Mockery::mock(SongStorage::class, ['getStorageType' => SongStorageType::LOCAL]);
        $file = '/tmp/some-file.mp3';
        $uploader = create_user();

        $reference = UploadReference::make(
            location: '/var/media/koel/some-file.mp3',
            localPath: '/var/media/koel/some-file.mp3',
        );

        $storage->expects('storeUploadedFile')->with($file, $uploader)->andReturn($reference);
        $this->scanner->expects('scan')->andThrow(new Exception('File supports racism'));
        $storage->expects('undoUpload')->with($reference);

        $this->expectException(SongUploadFailedException::class);
        $this->expectExceptionMessage('File supports racism');

        (new UploadService($this->songService, $storage, $this->scanner))->handleUpload($file, $uploader);
    }
}
