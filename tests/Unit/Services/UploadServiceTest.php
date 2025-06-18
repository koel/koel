<?php

namespace Tests\Unit\Services;

use App\Enums\SongStorageType;
use App\Exceptions\SongUploadFailedException;
use App\Models\Song;
use App\Services\Scanner\FileScanner;
use App\Services\SongStorages\Contracts\MustDeleteTemporaryLocalFileAfterUpload;
use App\Services\SongStorages\SongStorage;
use App\Services\UploadService;
use App\Values\Scanning\ScanConfiguration;
use App\Values\Scanning\ScanResult;
use App\Values\UploadReference;
use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_user;

class UploadServiceTest extends TestCase
{
    private FileScanner|MockInterface $scanner;

    public function setUp(): void
    {
        parent::setUp();

        $this->scanner = Mockery::mock(FileScanner::class);
    }

    #[Test]
    public function handleUpload(): void
    {
        $storage = Mockery::mock(SongStorage::class, ['getStorageType' => SongStorageType::LOCAL]);
        $file = Mockery::mock(UploadedFile::class);
        $uploader = create_user();

        $reference = UploadReference::make(
            location: '/var/media/koel/some-file.mp3',
            localPath: '/var/media/koel/some-file.mp3',
        );

        $storage->shouldReceive('storeUploadedFile')
            ->with($file, $uploader)
            ->andReturn($reference);

        $this->scanner->shouldReceive('setFile')
            ->with('/var/media/koel/some-file.mp3')
            ->andReturnSelf();

        $this->scanner->shouldReceive('scan')
            ->with(Mockery::on(static function (ScanConfiguration $config) use ($uploader): bool {
                return $config->owner->is($uploader)
                    && $config->makePublic === $uploader->preferences->makeUploadsPublic
                    && $config->extractFolderStructure;
            }))
            ->andReturn(ScanResult::success('/var/media/koel/some-file.mp3'));

        $song = Song::factory()->create();

        $this->scanner->shouldReceive('getSong')
            ->andReturn($song);

        self::assertSame($song, (new UploadService($storage, $this->scanner))->handleUpload($file, $uploader));
    }

    #[Test]
    public function uploadUpdatesSongPathAndStorage(): void
    {
        $storage = Mockery::mock(SongStorage::class, ['getStorageType' => SongStorageType::S3]);
        $file = Mockery::mock(UploadedFile::class);
        $uploader = create_user();

        $reference = UploadReference::make(
            location: 's3://koel/some-file.mp3',
            localPath: '/tmp/some-tmp-file.mp3',
        );

        $storage->shouldReceive('storeUploadedFile')
            ->with($file, $uploader)
            ->andReturn($reference);

        $this->scanner->shouldReceive('setFile')
            ->with('/tmp/some-tmp-file.mp3')
            ->andReturnSelf();

        $this->scanner->shouldReceive('scan')
            ->with(Mockery::on(static function (ScanConfiguration $config) use ($uploader): bool {
                return $config->owner->is($uploader)
                    && $config->makePublic === $uploader->preferences->makeUploadsPublic
                    && !$config->extractFolderStructure;
            }))
            ->andReturn(ScanResult::success('/tmp/some-tmp-file.mp3'));

        /** @var Song $song */
        $song = Song::factory()->create([
            'path' => '/tmp/some-tmp-file.mp3', // Initially set to the local path
            'storage' => SongStorageType::LOCAL, // Initially set to local storage
        ]);

        $this->scanner->shouldReceive('getSong')
            ->andReturn($song);

        $result = (new UploadService($storage, $this->scanner))->handleUpload($file, $uploader);

        self::assertSame($song, $result);
        self::assertSame('s3://koel/some-file.mp3', $song->path);
        self::assertSame(SongStorageType::S3, $song->storage);
    }

    #[Test]
    public function deletesTempLocalPathAfterUploading(): void
    {
        /** @var SongStorage|MustDeleteTemporaryLocalFileAfterUpload|MockInterface $storage */
        $storage = Mockery::mock(
            SongStorage::class . ',' . MustDeleteTemporaryLocalFileAfterUpload::class,
            ['getStorageType' => SongStorageType::S3]
        );

        $file = Mockery::mock(UploadedFile::class);
        $uploader = create_user();

        $reference = UploadReference::make(
            location: 's3://koel/some-file.mp3',
            localPath: '/tmp/some-tmp-file.mp3',
        );

        $storage->shouldReceive('storeUploadedFile')->andReturn($reference);
        $this->scanner->shouldReceive('setFile')->andReturnSelf();
        $this->scanner->shouldReceive('scan')->andReturn(ScanResult::success('/tmp/some-tmp-file.mp3'));

        /** @var Song $song */
        $song = Song::factory()->create();

        $this->scanner->shouldReceive('getSong')
            ->andReturn($song);

        File::shouldReceive('delete')->once()->with('/tmp/some-tmp-file.mp3');

        (new UploadService($storage, $this->scanner))->handleUpload($file, $uploader);
    }

    #[Test]
    public function undoUploadOnScanningError(): void
    {
        $storage = Mockery::mock(SongStorage::class, ['getStorageType' => SongStorageType::LOCAL]);
        $file = Mockery::mock(UploadedFile::class);
        $uploader = create_user();

        $reference = UploadReference::make(
            location: '/var/media/koel/some-file.mp3',
            localPath: '/var/media/koel/some-file.mp3',
        );

        $storage->shouldReceive('storeUploadedFile')
            ->with($file, $uploader)
            ->andReturn($reference);

        $this->scanner->shouldReceive('setFile')
            ->with('/var/media/koel/some-file.mp3')
            ->andReturnSelf();

        $this->scanner->shouldReceive('scan')
            ->andReturn(ScanResult::error('/var/media/koel/some-file.mp3', 'File supports racism'));

        $storage->shouldReceive('undoUpload')->with($reference)->once();

        $this->expectException(SongUploadFailedException::class);
        $this->expectExceptionMessage('File supports racism');

        (new UploadService($storage, $this->scanner))->handleUpload($file, $uploader);
    }

    #[Test]
    public function undoUploadOnScanningProcessException(): void
    {
        $storage = Mockery::mock(SongStorage::class, ['getStorageType' => SongStorageType::LOCAL]);
        $file = Mockery::mock(UploadedFile::class);
        $uploader = create_user();

        $reference = UploadReference::make(
            location: '/var/media/koel/some-file.mp3',
            localPath: '/var/media/koel/some-file.mp3',
        );

        $storage->shouldReceive('storeUploadedFile')->with($file, $uploader)->andReturn($reference);
        $this->scanner->shouldReceive('setFile')->andReturnSelf();

        $exception = new Exception('Scanning failed due to Koel author too handsome hehe');

        $this->scanner->shouldReceive('scan')->andThrow($exception);
        $storage->shouldReceive('undoUpload')->with($reference)->once();

        $this->expectException(SongUploadFailedException::class);
        $this->expectExceptionMessage('Scanning failed due to Koel author too handsome hehe');

        (new UploadService($storage, $this->scanner))->handleUpload($file, $uploader);
    }
}
