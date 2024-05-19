<?php

namespace App\Values\Podcast;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

final class PodcastState implements Arrayable, Jsonable
{
    private function __construct(public readonly ?string $currentEpisode, public readonly Collection $progresses)
    {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            Arr::get($data, 'current_episode'),
            new Collection(Arr::get($data, 'progresses', []))
        );
    }

    /** @inheritDoc */
    public function toArray(): array
    {
        return [
            'current_episode' => $this->currentEpisode,
            'progresses' => $this->progresses->toArray(),
        ];
    }

    /** @inheritDoc */
    public function toJson($options = 0): string
    {
        return json_encode($this->toArray(), $options);
    }
}
