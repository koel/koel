<?php

namespace App\Values\Ticketmaster;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Arr;

readonly class TicketmasterAttraction implements Arrayable
{
    private function __construct(
        public string $id,
        public string $name,
        public string $url,
        public string $image,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            id: Arr::get($data, 'id'),
            name: Arr::get($data, 'name'),
            url: Arr::get($data, 'url', ''),
            image: Arr::get($data, 'images.0.url', ''),
        );
    }

    /** @inheritdoc */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'url' => $this->url,
            'image' => $this->image,
        ];
    }
}
