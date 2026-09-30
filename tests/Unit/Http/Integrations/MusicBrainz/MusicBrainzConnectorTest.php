<?php

namespace Tests\Unit\Http\Integrations\MusicBrainz;

use App\Exceptions\MusicBrainzBusyException;
use App\Http\Integrations\MusicBrainz\MusicBrainzConnector;
use App\Http\Integrations\MusicBrainz\Requests\SearchForArtistRequest;
use App\Services\Integrations\MusicBrainzRateLimiter;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Saloon\Exceptions\Request\RequestException;
use Saloon\Http\Faking\MockResponse;
use Saloon\Laravel\Facades\Saloon;
use Tests\TestCase;

class MusicBrainzConnectorTest extends TestCase
{
    #[Test]
    public function takeASlotBeforeEveryRequest(): void
    {
        Saloon::fake([SearchForArtistRequest::class => MockResponse::make(body: ['artists' => []])]);
        $rateLimiter = Mockery::mock(MusicBrainzRateLimiter::class);
        $rateLimiter->expects('takeRequestSlot');

        (new MusicBrainzConnector($rateLimiter))->send(new SearchForArtistRequest('Skid Row'));
    }

    #[Test]
    public function sendNothingWhenNoSlotIsFree(): void
    {
        Saloon::fake([SearchForArtistRequest::class => MockResponse::make(body: ['artists' => []])]);
        $rateLimiter = Mockery::mock(MusicBrainzRateLimiter::class);
        $rateLimiter->expects('takeRequestSlot')->andThrow(MusicBrainzBusyException::create());

        try {
            (new MusicBrainzConnector($rateLimiter))->send(new SearchForArtistRequest('Skid Row'));
            self::fail('The request went out without a slot.');
        } catch (MusicBrainzBusyException) {
            Saloon::assertNothingSent();
        }
    }

    #[Test]
    public function backOffWhenMusicBrainzRefusesTheRequest(): void
    {
        Saloon::fake([SearchForArtistRequest::class => MockResponse::make(status: 503)]);
        $rateLimiter = Mockery::mock(MusicBrainzRateLimiter::class);
        $rateLimiter->allows('takeRequestSlot');
        $rateLimiter->expects('backOff');

        $this->expectException(RequestException::class);

        (new MusicBrainzConnector($rateLimiter))->send(new SearchForArtistRequest('Skid Row'));
    }
}
