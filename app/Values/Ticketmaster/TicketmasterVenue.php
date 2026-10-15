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
        public string $country,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            name: Arr::get($data, 'name', ''),
            url: Arr::get($data, 'url', ''),
            city: Arr::get($data, 'city.name', ''),
            country: Arr::get($data, 'country.name', ''),
        );
    }

    public static function make(string $name, string $url, string $city, string $country = ''): self
    {
        return new static(name: $name, url: $url, city: $city, country: $country);
    }

    /** @inheritdoc */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'url' => $this->url,
            'city' => $this->city,
            'country' => $this->country,
        ];
    }
}
