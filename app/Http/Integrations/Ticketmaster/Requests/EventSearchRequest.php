<?php

namespace App\Http\Integrations\Ticketmaster\Requests;

use App\Values\Ticketmaster\TicketmasterEvent;
use Illuminate\Support\Collection;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Http\Response;

class EventSearchRequest extends Request
{
    protected Method $method = Method::GET;

    public function __construct(
        private readonly string $attractionId,
        private readonly string $countryCode,
    ) {
    }

    public function resolveEndpoint(): string
    {
        return '/events.json';
    }

    /** @inheritdoc */
    protected function defaultQuery(): array
    {
        return [
            'attractionId' => $this->attractionId,
            'countryCode' => $this->countryCode,
            'classificationName' => ['Music'],
        ];
    }

    /** @return Collection<TicketmasterEvent>|array<array-key, TicketmasterEvent> */
    public function createDtoFromResponse(Response $response): Collection
    {
        return collect($response->json('_embedded.events', []))
            ->map(static fn (array $data) => TicketmasterEvent::fromArray($data));
    }
}
