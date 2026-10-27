<?php

namespace Tests\Unit\Values\Transcoding;

use App\Values\Transcoding\PreviewSection;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PreviewSectionTest extends TestCase
{
    /** @return array<string, array{float, float, float}> */
    public static function provideSongLengths(): array
    {
        return [
            'long song' => [300.0, 100.0, 30.0],
            'song barely longer than a preview' => [36.0, 6.0, 30.0],
            'song exactly as long as a preview' => [30.0, 0.0, 30.0],
            'song shorter than a preview' => [20.0, 0.0, 20.0],
        ];
    }

    #[Test]
    #[DataProvider('provideSongLengths')]
    public function startAThirdIntoTheSongWithoutRunningPastItsEnd(
        float $songLength,
        float $expectedStart,
        float $expectedDuration,
    ): void {
        $section = PreviewSection::make($songLength);

        self::assertSame($expectedStart, $section->start);
        self::assertSame($expectedDuration, $section->duration);
    }
}
