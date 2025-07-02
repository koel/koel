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
        /** @var Song $song */
        $song = Song::factory()->create(['path' => '/path/to/song.flac']);

        $ulid = Ulid::freeze();

        $destination = artifact_path("transcodes/128/$ulid.m4a", ensureDirectoryExists: false);

        $this->transcoder->expects('transcode')
            ->with('/path/to/song.flac', $destination, 128);

        File::expects('hash')
            ->with($destination)
            ->andReturn('mocked-checksum');

        File::expects('ensureDirectoryExists')
            ->with(dirname($destination));

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
        $this->transcoder->expects('transcode')->never();

        /** @var Transcode $transcode */
        $transcode = Transcode::factory()->create([
            'location' => '/path/to/transcode.m4a',
            'bit_rate' => 128,
            'hash' => 'mocked-checksum',
        ]);

        File::expects('isReadable')
            ->with('/path/to/transcode.m4a')
            ->andReturn(true);

        File::expects('hash')
            ->with('/path/to/transcode.m4a')
            ->andReturn('mocked-checksum');

        $transcodedPath = $this->strategy->getTranscodeLocation($transcode->song, $transcode->bit_rate);

        self::assertSame($transcode->location, $transcodedPath);
    }

    #[Test]
    public function retranscodeIfRecordIsInvalid(): void
    {
        $song = Song::factory()->create(['path' => '/path/to/song.flac']);

        $ulid = Ulid::freeze();

        /** @var Transcode $transcode */
        $transcode = Transcode::factory()->for($song)->create([
            'location' => '/path/to/transcode.m4a',
            'bit_rate' => 128,
            'hash' => 'mocked-checksum',
        ]);

        $destination = artifact_path("transcodes/128/$ulid.m4a", ensureDirectoryExists: false);

        File::expects('isReadable')->with('/path/to/transcode.m4a')->andReturn(false);
        File::expects('delete')->with('/path/to/transcode.m4a');
        File::expects('hash')->with($destination)->andReturn('mocked-checksum');
        File::expects('ensureDirectoryExists')->with(dirname($destination));

        $this->transcoder->expects('transcode')->with('/path/to/song.flac', $destination, 128);

        $transcodedLocation = $this->strategy->getTranscodeLocation($song, 128);

        self::assertSame($destination, $transcodedLocation);
        self::assertSame($transcode->refresh()->location, $transcodedLocation);
    }
}
