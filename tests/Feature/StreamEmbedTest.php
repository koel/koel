<?php

namespace Tests\Feature;

use App\Enums\SongStorageType;
use App\Models\Embed;
use App\Models\Song;
use App\Models\Transcode;
use App\Services\Streamer\Adapters\LocalStreamerAdapter;
use App\Services\Transcoding\LocalTranscodingStrategy;
use App\Values\EmbedOptions;
use Illuminate\Support\Facades\URL;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_user;
use function Tests\test_path;

class StreamEmbedTest extends TestCase
{
    private static function makeStreamUrl(Song $song): string
    {
        $embed = Embed::factory()->for($song->owner)->for($song, 'embeddable')->createOne();

        return URL::temporarySignedRoute('embeds.stream', now()->addDay(), [
            'embed' => $embed->id,
            'song' => $song->id,
            'options' => EmbedOptions::make()->encrypt(),
        ]);
    }

    #[Test]
    public function streamThePreviewWhenTheSongHasOne(): void
    {
        $song = Song::factory()->for(create_user(), 'owner')->createOne(['storage' => SongStorageType::LOCAL]);
        Transcode::factory()->asPreview()->for($song)->createOne();

        $this
            ->mock(LocalTranscodingStrategy::class)
            ->expects('getPreviewLocation')
            ->with(Mockery::on(static fn (Song $streamedSong): bool => $streamedSong->is($song)))
            ->andReturn(test_path('songs/full.mp3'));

        $this->mock(LocalStreamerAdapter::class)->expects('stream')->never();

        $this->get(self::makeStreamUrl($song))->assertSuccessful();
    }

    #[Test]
    public function streamTheOriginalWhenTheSongHasNoPreview(): void
    {
        $song = Song::factory()->for(create_user(), 'owner')->createOne(['storage' => SongStorageType::LOCAL]);
        Transcode::factory()->for($song)->createOne();

        $this->mock(LocalTranscodingStrategy::class)->expects('getPreviewLocation')->never();
        $this->mock(LocalStreamerAdapter::class)->expects('stream')->andReturn(response()->noContent());

        $this->get(self::makeStreamUrl($song))->assertNoContent();
    }
}
