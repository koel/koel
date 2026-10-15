<?php

namespace App\Values\Ticketmaster;

use Illuminate\Support\Collection;

final readonly class ArtistEventSearchResult
{
    /** @param Collection<int, TicketmasterEvent> $events */
    private function __construct(
        public Collection $events,
        public bool $nearby,
    ) {}

    /** @param Collection<int, TicketmasterEvent> $events */
    public static function make(Collection $events, bool $nearby): self
    {
        return new self(events: $events, nearby: $nearby);
    }
}
