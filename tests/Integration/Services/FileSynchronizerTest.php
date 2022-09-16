<?php

namespace Tests\Integration\Services;

use App\Services\FileSynchronizer;
use Illuminate\Support\Str;
use Tests\TestCase;

class FileSynchronizerTest extends TestCase
{
    private FileSynchronizer $fileSynchronizer;

    public function setUp(): void
    {
        parent::setUp();

        $this->fileSynchronizer = app(FileSynchronizer::class);
    }

    public function testGetFileInfo(): void
    {
        $info = $this->fileSynchronizer->setFile(__DIR__ . '/../../songs/full.mp3')->getFileScanInformation();

        $expectedData = [
            'artist' => 'Koel',
            'album' => 'Koel Testing Vol. 1',
            'title' => 'Amet',
            'track' => 5,
            'disc' => 3,
            'lyrics' => "Foo\rbar",
            'cover' => [
                'data' => file_get_contents(__DIR__ . '/../../blobs/cover.png'),
                'image_mime' => 'image/png',
                'image_width' => 512,
                'image_height' => 512,
                'imagetype' => 'PNG',
                'picturetype' => 'Other',
                'description' => '',
                'datalength' => 7627,
            ],
            'path' => realpath(__DIR__ . '/../../songs/full.mp3'),
            'mtime' => filemtime(__DIR__ . '/../../songs/full.mp3'),
            'albumartist' => '',
        ];

        self::assertArraySubset($expectedData, $info->toArray());
        self::assertEqualsWithDelta(10, $info->length, 0.1);
    }

    public function testGetFileInfoVorbisCommentsFlac(): void
    {
        $flacPath = __DIR__ . '/../../songs/full-vorbis-comments.flac';
        $info = $this->fileSynchronizer->setFile($flacPath)->getFileScanInformation();

        $expectedData = [
            'artist' => 'Koel',
            'album' => 'Koel Testing Vol. 1',
            'albumartist' => 'Koel',
            'title' => 'Amet',
            'track' => 5,
            'disc' => 3,
            'lyrics' => "Foo\r\nbar",
            'cover' => [
                'data' => file_get_contents(__DIR__ . '/../../blobs/cover.png'),
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
        $this->fileSynchronizer->setFile(__DIR__ . '/../../songs/blank.mp3');

        self::assertSame('blank', $this->fileSynchronizer->getFileScanInformation()->title);
    }

    public function testIgnoreLrcFileIfEmbeddedLyricsAvailable(): void
    {
        $base = sys_get_temp_dir() . '/' . Str::uuid();
        $mediaFile = $base . '.mp3';
        $lrcFile = $base . '.lrc';
        copy(__DIR__ . '/../../songs/full.mp3', $mediaFile);
        copy(__DIR__ . '/../../blobs/simple.lrc', $lrcFile);

        self::assertSame("Foo\rbar", $this->fileSynchronizer->setFile($mediaFile)->getFileScanInformation()->lyrics);
    }

    public function testReadLrcFileIfEmbeddedLyricsNotAvailable(): void
    {
        $base = sys_get_temp_dir() . '/' . Str::uuid();
        $mediaFile = $base . '.mp3';
        $lrcFile = $base . '.lrc';
        copy(__DIR__ . '/../../songs/blank.mp3', $mediaFile);
        copy(__DIR__ . '/../../blobs/simple.lrc', $lrcFile);

        $info = $this->fileSynchronizer->setFile($mediaFile)->getFileScanInformation();

        self::assertSame("Line 1\nLine 2\nLine 3", $info->lyrics);
    }
}
