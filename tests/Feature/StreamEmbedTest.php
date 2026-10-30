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
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_user;

class StreamEmbedTest extends TestCase
{
    private static function makeStreamUrl(Song $song, bool $previewMode = false): string
    {
        $embed = Embed::factory()->for($song->owner)->embedding($song)->createOne();

        return URL::temporarySignedRoute('embeds.stream', now()->addDay(), [
            'embed' => $embed->id,
            'song' => $song->id,
            'options' => EmbedOptions::make(preview: $previewMode)->encrypt(),
        ]);
    }

    #[Test]
    public function streamTheOriginalOnCommunityEvenInPreviewMode(): void
    {
        $song = Song::factory()->for(create_user(), 'owner')->createOne(['storage' => SongStorageType::LOCAL]);
        Transcode::factory()->asPreview()->for($song)->createOne();

        $this->mock(LocalTranscodingStrategy::class)->expects('getPreviewLocation')->never();
        $this->mock(LocalStreamerAdapter::class)->expects('stream')->andReturn(response()->noContent());

        $this->get(self::makeStreamUrl($song, previewMode: true))->assertNoContent();
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
