<?php

namespace Tests\Feature;

use App\Http\Integrations\Lastfm\Requests\GetSessionKeyRequest;
use App\Models\User;
use Illuminate\Http\Response;
use Illuminate\Support\Uri;
use PHPUnit\Framework\Attributes\Test;
use Saloon\Http\Faking\MockResponse;
use Saloon\Laravel\Saloon;
use Tests\TestCase;

use function Tests\create_user;

class LastfmTest extends TestCase
{
    #[Test]
    public function setSessionKey(): void
    {
        $user = create_user();
        $this->postAs('api/lastfm/session-key', ['key' => 'foo'], $user)->assertNoContent();

        static::assertSame('foo', $user->refresh()->preferences->lastFmSessionKey);
    }

    #[Test]
    public function getAuthorizationUrl(): void
    {
        $user = create_user();
        $url = $this->getAs('api/lastfm/authorization-url', $user)->assertOk()->json('url');

        $authorizationUri = Uri::of($url);
        $callbackUri = Uri::of($authorizationUri->query()->get('cb'));

        self::assertSame('https://www.last.fm/api/auth/', $authorizationUri->withQuery([], false)->value());
        self::assertSame('foo', $authorizationUri->query()->get('api_key'));
        self::assertSame('lastfm/callback', $callbackUri->path());
        self::assertSame(['state'], array_keys($callbackUri->query()->all()));
        self::assertSame(1, $user->tokens()->count());
    }

    #[Test]
    public function getAuthorizationUrlWhenLastfmIsNotConfigured(): void
    {
        config(['koel.services.lastfm.secret' => null]);

        $this->getAs('api/lastfm/authorization-url')->assertStatus(Response::HTTP_NOT_IMPLEMENTED);
    }

    #[Test]
    public function callbackStoresSessionKeyForTheUserWhoStartedTheFlow(): void
    {
        $user = create_user();
        $state = $this->startConnectFlow($user);

        self::fakeSessionKeyExchange('my-session-key');

        $this->get("lastfm/callback?token=lastfm-token&state=$state")->assertOk();

        self::assertSame('my-session-key', $user->refresh()->preferences->lastFmSessionKey);
    }

    #[Test]
    public function callbackRejectsUnknownState(): void
    {
        $this->get('lastfm/callback?token=lastfm-token&state=unknown')->assertForbidden();
    }

    #[Test]
    public function callbackRejectsReusedState(): void
    {
        $state = $this->startConnectFlow(create_user());

        self::fakeSessionKeyExchange('my-session-key');

        $this->get("lastfm/callback?token=lastfm-token&state=$state")->assertOk();
        $this->get("lastfm/callback?token=lastfm-token&state=$state")->assertForbidden();
    }

    #[Test]
    public function callbackRejectsExpiredState(): void
    {
        $state = $this->startConnectFlow(create_user());

        $this->travel(11)->minutes();

        $this->get("lastfm/callback?token=lastfm-token&state=$state")->assertForbidden();
    }

    #[Test]
    public function failedSessionKeyExchangeStillConsumesState(): void
    {
        $user = create_user(['preferences' => ['lastfm_session_key' => null]]);
        $state = $this->startConnectFlow($user);

        self::fakeSessionKeyExchange(null);

        $this->get("lastfm/callback?token=bad-token&state=$state")->assertServerError();
        $this->get("lastfm/callback?token=bad-token&state=$state")->assertForbidden();

        self::assertNull($user->refresh()->preferences->lastFmSessionKey);
    }

    #[Test]
    public function disconnectUser(): void
    {
        $user = create_user();
        static::assertNotNull($user->preferences->lastFmSessionKey);

        $this->deleteAs('api/lastfm/disconnect', [], $user);

        $user->refresh();
        static::assertNull($user->preferences->lastFmSessionKey);
    }

    private function startConnectFlow(User $user): string
    {
        $url = $this->getAs('api/lastfm/authorization-url', $user)->json('url');

        return Uri::of(Uri::of($url)->query()->get('cb'))->query()->get('state');
    }

    private static function fakeSessionKeyExchange(?string $sessionKey): void
    {
        $body = $sessionKey ? ['session' => ['key' => $sessionKey]] : ['error' => 4, 'message' => 'Invalid token'];

        Saloon::fake([GetSessionKeyRequest::class => MockResponse::make($body)]);
    }
}
