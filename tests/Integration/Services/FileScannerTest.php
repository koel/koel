<?php

namespace Tests\Integration\Services;

use App\Models\Album;
use App\Models\Artist;
use App\Models\Setting;
use App\Models\Song;
use App\Services\FileScanner;
use App\Values\ScanConfiguration;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_admin;
use function Tests\test_path;

class FileScannerTest extends TestCase
{
    private FileScanner $scanner;

    public function setUp(): void
    {
        parent::setUp();

        Setting::set('media_path', test_path());
        $this->scanner = app(FileScanner::class);
    }

    protected function tearDown(): void
    {
        Setting::set('media_path', '');

        parent::tearDown();
    }

    #[Test]
    public function getFileInfo(): void
    {
        $info = $this->scanner->setFile(test_path('songs/full.mp3'))->getScanInformation();

        $expectedData = [
            'artist' => 'Koel',
            'album' => 'Koel Testing Vol. 1',
            'title' => 'Amet',
            'track' => 5,
            'disc' => 3,
            'lyrics' => "Foo\rbar",
            'cover' => [
                'data' => File::get(test_path('blobs/cover.png')),
                'image_mime' => 'image/png',
                'image_width' => 512,
                'image_height' => 512,
                'imagetype' => 'PNG',
                'picturetype' => 'Other',
                'description' => '',
                'datalength' => 7627,
            ],
            'path' => test_path('songs/full.mp3'),
            'mtime' => filemtime(test_path('songs/full.mp3')),
            'albumartist' => '',
            'year' => 2015,
        ];

        self::assertArraySubset($expectedData, $info->toArray());
        self::assertEqualsWithDelta(10, $info->length, 0.1);
    }

    #[Test]
    public function scan(): void
    {
        $result = $this->scanner->setFile(test_path('songs/full.mp3'))->scan(
            ScanConfiguration::make(owner: create_admin())
        );

        $song = Song::query()->where('path', $result->path)->firstOrFail();

        self::assertArraySubset([
            'title' => 'Amet',
            'track' => 5,
            'disc' => 3,
            'lyrics' => "Foo\rbar",
            'mtime' => filemtime(test_path('songs/full.mp3')),
            'year' => 2015,
            'is_public' => false,
        ], $song->getAttributes());

        self::assertSame(2015, $song->album->year);

        // Ensure a folder is created for the song
        self::assertNotNull($song->folder);
    }

    #[Test]
    public function scanningSetsAlbumReleaseYearIfApplicable(): void
    {
        $artist = Artist::factory(['name' => 'Koel'])->create();

        /** @var Album $album */
        $album = Album::factory([
            'name' => 'Koel Testing Vol. 1',
            'year' => null,
        ])->for($artist)
            ->create();

        self::assertNull($album->year);

        $this->scanner->setFile(test_path('songs/full.mp3'))->scan(
            ScanConfiguration::make(owner: create_admin())
        );

        $album->refresh();
        self::assertSame(2015, $album->year);
    }

    #[Test]
    public function scanningDoesNotSetAlbumReleaseYearIfAlreadyExists(): void
    {
        $artist = Artist::factory(['name' => 'Koel'])->create();

        /** @var Album $album */
        $album = Album::factory([
            'name' => 'Koel Testing Vol. 1',
            'year' => 2018,
        ])->for($artist)
            ->create();

        $result = $this->scanner->setFile(test_path('songs/full.mp3'))->scan(
            ScanConfiguration::make(owner: create_admin())
        );

        $song = Song::query()->where('path', $result->path)->firstOrFail();

        self::assertTrue($song->album->is($album));

        $album->refresh();

        self::assertSame(2018, $album->year);
    }

    #[Test]
    public function getFileInfoVorbisCommentsFlac(): void
    {
        $flacPath = test_path('songs/full-vorbis-comments.flac');
        $info = $this->scanner->setFile($flacPath)->getScanInformation();

        $expectedData = [
            'artist' => 'Koel',
            'album' => 'Koel Testing Vol. 1',
            'albumartist' => 'Koel',
            'title' => 'Amet',
            'track' => 5,
            'disc' => 3,
            'lyrics' => "Foo\r\nbar",
            'cover' => [
                'data' => File::get(test_path('blobs/cover.png')),
                'image_mime' => 'image/png',
                'image_width' => 512,
                'image_height' => 512,
                'picturetype' => 'Other',
                'datalength' => 7627,
            ],
            'path' => realpath($flacPath),
            'mtime' => filemtime($flacPath),
        ];

        self::assertArraySubset($expectedData, $info->toArray());
        self::assertEqualsWithDelta(10, $info->length, 0.1);
    }

    #[Test]
    public function songWithoutTitleHasFileNameAsTitle(): void
    {
        $this->scanner->setFile(test_path('songs/blank.mp3'));

        self::assertSame('blank', $this->scanner->getScanInformation()->title);
    }

    #[Test]
    public function ignoreLrcFileIfEmbeddedLyricsAvailable(): void
    {
        $base = sys_get_temp_dir() . '/' . Str::uuid();
        $mediaFile = $base . '.mp3';
        $lrcFile = $base . '.lrc';
        File::copy(test_path('songs/full.mp3'), $mediaFile);
        File::copy(test_path('blobs/simple.lrc'), $lrcFile);

        self::assertSame("Foo\rbar", $this->scanner->setFile($mediaFile)->getScanInformation()->lyrics);
    }

    #[Test]
    public function readLrcFileIfEmbeddedLyricsNotAvailable(): void
    {
        $base = sys_get_temp_dir() . '/' . Str::uuid();
        $mediaFile = $base . '.mp3';
        $lrcFile = $base . '.lrc';
        File::copy(test_path('songs/blank.mp3'), $mediaFile);
        File::copy(test_path('blobs/simple.lrc'), $lrcFile);

        $info = $this->scanner->setFile($mediaFile)->getScanInformation();

        self::assertSame("Line 1\nLine 2\nLine 3", $info->lyrics);
    }
}
