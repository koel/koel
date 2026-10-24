<?php

namespace Tests\Feature;

use App\Models\RadioStation;
use App\Models\Song;
use App\Services\DownloadService;
use App\Services\Streamer\Adapters\LocalStreamerAdapter;
use App\Services\Streamer\Adapters\TranscodingStreamerAdapter;
use App\Services\Streamer\AudioUrlSigner;
use App\Values\Downloadable;
use Illuminate\Http\Response;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_user;
use function Tests\test_path;

class SignedAudioUrlTest extends TestCase
{
    private AudioUrlSigner $signer;

    public function setUp(): void
    {
        parent::setUp();

        $this->signer = app(AudioUrlSigner::class);

        ob_start();
    }

    protected function tearDown(): void
    {
        ob_end_clean();

        parent::tearDown();
    }

    #[Test]
    public function playWithSignedUrl(): void
    {
        $song = Song::factory()->createOne(['path' => test_path('songs/blank.mp3')]);
        $url = $this->signer->signSongPlayUrl($song, create_user(), transcode: false);

        $this->get($url, ['Range' => 'bytes=0-1'])->assertStatus(Response::HTTP_PARTIAL_CONTENT);
    }

    #[Test]
    public function rejectSignedUrlOfUserPendingDeletion(): void
    {
        $song = Song::factory()->createOne(['path' => test_path('songs/blank.mp3')]);
        $user = create_user(['deletion_requested_at' => now()]);

        $this->get($this->signer->signSongPlayUrl($song, $user, transcode: false))->assertUnauthorized();
    }

    #[Test]
    public function rejectSignedUrlEditedToAnotherSong(): void
    {
        $song = Song::factory()->createOne();
        $otherSong = Song::factory()->createOne();
        $url = $this->signer->signSongPlayUrl($song, create_user(), transcode: false);

        $this->get(str_replace($song->id, $otherSong->id, $url))->assertUnauthorized();
    }

    #[Test]
    public function keepSignedUrlValidForTheWholeSongPlusHalfAnHour(): void
    {
        $song = Song::factory()->createOne(['path' => test_path('songs/blank.mp3'), 'length' => 60 * 60]);
        $url = $this->signer->signSongPlayUrl($song, create_user(), transcode: false);

        $this->travel(89)->minutes();

        $this->get($url, ['Range' => 'bytes=0-1'])->assertStatus(Response::HTTP_PARTIAL_CONTENT);
    }

    #[Test]
    public function rejectExpiredSignedUrl(): void
    {
        $song = Song::factory()->createOne(['length' => 60 * 60]);
        $url = $this->signer->signSongPlayUrl($song, create_user(), transcode: false);

        $this->travel(91)->minutes();

        $this->get($url)->assertUnauthorized();
    }

    #[Test]
    public function downloadWithSignedUrl(): void
    {
        $song = Song::factory()->createOne();

        $this
            ->mock(DownloadService::class)
            ->expects('getDownloadable')
            ->andReturn(Downloadable::make(test_path('songs/blank.mp3')));

        $this->get($this->signer->signSongDownloadUrl($song, create_user()))->assertOk();
    }

    #[Test]
    public function fetchSongPlayUrl(): void
    {
        $song = Song::factory()->createOne();

        $url = $this->getAs("api/songs/{$song->id}/play-url?transcode=1")->assertOk()->json('url');

        self::assertStringContainsString("play/{$song->id}/1?", $url);
        self::assertStringContainsString('signature=', $url);
    }

    #[Test]
    public function playLosslessFileAsIsWhenTheClientSupportsItsCodec(): void
    {
        config(['koel.streaming.transcode_flac' => true]);
        $user = create_user();
        $song = Song::factory()->for($user, 'owner')->createOne([
            'path' => test_path('songs/blank.mp3'),
            'mime_type' => 'audio/flac',
        ]);

        $url = $this
            ->getAs("api/songs/{$song->id}/play-url?lossless[]=flac&lossless[]=alac", $user)
            ->assertOk()
            ->json('url');

        $this->mock(TranscodingStreamerAdapter::class)->expects('stream')->never();
        $this->mock(LocalStreamerAdapter::class)->expects('stream');

        $this->get($url)->assertOk();

        config(['koel.streaming.transcode_flac' => false]);
    }

    #[Test]
    public function fetchSongDownloadUrl(): void
    {
        $song = Song::factory()->createOne();

        $url = $this->getAs("api/songs/{$song->id}/download-url")->assertOk()->json('url');

        self::assertStringContainsString('download/songs?', $url);
        self::assertStringContainsString('signature=', $url);
    }

    #[Test]
    public function fetchRadioStationStreamUrl(): void
    {
        $user = create_user();
        $station = RadioStation::factory()->for($user)->createOne();

        $url = $this->getAs("api/radio/stations/{$station->id}/stream-url", $user)->assertOk()->json('url');

        self::assertStringContainsString("radio/stream/{$station->id}?", $url);
        self::assertStringContainsString('signature=', $url);
    }

    #[Test]
    public function cannotFetchStreamUrlForInaccessibleRadioStation(): void
    {
        $station = RadioStation::factory()->createOne(['is_public' => false]);

        $this->getAs("api/radio/stations/{$station->id}/stream-url")->assertForbidden();
    }
}
