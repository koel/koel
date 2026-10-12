<?php

namespace App\Http\Integrations\iTunes\Requests;

use Saloon\Enums\Method;
use Saloon\Http\Request;

class LookupPodcastRequest extends Request
{
    protected Method $method = Method::GET;

    public function __construct(
        private readonly string $applePodcastsId,
    ) {}

    /** @inheritdoc */
    protected function defaultQuery(): array
    {
        return [
            'id' => $this->applePodcastsId,
            'entity' => 'podcast',
        ];
    }

    public function resolveEndpoint(): string
    {
        return '/lookup';
    }
}
