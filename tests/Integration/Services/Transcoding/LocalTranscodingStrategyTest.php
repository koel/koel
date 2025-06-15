<?php

namespace Tests\Integration\Services\Transcoding;

use App\Helpers\Ulid;
use App\Models\Song;
use App\Models\Transcode;
use App\Services\Transcoding\LocalTranscodingStrategy;
use App\Services\Transcoding\Transcoder;
use Illuminate\Support\Facades\File;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LocalTranscodingStrategyTest extends TestCase
{
    private LocalTranscodingStrategy $strategy;
    private MockInterface|Transcoder $transcoder;

    public function setUp(): void
    {
        parent::setUp();

        $this->transcoder = $this->mock(Transcoder::class);
        $this->strategy = app(LocalTranscodingStrategy::class);
    }

    #[Test]
    public function getTranscodedLocation(): void
    {
        $ulid = Ulid::freeze();

        /** @var Song $song */
        $song = Song::factory()->create(['path' => '/path/to/song.flac']);

        $destination = artifact_path("transcodes/128/$ulid.m4a", ensureDirectoryExists: false);

        $this->transcoder->shouldReceive('transcode')
            ->with('/path/to/song.flac', $destination, 128)
            ->once();

        File::shouldReceive('hash')
            ->with($destination)
            ->andReturn('mocked-checksum');

        File::shouldReceive('ensureDirectoryExists')
            ->with(dirname($destination))
            ->once();

        $transcodedPath = $this->strategy->getTranscodeLocation($song, 128);

        $this->assertDatabaseHas(Transcode::class, [
            'song_id' => $song->id,
            'location' => $destination,
            'bit_rate' => 128,
            'hash' => 'mocked-checksum',
        ]);

        self::assertSame($transcodedPath, $destination);
    }

    #[Test]
    public function getFromDatabaseRecord(): void
    {
        $this->transcoder->shouldReceive('transcode')->never();

        /** @var Transcode $transcode */
        $transcode = Transcode::factory()->create([
            'location' => '/path/to/transcode.m4a',
            'bit_rate' => 128,
            'hash' => 'mocked-checksum',
        ]);

        File::shouldReceive('isReadable')
            ->with('/path/to/transcode.m4a')
            ->andReturn(true);

        File::shouldReceive('hash')
            ->with('/path/to/transcode.m4a')
            ->andReturn('mocked-checksum');

        $transcodedPath = $this->strategy->getTranscodeLocation($transcode->song, $transcode->bit_rate);

        self::assertSame($transcode->location, $transcodedPath);
    }

    #[Test]
    public function retranscodeIfRecordIsInvalid(): void
    {
        $ulid = Ulid::freeze();
        $song = Song::factory()->create(['path' => '/path/to/song.flac']);

        /** @var Transcode $transcode */
        $transcode = Transcode::factory()->for($song)->create([
            'location' => '/path/to/transcode.m4a',
            'bit_rate' => 128,
            'hash' => 'mocked-checksum',
        ]);

        $destination = artifact_path("transcodes/128/$ulid.m4a", ensureDirectoryExists: false);

        File::shouldReceive('isReadable')
            ->with('/path/to/transcode.m4a')
            ->andReturn(false);

        File::shouldReceive('delete')
            ->with('/path/to/transcode.m4a');

        File::shouldReceive('hash')
            ->with($destination)
            ->andReturn('mocked-checksum');

        File::shouldReceive('ensureDirectoryExists')
            ->with(dirname($destination))
            ->once();

        $this->transcoder->shouldReceive('transcode')
            ->with('/path/to/song.flac', $destination, 128)
            ->once();

        $transcodedLocation = $this->strategy->getTranscodeLocation($song, 128);

        self::assertSame($destination, $transcodedLocation);
        self::assertSame($transcode->refresh()->location, $transcodedLocation);
    }
}
