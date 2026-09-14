<?php

namespace Tests\Unit\Helpers;

use App\Helpers\LuceneQuery;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LuceneQueryTest extends TestCase
{
    /** @return array<string, array{string, string}> */
    public static function provideValues(): array
    {
        return [
            'single word' => ['Radiohead', '"Radiohead"'],
            'several words' => ['Pink Floyd', '"Pink Floyd"'],
            'a quote in the name' => ['Say "Yes"', '"Say \"Yes\""'],
            'a backslash in the name' => ['AC\DC', '"AC\\\\DC"'],
            'reserved words' => ['Sigur Rós AND Björk', '"Sigur Rós AND Björk"'],
            'empty' => ['', '""'],
        ];
    }

    #[Test, DataProvider('provideValues')]
    public function quoteValueAsASinglePhrase(string $value, string $expected): void
    {
        self::assertSame($expected, LuceneQuery::phrase($value));
    }
}
