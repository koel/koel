<?php

namespace App\Builders;

use App\Facades\License;
use App\Models\Artist;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\JoinClause;

class ArtistBuilder extends Builder
{
    public function isStandard(): self
    {
        return $this->whereNotIn('artists.id', [Artist::UNKNOWN_ID, Artist::VARIOUS_ID]);
    }

    public function accessibleBy(User $user): self
    {
        if (License::isCommunity()) {
            // With the Community license, all artists are accessible by all users.
            return $this;
        }

        return $this->join('songs', static function (JoinClause $join) use ($user): void {
            $join->on('artists.id', 'songs.artist_id')
                ->where(static function (JoinClause $query) use ($user): void {
                    $query->where('songs.owner_id', $user->id)
                        ->orWhere('songs.is_public', true);
                });
        });
    }
}
