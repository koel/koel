<?php

namespace Tests\Unit\Services;

use App\Models\Song;
use App\Models\User;
use App\Services\Integrations\LastfmService;
use App\Services\Integrations\ListenbrainzService;
use App\Services\ScrobbleService;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_user;

class ScrobbleServiceTest extends TestCase
{
    private LastfmService|MockInterface $lastfm;
    private ListenbrainzService|MockInterface $listenbrainz;
    private ScrobbleService $service;
    private User $user;

    public function setUp(): void
    {
        parent::setUp();

        $this->lastfm = Mockery::mock(LastfmService::class);
        $this->listenbrainz = Mockery::mock(ListenbrainzService::class);
        $this->service = new ScrobbleService($this->lastfm, $this->listenbrainz);
        $this->user = create_user();
    }

    #[Test]
    public function scrobblesToEveryConnectedService(): void
    {
        $song = Song::factory()->make();
        $this->lastfm->allows('isConnected')->andReturnTrue();
        $this->listenbrainz->allows('isConnected')->andReturnTrue();

        $this->lastfm->expects('scrobble')->with($song, $this->user, 100);
        $this->listenbrainz->expects('scrobble')->with($song, $this->user, 100);

        $this->service->scrobble($song, $this->user, 100);
    }

    #[Test]
    public function skipsDisconnectedServices(): void
    {
        $song = Song::factory()->make();
        $this->lastfm->allows('isConnected')->andReturnFalse();
        $this->listenbrainz->allows('isConnected')->andReturnTrue();

        $this->lastfm->shouldNotReceive('scrobble');
        $this->listenbrainz->expects('scrobble')->with($song, $this->user, 100);

        $this->service->scrobble($song, $this->user, 100);
    }

    #[Test]
    public function updatesNowPlayingOnConnectedServicesOnly(): void
    {
        $song = Song::factory()->make();
        $this->lastfm->allows('isConnected')->andReturnFalse();
        $this->listenbrainz->allows('isConnected')->andReturnTrue();

        $this->lastfm->shouldNotReceive('updateNowPlaying');
        $this->listenbrainz->expects('updateNowPlaying')->with($song, $this->user);

        $this->service->updateNowPlaying($song, $this->user);
    }

    #[Test]
    public function hasConnectedScrobbler(): void
    {
        $this->lastfm->allows('isConnected')->andReturnFalse();
        $this->listenbrainz->allows('isConnected')->andReturnTrue();

        self::assertTrue($this->service->hasConnectedScrobbler($this->user));
    }

    #[Test]
    public function hasNoConnectedScrobbler(): void
    {
        $this->lastfm->allows('isConnected')->andReturnFalse();
        $this->listenbrainz->allows('isConnected')->andReturnFalse();

        self::assertFalse($this->service->hasConnectedScrobbler($this->user));
    }
}
