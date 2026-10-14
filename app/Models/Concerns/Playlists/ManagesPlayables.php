<?php

namespace App\Models\Concerns\Playlists;

use App\Models\Song as Playable;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

trait ManagesPlayables
{
    /**
     * @param Collection|array<array-key, Playable>|Playable|array<string> $playables
     */
    public function addPlayables(Collection|Playable|array $playables, ?User $collaborator = null): void
    {
        $collaborator ??= $this->owner;

        if (!is_array($playables)) {
            $playables = Collection::wrap($playables)->pluck('id')->all();
        }

        DB::transaction(function () use ($playables, $collaborator): void {
            $maxPosition = $this->playables()->newPivotQuery()->max('position') ?? 0;

            foreach (array_chunk($playables, 1000) as $chunk) {
                $data = [];

                foreach ($chunk as $playable) {
                    $data[$playable] = [
                        'position' => ++$maxPosition,
                        'user_id' => $collaborator->id,
                    ];
                }

                $this->playables()->attach($data);
            }
        });
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
