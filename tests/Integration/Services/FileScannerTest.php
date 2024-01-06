<?php

namespace Tests\Integration\Services;

use App\Services\FileScanner;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Tests\TestCase;

class FileScannerTest extends TestCase
{
    private FileScanner $scanner;

    public function setUp(): void
    {
        parent::setUp();

        $this->scanner = app(FileScanner::class);
    }

    public function testGetFileInfo(): void
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
        ];

        self::assertArraySubset($expectedData, $info->toArray());
        self::assertEqualsWithDelta(10, $info->length, 0.1);
    }

    public function testGetFileInfoVorbisCommentsFlac(): void
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

    public function testSongWithoutTitleHasFileNameAsTitle(): void
    {
        $this->scanner->setFile(test_path('songs/blank.mp3'));

        self::assertSame('blank', $this->scanner->getScanInformation()->title);
    }

    public function testIgnoreLrcFileIfEmbeddedLyricsAvailable(): void
    {
        $base = sys_get_temp_dir() . '/' . Str::uuid();
        $mediaFile = $base . '.mp3';
        $lrcFile = $base . '.lrc';
        File::copy(test_path('songs/full.mp3'), $mediaFile);
        File::copy(test_path('blobs/simple.lrc'), $lrcFile);

        self::assertSame("Foo\rbar", $this->scanner->setFile($mediaFile)->getScanInformation()->lyrics);
    }

    public function testReadLrcFileIfEmbeddedLyricsNotAvailable(): void
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
