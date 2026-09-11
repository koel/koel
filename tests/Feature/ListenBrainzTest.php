<?php

namespace Tests\Feature;

use App\Http\Integrations\ListenBrainz\Requests\ValidateTokenRequest;
use PHPUnit\Framework\Attributes\Test;
use Saloon\Http\Faking\MockResponse;
use Saloon\Laravel\Saloon;
use Tests\TestCase;

use function Tests\create_user;

class ListenBrainzTest extends TestCase
{
    #[Test]
    public function setToken(): void
    {
        Saloon::fake([ValidateTokenRequest::class => MockResponse::make(['valid' => true])]);
        $user = create_user();

        $this->postAs('api/listenbrainz/token', ['token' => 'my_token'], $user)->assertNoContent();

        self::assertSame('my_token', $user->refresh()->preferences->listenBrainzToken);
    }

    #[Test]
    public function tokenRejectedByListenBrainzIsNotStored(): void
    {
        Saloon::fake([ValidateTokenRequest::class => MockResponse::make(['valid' => false])]);
        $user = create_user();

        $this
            ->postAs('api/listenbrainz/token', ['token' => 'bad_token'], $user)
            ->assertUnprocessable()
            ->assertJsonValidationErrorFor('token');

        self::assertNull($user->refresh()->preferences->listenBrainzToken);
    }

    #[Test]
    public function tokenIsRequired(): void
    {
        $this
            ->postAs('api/listenbrainz/token', [], create_user())
            ->assertUnprocessable()
            ->assertJsonValidationErrorFor('token');
    }

    #[Test]
    public function disconnect(): void
    {
        $user = create_user(['preferences' => ['listenbrainz_token' => 'my_token']]);

        $this->deleteAs('api/listenbrainz/token', [], $user)->assertNoContent();

        self::assertNull($user->refresh()->preferences->listenBrainzToken);
    }

    #[Test]
    public function unauthenticatedRequestsAreRejected(): void
    {
        $this->postJson('api/listenbrainz/token', ['token' => 'my_token'])->assertUnauthorized();
        $this->deleteJson('api/listenbrainz/token')->assertUnauthorized();
    }
}
