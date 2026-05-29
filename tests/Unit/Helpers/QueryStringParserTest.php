<?php

namespace Tests\Unit\Helpers;

use App\Helpers\QueryStringParser;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class QueryStringParserTest extends TestCase
{
    #[Test]
    public function preservesDuplicateKeys(): void
    {
        self::assertSame(['songId' => ['a', 'b', 'c']], QueryStringParser::parse('songId=a&songId=b&songId=c'));
    }

    #[Test]
    public function singleValueKeyStillBecomesList(): void
    {
        self::assertSame(['name' => ['Mix']], QueryStringParser::parse('name=Mix'));
    }

    #[Test]
    public function urlDecodesKeysAndValues(): void
    {
        self::assertSame(['my key' => ['hello world']], QueryStringParser::parse('my%20key=hello%20world'));
    }

    #[Test]
    public function skipsBracketSuffixedKeys(): void
    {
        self::assertSame(['plain' => ['x']], QueryStringParser::parse('plain=x&things%5B%5D=a&things%5B%5D=b'));
    }

    #[Test]
    public function skipsPairsWithoutEquals(): void
    {
        self::assertSame(['ok' => ['1']], QueryStringParser::parse('flag&ok=1'));
    }

    #[Test]
    public function emptyStringReturnsEmptyArray(): void
    {
        self::assertSame([], QueryStringParser::parse(''));
    }
}
