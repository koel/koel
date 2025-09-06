<?php

namespace App\Http\Resources;

use App\Values\Ticketmaster\TicketmasterEvent;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LiveEventResource extends JsonResource
{
    public const JSON_STRUCTURE = [
        'type',
        'id',
        'name',
        'dates' => [
            'start',
            'end',
        ],
        'venue' => [
            'name',
            'url',
            'city',
        ],
        'url',
        'image',
    ];

    // Right now we only have Ticketmaster events, so we keep it simple.
    public function __construct(private readonly TicketmasterEvent $event)
    {
        parent::__construct($event);
    }

    /** @inheritdoc */
    public function toArray(Request $request): array
    {
        $data = $this->event->toArray();
        $data['type'] = 'live-events';

        return $data;
    }
}
