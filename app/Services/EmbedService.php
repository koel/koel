<?php

namespace App\Services;

use App\Models\Album;
use App\Models\Artist;
use App\Models\Contracts\Embeddable;
use App\Models\Embed;
use App\Models\Playlist;
use App\Models\Song;
use App\Models\User;
use App\Models\UserFavorites;

class EmbedService
{
    /** @param Album|Artist|Playlist|Song|UserFavorites $embeddable */
    public function resolveEmbedForEmbeddable(Embeddable $embeddable, User $user): Embed
    {
        return $embeddable
            ->embeds()
            ->firstOrCreate([
                'embeddable_id' => $embeddable->getKey(),
                'embeddable_type' => $embeddable->getMorphClass(),
                'user_id' => $user->id,
            ]);
    }
}
