<?php

namespace Tests\Unit\Helpers\Encoding;

use App\Helpers\Encoding\Bom;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BomTest extends TestCase
{
    #[Test]
    public function stripsUtf8Bom(): void
    {
        self::assertSame('Beethoven', Bom::strip("\xEF\xBB\xBFBeethoven"));
    }

    #[Test]
    public function stripsUtf16BeBom(): void
    {
        $bytes = "\xFE\xFF" . mb_convert_encoding('Beethoven', 'UTF-16BE', 'UTF-8');

        self::assertSame('Beethoven', Bom::strip($bytes));
    }

    #[Test]
    public function stripsUtf16LeBom(): void
    {
        $bytes = "\xFF\xFE" . mb_convert_encoding('Beethoven', 'UTF-16LE', 'UTF-8');

        self::assertSame('Beethoven', Bom::strip($bytes));
    }

    #[Test]
    public function stripsUtf32BeBom(): void
    {
        $bytes = "\x00\x00\xFE\xFF" . mb_convert_encoding('Beethoven', 'UTF-32BE', 'UTF-8');

        self::assertSame('Beethoven', Bom::strip($bytes));
    }

    #[Test]
    public function stripsUtf32LeBomWithoutMisidentifyingAsUtf16Le(): void
    {
        $bytes = "\xFF\xFE\x00\x00" . mb_convert_encoding('Beethoven', 'UTF-32LE', 'UTF-8');

        self::assertSame('Beethoven', Bom::strip($bytes));
    }

    #[Test]
    public function returnsInputUnchangedWhenNoBomPresent(): void
    {
        self::assertSame('Beethoven', Bom::strip('Beethoven'));
    }

    #[Test]
    public function passesThroughNullAndEmpty(): void
    {
        self::assertNull(Bom::strip(null));
        self::assertSame('', Bom::strip(''));
    }
}
