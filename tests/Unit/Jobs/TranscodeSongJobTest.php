<?php

namespace Tests\Unit\Jobs;

use App\Jobs\TranscodeSongJob;
use App\Models\Song;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TranscodeSongJobTest extends TestCase
{
    #[Test]
    public function outlastTheLongestAllowedTranscode(): void
    {
        $song = Song::factory()->createOne();

        self::assertSame(330, (new TranscodeSongJob($song, 256, 300))->timeout);
        self::assertSame(0, (new TranscodeSongJob($song, 256, 0))->timeout);
    }
}
