<?php

namespace Tests\Unit\Services\Upload;

use App\Enums\SongStorageType;
use App\Exceptions\DuplicateSongUploadException;
use App\Models\DuplicateUpload;
use App\Models\Song;
use App\Repositories\DuplicateUploadRepository;
use App\Repositories\SongRepository;
use App\Services\AudioAnalysis\AnalyzeAudioOnScan;
use App\Services\AudioAnalysis\AudioAnalyzer;
use App\Services\LibrarySummaryService;
use App\Services\Scanners\FileScanner;
use App\Services\SongService;
use App\Services\SongStorages\LocalStorage;
use App\Services\SongStorages\SongStorage;
use App\Services\Transcoding\TranscodeOnScan;
use App\Services\Upload\DuplicateUploadService;
use App\Values\UploadReference;
use Illuminate\Support\Facades\File;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_user;

class DuplicateUploadServiceTest extends TestCase
{
    private SongRepository|MockInterface $songRepository;
    private SongService|MockInterface $songService;
    private FileScanner|MockInterface $scanner;
    private SongStorage|MockInterface $storage;

    public function setUp(): void
    {
        parent::setUp();

        $this->songRepository = Mockery::mock(SongRepository::class);
        $this->songService = Mockery::mock(SongService::class);
        $this->scanner = Mockery::mock(FileScanner::class);
        $this->storage = Mockery::mock(SongStorage::class, ['getStorageType' => SongStorageType::LOCAL]);
    }

    private function makeService(): DuplicateUploadService
    {
        return new DuplicateUploadService(
            $this->songRepository,
            app(DuplicateUploadRepository::class),
            $this->songService,
            $this->scanner,
            $this->storage,
            new TranscodeOnScan(app(LocalStorage::class)),
            new AnalyzeAudioOnScan(app(LocalStorage::class), new AudioAnalyzer()),
            Mockery::mock(LibrarySummaryService::class),
        );
    }

    #[Test]
    public function detectsAndCreatesDuplicateRecord(): void
    {
        $uploader = create_user(['preferences' => ['detect_duplicate_uploads' => true]]);
        $existingSong = Song::factory()->createOne();
        $reference = UploadReference::make('/media/song.mp3', '/media/song.mp3', 'song.mp3');

        File::expects('hash')->with('/tmp/song.mp3', 'xxh128')->andReturn('fast-hash');
        File::expects('hash')->with('/tmp/song.mp3', 'md5')->andReturn('legacy-hash');
        $this->songRepository
            ->expects('findByAnyHash')
            ->with(['fast-hash', 'legacy-hash'], $uploader)
            ->andReturn($existingSong);

        try {
            $this->makeService()->detectDuplicate('/tmp/song.mp3', $reference, $uploader);
            self::fail('Expected DuplicateSongUploadException was not thrown.');
        } catch (DuplicateSongUploadException) {
            $this->assertDatabaseHas('duplicate_uploads', [
                'user_id' => $uploader->id,
                'existing_song_id' => $existingSong->id,
                'file_name' => 'song.mp3',
            ]);
        }
    }

    #[Test]
    public function skipsDetectionWhenPreferenceIsDisabled(): void
    {
        $uploader = create_user(['preferences' => ['detect_duplicate_uploads' => false]]);
        $reference = UploadReference::make('/media/song.mp3', '/media/song.mp3', 'song.mp3');

        $this->songRepository->expects('findByAnyHash')->never();

        $this->makeService()->detectDuplicate('/tmp/song.mp3', $reference, $uploader);
    }

    #[Test]
    public function doesNothingWhenNoDuplicateFound(): void
    {
        $uploader = create_user(['preferences' => ['detect_duplicate_uploads' => true]]);
        $reference = UploadReference::make('/media/song.mp3', '/media/song.mp3', 'song.mp3');

        File::expects('hash')->twice()->andReturn('abc123');
        $this->songRepository->expects('findByAnyHash')->with(['abc123', 'abc123'], $uploader)->andReturnNull();

        $this->makeService()->detectDuplicate('/tmp/song.mp3', $reference, $uploader);

        self::assertSame(0, DuplicateUpload::query()->count());
    }

    #[Test]
    public function keepSkipsADuplicateAlreadyKept(): void
    {
        $upload = DuplicateUpload::factory()->createOne();
        $upload->delete();

        $this->scanner->expects('scan')->never();

        self::assertTrue(
            $this
                ->makeService()
                ->keep(collect([$upload]))
                ->isEmpty(),
        );
    }

    #[Test]
    public function discardDeletesAFileOnCloudStorageByItsKey(): void
    {
        $uploads = DuplicateUpload::factory()->createMany([
            ['location' => 's3://koel-bucket/songs/duplicate.mp3', 'storage' => SongStorageType::S3],
        ]);

        $this->mock(SongStorage::class)->expects('delete')->with('songs/duplicate.mp3', Mockery::any());

        $this->makeService()->discard($uploads);
    }

    #[Test]
    public function discardDeletesRecordsAndFiles(): void
    {
        $uploads = DuplicateUpload::factory()->createMany([[], []]);

        $this->makeService()->discard($uploads);

        foreach ($uploads as $upload) {
            $this->assertDatabaseMissing('duplicate_uploads', ['id' => $upload->id]);
        }
    }
}
