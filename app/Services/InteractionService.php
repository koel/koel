<?php

namespace App\Services;

use App\Models\Interaction;
use App\Models\Play;
use App\Models\Song as Playable;
use App\Models\User;

class InteractionService
{
    public function increasePlayCount(Playable $playable, User $user): Interaction
    {
        if (!$playable->isEpisode()) {
            Play::query()->create([
                'user_id' => $user->id,
                'song_id' => $playable->id,
                'played_at' => now(),
            ]);
        }

        return tap(
            Interaction::query()->firstOrCreate([
                'song_id' => $playable->id,
                'user_id' => $user->id,
            ]),
            static function (Interaction $interaction): void {
                $interaction->last_played_at = now();

                ++$interaction->play_count;
                $interaction->save();
            },
        );
    }
}
