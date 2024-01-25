<?php

namespace App\Http\Resources;

use App\Values\QueueState;
use Illuminate\Http\Resources\Json\JsonResource;

class QueueStateResource extends JsonResource
{
    public const JSON_STRUCTURE = [
        'songs' => [
            SongResource::JSON_STRUCTURE,
        ],
        'current_song',
        'playback_position',
    ];

    public function __construct(private QueueState $state)
    {
        parent::__construct($state);
    }

    /** @return array<mixed> */
    public function toArray($request): array
    {
        return [
            'type' => 'queue-states',
            'songs' => SongResource::collection($this->state->songs),
            'current_song' => $this->state->currentSong ? new SongResource($this->state->currentSong) : null,
            'playback_position' => $this->state->playbackPosition,
        ];
    }
}
