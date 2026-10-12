<?php

namespace App\Http\Integrations\MusicBrainz;

use App\Http\Integrations\Concerns\OnlyThrowsOnServerErrorsAndRateLimits;
use App\Services\Integrations\MusicBrainzRateLimiter;
use App\Services\Integrations\MusicBrainzService;
use Illuminate\Http\Response as HttpResponse;
use Saloon\Http\Connector;
use Saloon\Http\PendingRequest;
use Saloon\Http\Response;
use Saloon\Traits\Plugins\AcceptsJson;

class MusicBrainzConnector extends Connector
{
    use AcceptsJson;
    use OnlyThrowsOnServerErrorsAndRateLimits;

    public function __construct(
        private readonly MusicBrainzRateLimiter $rateLimiter,
    ) {}

    public function resolveBaseUrl(): string
    {
        return config('koel.services.musicbrainz.endpoint');
    }

    /** @inheritdoc */
    public function defaultHeaders(): array
    {
        return [
            'Accept' => 'application/json',
            'User-Agent' => MusicBrainzService::userAgent(),
        ];
    }

    public function boot(PendingRequest $pendingRequest): void
    {
        $this->rateLimiter->takeRequestSlot();

        $pendingRequest
            ->middleware()
            ->onResponse(function (Response $response): void {
                if (in_array(
                    $response->status(),
                    [HttpResponse::HTTP_SERVICE_UNAVAILABLE, HttpResponse::HTTP_TOO_MANY_REQUESTS],
                    true,
                )) {
                    $this->rateLimiter->backOff();
                }
            });
    }
}
