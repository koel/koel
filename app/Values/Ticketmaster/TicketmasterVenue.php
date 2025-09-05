<?php

namespace App\Values\Ticketmaster;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Arr;

readonly class TicketmasterVenue implements Arrayable
{
    private function __construct(
        public string $name,
        public string $url,
        public string $city,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            name: Arr::get($data, 'name', ''),
            url: Arr::get($data, 'url', ''),
            city: Arr::get($data, 'city.name', ''),
        );
    }

    public static function make(string $name, string $url, string $city): self
    {
        return new static(
            name: $name,
            url: $url,
            city: $city,
        );
    }

    /** @inheritdoc */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'url' => $this->url,
            'city' => $this->city,
        ];
    }
}
