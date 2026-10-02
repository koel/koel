<?php

namespace App\Services\AudioAnalysis;

use App\Exceptions\AudioAnalysisFailedException;
use App\Models\Song;
use App\Models\SongAnalysis;
use Illuminate\Container\Attributes\Config;
use Illuminate\Support\Facades\Process;

class AudioAnalyzer
{
    private const int LEVEL_COUNT = 800;
    private const int SQUARED_SAMPLE_RATE = 100;
    private const int TIMEOUT_SECONDS = 300;

    public function __construct(
        #[Config('koel.streaming.ffmpeg_path')]
        private readonly string $ffmpegPath = '',
    ) {}

    public function analyzeSong(Song $song, string $filePath): void
    {
        setlocale(LC_CTYPE, 'en_US.UTF-8'); // #1481 special chars might be stripped otherwise

        $result = Process::timeout(self::TIMEOUT_SECONDS)->run([
            $this->ffmpegPath,
            '-hide_banner',
            '-nostdin',
            '-i',
            $filePath,
            '-filter_complex',
            sprintf(
                '[0:a]asplit=2[loudness][levels];'
                . '[loudness]ebur128=peak=true:framelog=quiet[loudness_out];'
                . '[levels]aformat=sample_fmts=flt:channel_layouts=mono,aeval=val(0)*val(0),aresample=%d[levels_out]',
                self::SQUARED_SAMPLE_RATE,
            ),
            '-map',
            '[loudness_out]',
            '-f',
            'null',
            '-',
            '-map',
            '[levels_out]',
            '-f',
            'f32le',
            '-',
        ]);

        throw_if($result->failed(), AudioAnalysisFailedException::class, $result->errorOutput());

        $loudness = self::parseSummaryValue($result->errorOutput(), 'Integrated loudness', 'I', 'LUFS');
        $truePeak = self::parseSummaryValue($result->errorOutput(), 'True peak', 'Peak', 'dBFS');

        throw_if($loudness === null || $truePeak === null, AudioAnalysisFailedException::class);

        SongAnalysis::query()->updateOrCreate(['song_id' => $song->id], [
            'loudness' => $loudness,
            'true_peak' => $truePeak,
            'levels' => self::computeLevels($result->output()),
        ]);
    }

    private static function parseSummaryValue(
        string $ffmpegOutput,
        string $section,
        string $label,
        string $unit,
    ): ?float {
        $pattern = sprintf('/%s:\s+%s:\s+(-?\d+(?:\.\d+)?) %s/', $section, $label, $unit);

        if (!preg_match($pattern, $ffmpegOutput, $matches)) {
            return null;
        }

        return (float) $matches[1];
    }

    /**
     * @return array<float>
     */
    private static function computeLevels(string $squaredSamples): array
    {
        $samples = array_values(unpack('g*', $squaredSamples) ?: []);
        $samplesPerLevel = max(1, (int) ceil(count($samples) / self::LEVEL_COUNT));

        return array_map(
            static fn (array $chunk): float => round(sqrt(max(0, array_sum($chunk) / count($chunk))), 3),
            array_chunk($samples, $samplesPerLevel),
        );
    }
}
