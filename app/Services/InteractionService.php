<?php

namespace App\Services;

use App\Models\Interaction;
use App\Models\Song as Playable;
use App\Models\User;

class InteractionService
{
    public function increasePlayCount(Playable $playable, User $user): Interaction
    {
        return tap(Interaction::query()->firstOrCreate([
            'song_id' => $playable->id,
            'user_id' => $user->id,
        ]), static function (Interaction $interaction): void {
            $interaction->last_played_at = now();

            ++$interaction->play_count;
            $interaction->save();
        });
    }
}
