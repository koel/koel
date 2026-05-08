<?php

namespace Tests\Unit\Values\Scanning;

use App\Values\Scanning\ScanInformation as Component;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ScanInformationTest extends TestCase
{
    private string $songPath;

    public function setUp(): void
    {
        parent::setUp();

        $this->songPath = tempnam(sys_get_temp_dir(), 'koel_scan_');
        file_put_contents($this->songPath, 'fake mp3 bytes');
    }

    public function tearDown(): void
    {
        if (file_exists($this->songPath)) {
            unlink($this->songPath);
        }

        parent::tearDown();
    }

    /** @return array<string, array{string, string}> */
    public static function nonUtf8EncodedTags(): array
    {
        return [
            'GB18030 (Simplified Chinese, covers GB2312)' => ['你好世界', 'GB18030'],
            'Windows-1252 (Western European Latin-1 mojibake)' => ['Café déjà vu', 'Windows-1252'],
        ];
    }

    #[Test]
    #[DataProvider('nonUtf8EncodedTags')]
    public function recoversUtf8FromTagBytes(string $original, string $sourceEncoding): void
    {
        $rawBytes = mb_convert_encoding($original, $sourceEncoding, 'UTF-8');

        $info = Component::fromGetId3Info(['tags' => ['id3v2' => [
            'title' => [$rawBytes],
            'artist' => [$rawBytes],
            'album' => [$rawBytes],
        ]]], $this->songPath);

        self::assertSame($original, $info->title);
        self::assertSame($original, $info->artistName);
        self::assertSame($original, $info->albumName);
    }

    #[Test]
    public function passesThroughValidUtf8Unchanged(): void
    {
        $info = Component::fromGetId3Info(['tags' => ['id3v2' => [
            'title' => ['Café'],
            'artist' => ['東京'],
            'album' => ['Привет'],
        ]]], $this->songPath);

        self::assertSame('Café', $info->title);
        self::assertSame('東京', $info->artistName);
        self::assertSame('Привет', $info->albumName);
    }
}
