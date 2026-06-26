<?php

namespace Tests\Integration\Services\Scanners;

use App\Helpers\Ulid;
use App\Models\Setting;
use App\Services\Scanners\FileScanner;
use getID3;
use Illuminate\Support\Facades\File;
use Mockery;
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
                'data' => File::get(test_path('fixtures/cover.png')),
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
            'mime_type' => 'audio/mpeg',
            'file_size' => 72_081,
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
                'data' => File::get(test_path('fixtures/cover.png')),
                'image_mime' => 'image/png',
                'image_width' => 512,
                'image_height' => 512,
                'picturetype' => 'Other',
                'datalength' => 7627,
            ],
            'path' => realpath($flacPath),
            'mtime' => filemtime($flacPath),
            'mime_type' => 'audio/flac',
            'file_size' => 532_201,
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
        File::copy(test_path('fixtures/simple.lrc'), $lrcFile);

        self::assertSame("Foo\rbar", $this->scanner->scan($mediaFile)->lyrics);
    }

    #[Test]
    public function readLrcFileIfEmbeddedLyricsNotAvailable(): void
    {
        $base = sys_get_temp_dir() . '/' . Ulid::generate();
        $mediaFile = $base . '.mp3';
        $lrcFile = $base . '.lrc';
        File::copy(test_path('songs/blank.mp3'), $mediaFile);
        File::copy(test_path('fixtures/simple.lrc'), $lrcFile);

        self::assertSame("Line 1\nLine 2\nLine 3", $this->scanner->scan($mediaFile)->lyrics);
    }

    #[Test]
    public function convertsEmbeddedSyncedLyricsToLrc(): void
    {
        $path = test_path('songs/blank.mp3');

        $analyzed = [
            'filenamepath' => $path,
            'id3v2' => [
                'SYLT' => [
                    [
                        'timestampformat' => 2,
                        'lyrics' => [
                            ['data' => 'First line', 'timestamp' => 12_340],
                            ['data' => 'Second line', 'timestamp' => 65_060],
                        ],
                    ],
                ],
            ],
            'playtime_seconds' => 100,
        ];

        $this->swap(getID3::class, Mockery::mock(getID3::class, [
            'CopyTagsToComments' => $analyzed,
            'analyze' => $analyzed,
        ]));

        $info = app(FileScanner::class)->scan($path);

        self::assertSame("[00:12.34]First line\n[01:05.06]Second line", $info->lyrics);
    }

    #[Test]
    public function prefersSyncedLyricsOverUnsynchronisedLyrics(): void
    {
        $path = test_path('songs/blank.mp3');

        $analyzed = [
            'filenamepath' => $path,
            'id3v2' => [
                'SYLT' => [
                    ['timestampformat' => 2, 'lyrics' => [['data' => 'Synced', 'timestamp' => 0]]],
                ],
            ],
            'tags' => ['id3v2' => ['unsynchronised_lyric' => ['Plain lyrics']]],
            'playtime_seconds' => 100,
        ];

        $this->swap(getID3::class, Mockery::mock(getID3::class, [
            'CopyTagsToComments' => $analyzed,
            'analyze' => $analyzed,
        ]));

        self::assertSame('[00:00.00]Synced', app(FileScanner::class)->scan($path)->lyrics);
    }

    #[Test]
    public function treatsSyncedLyricEntryWithoutTimestampAsStartOfFile(): void
    {
        $path = test_path('songs/blank.mp3');

        $analyzed = [
            'filenamepath' => $path,
            'id3v2' => [
                'SYLT' => [
                    [
                        'timestampformat' => 2,
                        'lyrics' => [
                            ['data' => 'Intro line'], // first entry may omit its timestamp
                            ['data' => 'Next line', 'timestamp' => 5000],
                        ],
                    ],
                ],
            ],
            'playtime_seconds' => 100,
        ];

        $this->swap(getID3::class, Mockery::mock(getID3::class, [
            'CopyTagsToComments' => $analyzed,
            'analyze' => $analyzed,
        ]));

        self::assertSame("[00:00.00]Intro line\n[00:05.00]Next line", app(FileScanner::class)->scan($path)->lyrics);
    }

    #[Test]
    public function usesFirstUsableSyncedLyricsFrame(): void
    {
        $path = test_path('songs/blank.mp3');

        $analyzed = [
            'filenamepath' => $path,
            'id3v2' => [
                'SYLT' => [
                    ['timestampformat' => 1, 'lyrics' => [['data' => 'Frame timestamps', 'timestamp' => 1]]],
                    ['timestampformat' => 2, 'lyrics' => [['data' => 'Real line', 'timestamp' => 3000]]],
                ],
            ],
            'playtime_seconds' => 100,
        ];

        $this->swap(getID3::class, Mockery::mock(getID3::class, [
            'CopyTagsToComments' => $analyzed,
            'analyze' => $analyzed,
        ]));

        self::assertSame('[00:03.00]Real line', app(FileScanner::class)->scan($path)->lyrics);
    }

    #[Test]
    public function fallsBackToUnsynchronisedLyricsWhenSyltUsesFrameTimestamps(): void
    {
        $path = test_path('songs/blank.mp3');

        $analyzed = [
            'filenamepath' => $path,
            'id3v2' => [
                'SYLT' => [
                    ['timestampformat' => 1, 'lyrics' => [['data' => 'Synced', 'timestamp' => 1]]],
                ],
            ],
            'tags' => ['id3v2' => ['unsynchronised_lyric' => ['Plain lyrics']]],
            'playtime_seconds' => 100,
        ];

        $this->swap(getID3::class, Mockery::mock(getID3::class, [
            'CopyTagsToComments' => $analyzed,
            'analyze' => $analyzed,
        ]));

        self::assertSame('Plain lyrics', app(FileScanner::class)->scan($path)->lyrics);
    }

    #[Test]
    public function htmlEntities(): void
    {
        $path = test_path('songs/blank.mp3');

        $analyzed = [
            'filenamepath' => $path,
            'tags' => [
                'id3v2' => [
                    'title' => ['&#27700;&#35895;&#24195;&#23455;'],
                    'album' => ['&#23567;&#23721;&#20117;&#12371; Random'],
                    'artist' => ['&#20304;&#20489;&#32190;&#38899; Unknown'],
                ],
            ],
            'encoding' => 'UTF-8',
            'playtime_seconds' => 100,
        ];

        $this->swap(getID3::class, Mockery::mock(getID3::class, [
            'CopyTagsToComments' => $analyzed,
            'analyze' => $analyzed,
        ]));

        /** @var FileScanner $fileScanner */
        $fileScanner = app(FileScanner::class);
        $info = $fileScanner->scan($path);

        self::assertSame('佐倉綾音 Unknown', $info->artistName);
        self::assertSame('小岩井こ Random', $info->albumName);
        self::assertSame('水谷広実', $info->title);
    }
}
