<?php

namespace Tests\Unit\Services\AudioAnalysis;

use App\Exceptions\AudioAnalysisFailedException;
use App\Models\Song;
use App\Models\SongAnalysis;
use App\Services\AudioAnalysis\AudioAnalyzer;
use Illuminate\Process\PendingProcess;
use Illuminate\Support\Facades\Process;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AudioAnalyzerTest extends TestCase
{
    private const string LOUDNESS_SUMMARY = <<<'TXT'
        [Parsed_ebur128_1 @ 0x600] Summary:

          Integrated loudness:
            I:         -10.2 LUFS
            Threshold: -20.4 LUFS

          True peak:
            Peak:        1.5 dBFS
        TXT;

    private static function makeSquaredSamples(int $sampleCount, float $squaredSample): string
    {
        return pack('g*', ...array_fill(0, $sampleCount, $squaredSample));
    }

    #[Test]
    public function storeLoudnessTruePeakAndLevels(): void
    {
        $song = Song::factory()->createOne();

        Process::fake([
            '*' => Process::result(
                output: self::makeSquaredSamples(800, 0.0625) . self::makeSquaredSamples(800, 0.5625),
                errorOutput: self::LOUDNESS_SUMMARY,
            ),
        ]);

        (new AudioAnalyzer('/usr/bin/ffmpeg'))->analyzeSong($song, '/music/song.mp3');

        $song->refresh();

        self::assertSame(-10.2, $song->analysis->loudness);
        self::assertSame(1.5, $song->analysis->true_peak);
        self::assertSame([...array_fill(0, 400, 0.25), ...array_fill(0, 400, 0.75)], $song->analysis->levels);

        Process::assertRan(static fn (PendingProcess $process): bool => in_array(
            '/music/song.mp3',
            $process->command,
            true,
        ));
    }

    #[Test]
    public function replaceAnExistingWaveform(): void
    {
        $song = Song::factory()->createOne();
        SongAnalysis::factory()->for($song)->createOne();

        Process::fake(['*' => Process::result(
            output: self::makeSquaredSamples(8, 0.25),
            errorOutput: self::LOUDNESS_SUMMARY,
        )]);

        (new AudioAnalyzer('/usr/bin/ffmpeg'))->analyzeSong($song, '/music/song.mp3');

        self::assertSame(array_fill(0, 8, 0.5), $song->refresh()->analysis->levels);
    }

    #[Test]
    public function throwWhenFfmpegFails(): void
    {
        $song = Song::factory()->createOne();

        Process::fake(['*' => Process::result(errorOutput: 'Invalid data', exitCode: 1)]);

        $this->expectException(AudioAnalysisFailedException::class);

        (new AudioAnalyzer('/usr/bin/ffmpeg'))->analyzeSong($song, '/music/song.mp3');
    }

    #[Test]
    public function throwWhenTheLoudnessIsUnmeasurable(): void
    {
        $song = Song::factory()->createOne();

        Process::fake([
            '*' => Process::result(output: self::makeSquaredSamples(8, 0), errorOutput: <<<'TXT'
                  Integrated loudness:
                    I:         -70.0 LUFS
                  True peak:
                    Peak:       -inf dBFS
                TXT),
        ]);

        $this->expectException(AudioAnalysisFailedException::class);

        (new AudioAnalyzer('/usr/bin/ffmpeg'))->analyzeSong($song, '/music/song.mp3');
    }
}
