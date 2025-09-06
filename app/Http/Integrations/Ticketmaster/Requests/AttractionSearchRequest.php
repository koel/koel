<?php

namespace App\Http\Integrations\Ticketmaster\Requests;

use App\Values\Ticketmaster\TicketmasterAttraction;
use Illuminate\Support\Collection;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Http\Response;

class AttractionSearchRequest extends Request
{
    protected Method $method = Method::GET;

    public function __construct(private readonly string $keywords)
    {
    }

    public function resolveEndpoint(): string
    {
        return '/attractions.json';
    }

    /** @inheritdoc */
    protected function defaultQuery(): array
    {
        return [
            'keyword' => $this->keywords,
            'size' => 5,
            'classificationName' => ['Music'],
        ];
    }

    /** @return Collection<TicketmasterAttraction>|array<array-key, TicketmasterAttraction> */
    public function createDtoFromResponse(Response $response): Collection
    {
        return collect($response->json('_embedded.attractions', []))
            ->map(static fn (array $data) => TicketmasterAttraction::fromArray($data));
    }
}
