<?php

namespace Tests\Integration\Values;

use App\Models\Song;
use App\Values\TranscodeResult;
use Illuminate\Process\PendingProcess;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Process;
use Tests\TestCase;

use function Tests\test_path;

class TranscodeResultTest extends TestCase
{
    public function testCreateAndRetrieve(): void
    {
        config(['koel.streaming.ffmpeg_path' => '/usr/bin/ffmpeg']);
        Process::fake();

        /** @var Song $song */
        $song = Song::factory()->create();

        $result = TranscodeResult::getForSong($song, 128, test_path('songs/full.mp3'));

        $closure = static function (PendingProcess $process) use ($song): bool {
            return $process->command === [
                    '/usr/bin/ffmpeg',
                    '-i',
                    $song->storage_metadata->getPath(),
                    '-vn',
                    '-b:a',
                    '128k',
                    '-preset',
                    'ultrafast',
                    '-y',
                    test_path('songs/full.mp3'),
                ];
        };

        Process::assertRanTimes($closure, 1);

        self::assertSame('3c7b4e187277e40f8ae793650336e03b', $result->checksum);
        self::assertSame(test_path('songs/full.mp3'), $result->path);

        self::assertTrue(Cache::has("transcoded.{$song->id}.128"));

        $cached = TranscodeResult::getForSong($song, 128, test_path('songs/full.mp3'));

        // No extra ffmpeg process should be run.
        Process::assertRanTimes($closure, 1);
        self::assertSame('3c7b4e187277e40f8ae793650336e03b', $cached->checksum);
        self::assertSame(test_path('songs/full.mp3'), $cached->path);
    }
}
