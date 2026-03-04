<?php

namespace App\Models\Concerns\Playlists;

use App\Models\Song as Playable;
use App\Models\User;
use Illuminate\Support\Collection;

trait ManagesPlayables
{
    /**
     * @param Collection|array<array-key, Playable>|Playable|array<string> $playables
     */
    public function addPlayables(Collection|Playable|array $playables, ?User $collaborator = null): void
    {
        $collaborator ??= $this->owner;
        $maxPosition = $this->playables()->getQuery()->max('position') ?? 0;

        if (!is_array($playables)) {
            $playables = Collection::wrap($playables)->pluck('id')->all();
        }

        $data = [];

        foreach ($playables as $playable) {
            $data[$playable] = [
                'position' => ++$maxPosition,
                'user_id' => $collaborator->id,
            ];
        }

        $this->playables()->attach($data);
    }

    /**
     * @param Collection<array-key, Playable>|Playable|array<string> $playables
     */
    public function removePlayables(Collection|Playable|array $playables): void
    {
        if (!is_array($playables)) {
            $playables = Collection::wrap($playables)->pluck('id')->all();
        }

        $this->playables()->detach($playables);
    }
}
