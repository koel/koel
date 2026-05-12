<?php

namespace Tests\Unit\Services\Upload;

use App\Enums\SongStorageType;
use App\Exceptions\DuplicateSongUploadException;
use App\Exceptions\SongUploadFailedException;
use App\Models\DuplicateUpload;
use App\Models\Song;
use App\Services\Scanners\FileScanner;
use App\Services\SongService;
use App\Services\SongStorages\Contracts\MustDeleteTemporaryLocalFileAfterUpload;
use App\Services\SongStorages\SongStorage;
use App\Services\Upload\DuplicateUploadService;
use App\Services\Upload\UploadService;
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
    private DuplicateUploadService|MockInterface $duplicateUploadService;

    public function setUp(): void
    {
        parent::setUp();

        $this->songService = Mockery::mock(SongService::class);
        $this->scanner = Mockery::mock(FileScanner::class);
        $this->duplicateUploadService = Mockery::mock(DuplicateUploadService::class);
    }

    private function makeService(SongStorage $storage): UploadService
    {
        return new UploadService($this->songService, $storage, $this->scanner, $this->duplicateUploadService);
    }

    #[Test]
    public function handleUpload(): void
    {
        $storage = Mockery::mock(SongStorage::class, ['getStorageType' => SongStorageType::LOCAL]);
        $scanInfo = ScanInformation::make(path: '/var/media/koel/some-file.mp3');
        $file = '/var/media/koel/some-file.mp3';
        $song = Song::factory()->createOne();
        $uploader = create_user();

        $reference = UploadReference::make(
            location: '/var/media/koel/some-file.mp3',
            localPath: '/var/media/koel/some-file.mp3',
        );

        $storage->expects('storeUploadedFile')->with($file, $uploader)->andReturn($reference);
        $this->duplicateUploadService->expects('detectDuplicate');

        $this->scanner
            ->expects('scan')
            ->with('/var/media/koel/some-file.mp3')
            ->andReturn($scanInfo);

        $this->songService
            ->expects('createOrUpdateSongFromScan')
            ->with($scanInfo, Mockery::type(ScanConfiguration::class))
            ->andReturn($song);

        $result = $this->makeService($storage)->handleUpload($file, $uploader);

        self::assertTrue($song->is($result));
    }

    #[Test]
    public function uploadUpdatesSongPathAndStorage(): void
    {
        $storage = Mockery::mock(SongStorage::class, ['getStorageType' => SongStorageType::S3]);
        $file = '/tmp/some-tmp-file.mp3';
        $scanInfo = ScanInformation::make(path: '/tmp/some-tmp-file.mp3');
        $uploader = create_user();
        $song = Song::factory()->createOne([
            'path' => '/tmp/some-tmp-file.mp3',
            'storage' => SongStorageType::LOCAL,
        ]);

        $reference = UploadReference::make(location: 's3://koel/some-file.mp3', localPath: '/tmp/some-tmp-file.mp3');

        $storage->expects('storeUploadedFile')->with($file, $uploader)->andReturn($reference);
        $this->duplicateUploadService->expects('detectDuplicate');
        $this->scanner
            ->expects('scan')
            ->with('/tmp/some-tmp-file.mp3')
            ->andReturn($scanInfo);
        $this->songService->expects('createOrUpdateSongFromScan')->andReturn($song);

        $result = $this->makeService($storage)->handleUpload($file, $uploader);

        self::assertTrue($song->is($result));
        self::assertSame('s3://koel/some-file.mp3', $song->path);
        self::assertSame(SongStorageType::S3, $song->storage);
    }

    #[Test]
    public function deletesTempLocalPathAfterUploading(): void
    {
        $scanInfo = ScanInformation::make(path: '/tmp/some-tmp-file.mp3');

        /** @var SongStorage|MustDeleteTemporaryLocalFileAfterUpload|MockInterface $storage */
        $storage = Mockery::mock(SongStorage::class . ',' . MustDeleteTemporaryLocalFileAfterUpload::class, [
            'getStorageType' => SongStorageType::S3,
        ]);
        $song = Song::factory()->createOne();
        $file = '/tmp/some-tmp-file.mp3';
        $uploader = create_user();

        $reference = UploadReference::make(location: 's3://koel/some-file.mp3', localPath: '/tmp/some-tmp-file.mp3');

        $storage->expects('storeUploadedFile')->andReturn($reference);
        $this->duplicateUploadService->expects('detectDuplicate');
        $this->scanner->expects('scan')->andReturn($scanInfo);
        $this->songService->expects('createOrUpdateSongFromScan')->andReturn($song);

        File::expects('delete')->with('/tmp/some-tmp-file.mp3');

        $this->makeService($storage)->handleUpload($file, $uploader);
    }

    #[Test]
    public function undoUploadOnFailure(): void
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
        $this->duplicateUploadService->expects('detectDuplicate');
        $this->scanner->expects('scan')->andReturn($scanInfo);
        $storage->expects('undoUpload')->with($reference);
        $this->songService->expects('createOrUpdateSongFromScan')->andThrow(new Exception('scan failed'));

        $this->expectException(SongUploadFailedException::class);

        $this->makeService($storage)->handleUpload($file, $uploader);
    }

    #[Test]
    public function handleUploadThrowsOnDuplicate(): void
    {
        $storage = Mockery::mock(SongStorage::class, ['getStorageType' => SongStorageType::LOCAL]);
        $file = '/tmp/some-file.mp3';
        $uploader = create_user();

        $reference = UploadReference::make(location: $file, localPath: $file);

        $storage->expects('storeUploadedFile')->with($file, $uploader)->andReturn($reference);
        $this->duplicateUploadService
            ->expects('detectDuplicate')
            ->andThrow(DuplicateSongUploadException::create($file, DuplicateUpload::factory()->makeOne()));

        $this->expectException(DuplicateSongUploadException::class);

        $this->makeService($storage)->handleUpload($file, $uploader);
    }
}
