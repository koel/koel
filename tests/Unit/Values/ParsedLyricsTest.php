<?php

namespace Tests\Unit\Values;

use App\Values\ParsedLyrics;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ParsedLyricsTest extends TestCase
{
    #[Test]
    public function parsesPlainLyricsAsUnsynced(): void
    {
        $lyrics = ParsedLyrics::fromRawLyrics("Line one\nLine two\nLine three");

        self::assertFalse($lyrics->synced);
        self::assertSame(0, $lyrics->offset);
        self::assertSame([['value' => 'Line one'], ['value' => 'Line two'], ['value' => 'Line three']], $lyrics->lines);
    }

    #[Test]
    public function parsesLrcLyricsAsSyncedWithMillisecondOffsets(): void
    {
        $lyrics = ParsedLyrics::fromRawLyrics("[00:12.34]First line\n[01:05.06]Second line");

        self::assertTrue($lyrics->synced);
        self::assertSame(
            [
                ['start' => 12_340, 'value' => 'First line'],
                ['start' => 65_060, 'value' => 'Second line'],
            ],
            $lyrics->lines,
        );
    }

    #[Test]
    public function handlesMissingAndThreeDigitFractions(): void
    {
        $lyrics = ParsedLyrics::fromRawLyrics("[00:30]No fraction\n[00:01.500]Millis");

        self::assertTrue($lyrics->synced);
        self::assertSame(
            [
                ['start' => 1500, 'value' => 'Millis'],
                ['start' => 30_000, 'value' => 'No fraction'],
            ],
            $lyrics->lines,
        );
    }

    #[Test]
    public function extractsOffsetMetadataAndSortsByStart(): void
    {
        $lyrics = ParsedLyrics::fromRawLyrics("[offset:-250]\n[00:10.00]Later\n[00:05.00]Earlier");

        self::assertTrue($lyrics->synced);
        self::assertSame(-250, $lyrics->offset);
        self::assertSame(
            [
                ['start' => 5000, 'value' => 'Earlier'],
                ['start' => 10_000, 'value' => 'Later'],
            ],
            $lyrics->lines,
        );
    }

    #[Test]
    public function ignoresNonTimestampTagsInSyncedLyrics(): void
    {
        $lyrics = ParsedLyrics::fromRawLyrics("[ar:Radiohead]\n[ti:Karma Police]\n[00:00.00]Karma police");

        self::assertTrue($lyrics->synced);
        self::assertSame([['start' => 0, 'value' => 'Karma police']], $lyrics->lines);
    }

    #[Test]
    public function emptyLyricsYieldNoLines(): void
    {
        $lyrics = ParsedLyrics::fromRawLyrics('   ');

        self::assertFalse($lyrics->synced);
        self::assertSame([], $lyrics->lines);
    }
}
