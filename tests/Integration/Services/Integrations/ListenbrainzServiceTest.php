<?php

namespace Tests\Integration\Services\Integrations;

use App\Http\Integrations\Listenbrainz\Requests\SubmitListensRequest;
use App\Http\Integrations\Listenbrainz\Requests\ValidateTokenRequest;
use App\Models\Album;
use App\Models\Artist;
use App\Models\Song;
use App\Services\Integrations\ListenbrainzService;
use PHPUnit\Framework\Attributes\Test;
use Saloon\Http\Faking\MockResponse;
use Saloon\Laravel\Saloon;
use Tests\TestCase;

use function Tests\create_user;

class ListenbrainzServiceTest extends TestCase
{
    private ListenbrainzService $service;

    public function setUp(): void
    {
        parent::setUp();

        $this->service = app(ListenbrainzService::class);
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
