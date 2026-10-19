<?php

namespace Tests\Unit\Models;

use App\Models\Song;
use App\Models\Transcode;
use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\test_path;

class TranscodeTest extends TestCase
{
    #[Test]
    public function becomeInvalidOnceItsSourceFileChanges(): void
    {
        $location = test_path('songs/blank.mp3');
        $song = Song::factory()->createOne(['mtime' => now()->subDay()->getTimestamp()]);
        $transcode = Transcode::factory()->for($song)->createOne([
            'location' => $location,
            'file_size' => File::size($location),
        ]);

        self::assertTrue($transcode->isValid());

        $song->update(['mtime' => now()->addMinute()->getTimestamp()]);

        self::assertFalse($transcode->refresh()->isValid());
    }
}
