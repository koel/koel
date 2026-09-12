<?php

namespace Tests\Integration\Services\Integrations;

use App\Http\Integrations\ListenBrainz\Requests\SubmitListensRequest;
use App\Http\Integrations\ListenBrainz\Requests\ValidateTokenRequest;
use App\Models\Album;
use App\Models\Artist;
use App\Models\Song;
use App\Services\Integrations\ListenBrainzService;
use PHPUnit\Framework\Attributes\Test;
use Saloon\Http\Faking\MockResponse;
use Saloon\Laravel\Saloon;
use Tests\TestCase;

use function Tests\create_user;

class ListenBrainzServiceTest extends TestCase
{
    private ListenBrainzService $service;

    public function setUp(): void
    {
        parent::setUp();

        $this->service = app(ListenBrainzService::class);
    }

    #[Test]
    public function scrobble(): void
    {
        $user = create_user(['preferences' => ['listenbrainz_token' => 'my_token']]);
        $artist = Artist::factory()->createOne(['name' => 'Nick Drake']);
        $album = Album::factory()->for($artist)->createOne(['name' => 'Pink Moon']);
        $song = Song::factory()
            ->for($artist)
            ->for($album)
            ->createOne([
                'title' => 'Pink Moon',
                'length' => 130.5,
                'track' => 1,
            ]);

        Saloon::fake([SubmitListensRequest::class => MockResponse::make()]);

        $this->service->scrobble($song, $user, 100);

        Saloon::assertSent(static function (SubmitListensRequest $request): bool {
            self::assertSame(
                [
                    'listen_type' => 'single',
                    'payload' => [
                        [
                            'track_metadata' => [
                                'artist_name' => 'Nick Drake',
                                'track_name' => 'Pink Moon',
                                'additional_info' => [
                                    'duration_ms' => 130_500,
                                    'tracknumber' => 1,
                                    'media_player' => 'Koel',
                                    'submission_client' => 'Koel',
                                ],
                                'release_name' => 'Pink Moon',
                            ],
                            'listened_at' => 100,
                        ],
                    ],
                ],
                $request->body()->all(),
            );

            self::assertSame('Token my_token', $request->headers()->get('Authorization'));

            return true;
        });
    }

    #[Test]
    public function scrobbleOmitsUnknownAlbum(): void
    {
        $user = create_user(['preferences' => ['listenbrainz_token' => 'my_token']]);
        $album = Album::factory()->createOne(['name' => Album::UNKNOWN_NAME]);
        $song = Song::factory()->for($album)->createOne();

        Saloon::fake([SubmitListensRequest::class => MockResponse::make()]);

        $this->service->scrobble($song, $user, 100);

        Saloon::assertSent(static function (SubmitListensRequest $request): bool {
            self::assertArrayNotHasKey('release_name', $request->body()->all()['payload'][0]['track_metadata']);

            return true;
        });
    }

    #[Test]
    public function scrobbleSubmitsMusicBrainzIdentifiers(): void
    {
        $user = create_user(['preferences' => ['listenbrainz_token' => 'my_token']]);
        $artist = Artist::factory()->createOne(['mbid' => 'c1a1b1cc-1111-4aaa-8bbb-0d0d0d0d0d0d']);
        $album = Album::factory()->for($artist)->createOne(['mbid' => 'd2b2c2dd-2222-4bbb-8ccc-1e1e1e1e1e1e']);
        $song = Song::factory()
            ->for($artist)
            ->for($album)
            ->createOne(['mbid' => 'e3c3d3ee-3333-4ccc-8ddd-2f2f2f2f2f2f']);

        Saloon::fake([SubmitListensRequest::class => MockResponse::make()]);

        $this->service->scrobble($song, $user, 100);

        Saloon::assertSent(static function (SubmitListensRequest $request) use ($song, $album, $artist): bool {
            $additionalInfo = $request->body()->all()['payload'][0]['track_metadata']['additional_info'];

            self::assertSame($song->mbid, $additionalInfo['recording_mbid']);
            self::assertSame($album->mbid, $additionalInfo['release_mbid']);
            self::assertSame([$artist->mbid], $additionalInfo['artist_mbids']);

            return true;
        });
    }

    #[Test]
    public function scrobbleOmitsMissingMusicBrainzIdentifiers(): void
    {
        $user = create_user(['preferences' => ['listenbrainz_token' => 'my_token']]);
        $artist = Artist::factory()->createOne(['mbid' => null]);
        $album = Album::factory()->for($artist)->createOne(['mbid' => null]);
        $song = Song::factory()->for($artist)->for($album)->createOne(['mbid' => null]);

        Saloon::fake([SubmitListensRequest::class => MockResponse::make()]);

        $this->service->scrobble($song, $user, 100);

        Saloon::assertSent(static function (SubmitListensRequest $request): bool {
            $additionalInfo = $request->body()->all()['payload'][0]['track_metadata']['additional_info'];

            self::assertArrayNotHasKey('recording_mbid', $additionalInfo);
            self::assertArrayNotHasKey('release_mbid', $additionalInfo);
            self::assertArrayNotHasKey('artist_mbids', $additionalInfo);

            return true;
        });
    }

    #[Test]
    public function updateNowPlayingOmitsTimestamp(): void
    {
        $user = create_user(['preferences' => ['listenbrainz_token' => 'my_token']]);
        $song = Song::factory()->createOne();

        Saloon::fake([SubmitListensRequest::class => MockResponse::make()]);

        $this->service->updateNowPlaying($song, $user);

        Saloon::assertSent(static function (SubmitListensRequest $request): bool {
            $body = $request->body()->all();

            self::assertSame('playing_now', $body['listen_type']);
            self::assertArrayNotHasKey('listened_at', $body['payload'][0]);

            return true;
        });
    }

    #[Test]
    public function validateToken(): void
    {
        Saloon::fake([ValidateTokenRequest::class => MockResponse::make(['valid' => true])]);

        self::assertTrue($this->service->validateToken('good_token'));
    }

    #[Test]
    public function invalidTokenIsRejected(): void
    {
        Saloon::fake([ValidateTokenRequest::class => MockResponse::make(['valid' => false], 200)]);

        self::assertFalse($this->service->validateToken('bad_token'));
    }

    #[Test]
    public function unreachableServiceDoesNotValidateToken(): void
    {
        Saloon::fake([ValidateTokenRequest::class => MockResponse::make([], 500)]);

        self::assertFalse($this->service->validateToken('any_token'));
    }

    #[Test]
    public function isConnected(): void
    {
        self::assertTrue($this->service->isConnected(create_user(['preferences' => [
            'listenbrainz_token' => 'my_token',
        ]])));

        self::assertFalse($this->service->isConnected(create_user()));
    }

    #[Test]
    public function setUserToken(): void
    {
        $user = create_user();

        $this->service->setUserToken($user, 'my_token');
        self::assertSame('my_token', $user->refresh()->preferences->listenBrainzToken);

        $this->service->setUserToken($user, null);
        self::assertNull($user->refresh()->preferences->listenBrainzToken);
    }
}
