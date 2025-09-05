<?php

namespace App\Values\Ticketmaster;

use Carbon\Carbon;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Arr;

readonly class TicketmasterEvent implements Arrayable
{
    private function __construct(
        public string $id,
        public string $name,
        public string $url,
        public string $image,
        public ?string $start,
        public ?string $end,
        public TicketmasterVenue $venue,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new static(
            id: Arr::get($data, 'id', ''),
            name: Arr::get($data, 'name', ''),
            url: Arr::get($data, 'url', ''),
            image: Arr::get($data, 'images.0.url', ''),
            start: self::tryParseDate(Arr::get($data, 'dates.start', [])),
            end: self::tryParseDate(Arr::get($data, 'dates.end', [])),
            venue: TicketmasterVenue::fromArray(Arr::get($data, '_embedded.venues.0', [])),
        );
    }

    public static function make(
        string $id,
        string $name,
        string $url,
        string $image,
        ?string $start,
        ?string $end,
        TicketmasterVenue $venue,
    ): self {
        return new static(
            id: $id,
            name: $name,
            url: $url,
            image: $image,
            start: $start,
            end: $end,
            venue: $venue,
        );
    }

    private static function tryParseDate(array $dateData): ?string
    {
        if (!$dateData) {
            return null;
        }

        if (Arr::get($dateData, 'localTime')) {
            return Carbon::createFromFormat(
                'Y-m-d H:i:s',
                Arr::get($dateData, 'localDate') . ' ' . Arr::get($dateData, 'localTime')
            )->format('D, j M Y, H:i');
        }

        return Carbon::createFromFormat('Y-m-d', Arr::get($dateData, 'localDate'))->format('D, j M Y');
    }

    /** @inheritdoc */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'url' => $this->url,
            'image' => $this->image,
            'dates' => [
                'start' => $this->start,
                'end' => $this->end,
            ],
            'venue' => $this->venue->toArray(),
        ];
    }
}
