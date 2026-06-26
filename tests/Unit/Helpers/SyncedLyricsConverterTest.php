<?php

namespace Tests\Unit\Helpers;

use App\Helpers\SyncedLyricsConverter;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SyncedLyricsConverterTest extends TestCase
{
    #[Test]
    public function convertsMillisecondTimestampsToLrc(): void
    {
        $lrc = SyncedLyricsConverter::fromSyltFrames([
            [
                'timestampformat' => 2,
                'lyrics' => [
                    ['data' => 'First line', 'timestamp' => 12_340],
                    ['data' => 'Second line', 'timestamp' => 65_060],
                ],
            ],
        ]);

        self::assertSame("[00:12.34]First line\n[01:05.06]Second line", $lrc);
    }

    #[Test]
    public function treatsEntryWithoutTimestampAsStartOfFile(): void
    {
        $lrc = SyncedLyricsConverter::fromSyltFrames([
            [
                'timestampformat' => 2,
                'lyrics' => [
                    ['data' => 'Intro line'],
                    ['data' => 'Next line', 'timestamp' => 5000],
                ],
            ],
        ]);

        self::assertSame("[00:00.00]Intro line\n[00:05.00]Next line", $lrc);
    }

    #[Test]
    public function skipsLaterEntriesWithoutTimestamp(): void
    {
        $lrc = SyncedLyricsConverter::fromSyltFrames([
            [
                'timestampformat' => 2,
                'lyrics' => [
                    ['data' => 'Intro', 'timestamp' => 1000],
                    ['data' => 'Malformed line'], // missing timestamp, not first -> skipped
                    ['data' => 'Outro', 'timestamp' => 2000],
                ],
            ],
        ]);

        self::assertSame("[00:01.00]Intro\n[00:02.00]Outro", $lrc);
    }

    #[Test]
    public function usesFirstUsableFrame(): void
    {
        $lrc = SyncedLyricsConverter::fromSyltFrames([
            ['timestampformat' => 1, 'lyrics' => [['data' => 'Frame timestamps', 'timestamp' => 1]]],
            ['timestampformat' => 2, 'lyrics' => [['data' => 'Real line', 'timestamp' => 3000]]],
        ]);

        self::assertSame('[00:03.00]Real line', $lrc);
    }

    #[Test]
    public function returnsEmptyForUnsupportedFrameTimestampFormat(): void
    {
        $lrc = SyncedLyricsConverter::fromSyltFrames([
            ['timestampformat' => 1, 'lyrics' => [['data' => 'Frame timestamps', 'timestamp' => 1]]],
        ]);

        self::assertSame('', $lrc);
    }

    #[Test]
    public function returnsEmptyWhenThereAreNoFrames(): void
    {
        self::assertSame('', SyncedLyricsConverter::fromSyltFrames([]));
    }
}
