<?php

namespace Tests\Unit\Helpers;

use App\Helpers\TagEncodingFixer;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TagEncodingFixerTest extends TestCase
{
    #[Test]
    public function recoversGb18030FromRawBytes(): void
    {
        $bytes = mb_convert_encoding('你好世界', 'GB18030', 'UTF-8');

        self::assertSame('你好世界', TagEncodingFixer::fix($bytes));
    }

    #[Test]
    public function recoversGb18030FromDoubleMojibake(): void
    {
        // Simulate getID3's failure mode: raw GB2312 bytes treated as Latin-1, then
        // converted to UTF-8. The result is valid UTF-8 but renders as nonsense
        // ("安妮" → "°²ÄÝ"). The fixer must round-trip through Latin-1 to recover.
        $rawBytes = mb_convert_encoding('安妮', 'GB18030', 'UTF-8');
        $doubleMojibake = mb_convert_encoding($rawBytes, 'UTF-8', 'ISO-8859-1');

        self::assertSame('安妮', TagEncodingFixer::fix($doubleMojibake));
    }

    #[Test]
    public function recoversLatin1FromRawBytes(): void
    {
        $bytes = mb_convert_encoding('Café déjà vu', 'Windows-1252', 'UTF-8');

        self::assertSame('Café déjà vu', TagEncodingFixer::fix($bytes));
    }

    #[Test]
    public function passesThroughValidUtf8(): void
    {
        self::assertSame('Café', TagEncodingFixer::fix('Café'));
        self::assertSame('東京', TagEncodingFixer::fix('東京'));
        self::assertSame('Привет', TagEncodingFixer::fix('Привет'));
    }

    #[Test]
    public function leavesIsolatedHighBytesAloneOnTheCjkPath(): void
    {
        // Real UTF-8 strings whose Latin-1 form has isolated high bytes (e.g. "Cafés"
        // → bytes 43 61 66 E9 73) must NOT trigger CJK conversion, even though E9 73
        // is structurally a valid GB18030 sequence. The all-high-bytes-paired heuristic
        // protects against this.
        self::assertSame('Cafés', TagEncodingFixer::fix('Cafés'));
        self::assertSame('Renée Smith', TagEncodingFixer::fix('Renée Smith'));
    }

    #[Test]
    public function passesThroughEmptyAndNonString(): void
    {
        self::assertSame('', TagEncodingFixer::fix(''));
        self::assertSame(null, TagEncodingFixer::fix(null));
        self::assertSame(42, TagEncodingFixer::fix(42));
    }
}
