<?php

namespace Tests\Integration\Services\Scanners;

use App\Helpers\Ulid;
use App\Models\Setting;
use App\Services\Scanners\FileScanner;
use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\test_path;

class FileScannerTest extends TestCase
{
    private FileScanner $scanner;

    public function setUp(): void
    {
        parent::setUp();

        Setting::set('media_path', $this->mediaPath);
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
        $info = $this->scanner->scan(test_path('songs/full.mp3'));

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
    public function getFileInfoVorbisCommentsFlac(): void
    {
        $flacPath = test_path('songs/full-vorbis-comments.flac');
        $info = $this->scanner->scan($flacPath);

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
        self::assertSame('blank', $this->scanner->scan(test_path('songs/blank.mp3'))->title);
    }

    #[Test]
    public function ignoreLrcFileIfEmbeddedLyricsAvailable(): void
    {
        $base = sys_get_temp_dir() . '/' . Ulid::generate();
        $mediaFile = $base . '.mp3';
        $lrcFile = $base . '.lrc';
        File::copy(test_path('songs/full.mp3'), $mediaFile);
        File::copy(test_path('blobs/simple.lrc'), $lrcFile);

        self::assertSame("Foo\rbar", $this->scanner->scan($mediaFile)->lyrics);
    }

    #[Test]
    public function readLrcFileIfEmbeddedLyricsNotAvailable(): void
    {
        $base = sys_get_temp_dir() . '/' . Ulid::generate();
        $mediaFile = $base . '.mp3';
        $lrcFile = $base . '.lrc';
        File::copy(test_path('songs/blank.mp3'), $mediaFile);
        File::copy(test_path('blobs/simple.lrc'), $lrcFile);

        self::assertSame("Line 1\nLine 2\nLine 3", $this->scanner->scan($mediaFile)->lyrics);
    }
}
