<?php

namespace Tests\Feature\KoelPlus;

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
use Tests\PlusTestCase;

use function Tests\create_user;
use function Tests\test_path;

class StreamEmbedTest extends PlusTestCase
{
    private static function makeStreamUrl(Song $song, bool $previewMode): string
    {
        $embed = Embed::factory()->for($song->owner)->embedding($song)->createOne();

        return URL::temporarySignedRoute('embeds.stream', now()->addDay(), [
            'embed' => $embed->id,
            'song' => $song->id,
            'options' => EmbedOptions::make(preview: $previewMode)->encrypt(),
        ]);
    }

    #[Test]
    public function streamThePreviewInPreviewMode(): void
    {
        $song = Song::factory()->for(create_user(), 'owner')->createOne(['storage' => SongStorageType::LOCAL]);
        Transcode::factory()->asPreview()->for($song)->createOne();

        $this
            ->mock(LocalTranscodingStrategy::class)
            ->expects('getPreviewLocation')
            ->with(Mockery::on(static fn (Song $streamedSong): bool => $streamedSong->is($song)))
            ->andReturn(test_path('songs/full.mp3'));

        $this->mock(LocalStreamerAdapter::class)->expects('stream')->never();

        $this->get(self::makeStreamUrl($song, previewMode: true))->assertSuccessful();
    }

    #[Test]
    public function streamTheOriginalOutsidePreviewMode(): void
    {
        $song = Song::factory()->for(create_user(), 'owner')->createOne(['storage' => SongStorageType::LOCAL]);
        Transcode::factory()->asPreview()->for($song)->createOne();

        $this->mock(LocalTranscodingStrategy::class)->expects('getPreviewLocation')->never();
        $this->mock(LocalStreamerAdapter::class)->expects('stream')->andReturn(response()->noContent());

        $this->get(self::makeStreamUrl($song, previewMode: false))->assertNoContent();
    }
}
